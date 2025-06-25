<?php

namespace Drupal\paragraphs_migration;

use Drupal\Component\Utility\NestedArray;
use Drupal\migrate\Plugin\MigratePluginManagerInterface;
use Drupal\migrate\Plugin\MigrationDeriverTrait;
use Drupal\migrate\Row;
use Drupal\paragraphs_migration\Plugin\migrate\field\ParagraphsFieldPluginBase;

/**
 * Alters migrations for being compatible with paragraphs.
 */
final class MigrationPluginsAlterer {

  use MigrationDeriverTrait;

  /**
   * Map for the source entity type ID → type and default rev migration.
   *
   * @var string[][];
   */
  const PARAGRAPHS_ENTITY_TYPE_ID_MAP = [
    'field_collection_item' => [
      'bundle_migration' => 'd7_pm_field_collection_type',
      'default_revision_migration' => 'd7_pm_field_collection',
    ],
    'paragraphs_item' => [
      'bundle_migration' => 'd7_pm_paragraphs_type',
      'default_revision_migration' => 'd7_pm_paragraphs',
    ],
    'multifield' => [
      'bundle_migration' => 'pm_multifield_type',
      'default_revision_migration' => 'pm_multifield',
    ],
  ];

  /**
   * Alters migrations for paragraphs.
   *
   * @param array[] $migrations
   *   An associative array of migrations keyed by migration ID, the same that
   *   is passed to hook_migration_plugins_alter() hooks.
   */
  public function alterMigrationPlugins(array &$migrations) {
    $this->alterFieldMigrationPlugins($migrations);
    $this->finalizeContentEntityMigrationDependencies($migrations);
    $this->replaceCallbackPluginsInParagraphsMigrations($migrations);
    $this->logDataIntegrityIssuesIfPossible($migrations);
  }

  /**
   * Adds and finalizes field collection and paragraph migration dependencies.
   *
   * @param array[] $migrations
   *   An associative array of migrations keyed by migration ID, the same that
   *   is passed to hook_migration_plugins_alter() hooks.
   */
  protected function alterFieldMigrationPlugins(array &$migrations) {
    foreach ($migrations as $migration_plugin_id => &$migration) {
      // If this is not a Drupal 7 migration, we can skip processing it.
      if (!in_array('Drupal 7', $migration['migration_tags'] ?? [])) {
        continue;
      }

      // Adds field collection type and paragraph type migration dependencies to
      // field storage, field instance and field display mode migrations.
      foreach (['entity_type', 'targetEntityType'] as $process_property) {
        // Only add the mappings if the property actually needs our mapping.
        try {
          $current_value = static::getSourceValueOfMigrationProcess($migration, $process_property);
          $this->paragraphsMigrationEntityTypeAdjustWithValue($migration, $process_property, $current_value);
          // From Drupal core 9.1.4+, the only field migration which needs the
          // bundle process alters is 'd7_field_instance', because the formatter
          // and the widget migration are using migration lookup for determining
          // the destination bundle.
          // @see https://drupal.org/i/2565931
          if (
            (
              version_compare(\Drupal::VERSION, '9.1.4', 'lt') &&
              \Drupal::VERSION !== '9.1.x-dev'
            ) ||
            $migration['id'] === 'd7_field_instance'
          ) {
            $this->paragraphsMigrationBundleAdjust($migration);
          }
        }
        catch (\LogicException $e) {
          // The migration's source or the given process property key is missing
          // form the current migration.
        }
      }
    }
  }

  /**
   * Finalizes content entity migration dependencies.
   *
   * Finalizes dependencies of content entity migrations where paragraphs
   * fields are present. The raw (and at this point, not accurate) migration
   * dependency IDs are added in
   * ParagraphsFieldPluginBase::getParentBasedMigrationDependencies().
   *
   * @param array[] $migrations
   *   An associative array of migrations keyed by migration ID, the same that
   *   is passed to hook_migration_plugins_alter() hooks.
   *
   * @see \Drupal\paragraphs\Plugin\migrate\field\ParagraphsFieldPluginBase::getParentBasedMigrationDependencies()
   */
  protected function finalizeContentEntityMigrationDependencies(array &$migrations) {
    $migration_plugin_ids = array_keys($migrations);

    foreach ($migrations as $migration_plugin_id => &$migration) {
      // If this is not a Drupal 7 migration, we can skip processing it.
      if (!in_array('Drupal 7', $migration['migration_tags'] ?? [])) {
        continue;
      }

      $paragraph_unfinalized_dependencies = $migration['source'][ParagraphsMigration::PARAGRAPHS_RAW_DEPENDENCIES] ?? [];
      $component_dependency_type = ParagraphsFieldPluginBase::getComponentDependencyType($migration['id'], $migration['source']);
      foreach ($paragraph_unfinalized_dependencies as $unfinalized_dependency) {
        if (($dep_key = array_search($unfinalized_dependency, $migration['migration_dependencies'][$component_dependency_type])) !== FALSE) {
          unset($migration['migration_dependencies'][$component_dependency_type][$dep_key]);
          $finalized_dependencies = array_reduce($migration_plugin_ids, function (array $carry, string $plugin_id) use ($unfinalized_dependency) {
            if (strpos($plugin_id, $unfinalized_dependency) === 0) {
              $carry[] = $plugin_id;
            }
            return $carry;
          }, []);

          // We have to remove the potential self-dependency and let the
          // "pm_paragraphs_lookup" migrate process plugin stub the
          // not-yet-migrated entities.
          if (($self_dep_key = array_search($migration_plugin_id, $finalized_dependencies)) !== FALSE) {
            unset($finalized_dependencies[$self_dep_key]);
          }
          $migration['migration_dependencies'][$component_dependency_type] = array_unique(array_merge($migration['migration_dependencies'][$component_dependency_type], $finalized_dependencies));
        }
      }
      unset($migration['source'][ParagraphsMigration::PARAGRAPHS_RAW_DEPENDENCIES]);
    }
  }

  /**
   * Map 'field_collection_item' and 'paragraphs_item' fields to 'paragraph'.
   *
   * @param array $migration
   *   The migration to process.
   * @param string $process_property
   *   The process destination.
   * @param mixed $current_value
   *   The current value, or NULL if it wasn't determinable.
   */
  public function paragraphsMigrationEntityTypeAdjustWithValue(array &$migration, string $process_property, $current_value) {
    if (!self::paragraphsMigrationPrepareProcess($migration['process'], $process_property)) {
      return;
    }

    switch ($current_value) {
      case 'paragraphs_item':
      case 'multifield':
      case 'field_collection_item':
        $migration['source']["paragraphs_$process_property"] = 'paragraph';
        $migration['process'][$process_property] = "paragraphs_$process_property";
        break;

      case NULL:
        $entity_type_process = &$migration['process'][$process_property];
        $entity_type_process[] = [
          'plugin' => 'static_map',
          'map' => array_combine(
            array_keys(self::PARAGRAPHS_ENTITY_TYPE_ID_MAP),
            array_fill(0, count(self::PARAGRAPHS_ENTITY_TYPE_ID_MAP), 'paragraph')
          ),
          'bypass' => TRUE,
        ];
        break;
    }
  }

  /**
   * Map 'field_collection_item' and 'paragraphs_item' fields to 'paragraph'.
   *
   * @param array $migration
   *   The migration to process.
   * @param string $process_property
   *   The process destination.
   */
  public function paragraphsMigrationEntityTypeAdjust(array &$migration, string $process_property) {
    try {
      $current_value = static::getSourceValueOfMigrationProcess($migration, $process_property);
      $this->paragraphsMigrationEntityTypeAdjustWithValue($migration, $process_property, $current_value);
    }
    catch (\LogicException $e) {
      // Source or the process property key is missing form the given migration.
    }
  }

  /**
   * Make sure paragraph types properties are using destination type lookups.
   *
   * @param array $migration
   *   The migration configuration to process.
   * @param mixed $source_entity_type_id
   *   The current entity type ID, or NULL if it wasn't determinable.
   */
  public function paragraphsMigrationBundleAdjust(array &$migration, $source_entity_type_id = NULL) {
    $bundle_process_key_to_alter = in_array('bundle_mapped', array_keys($migration['process']))
      ? 'bundle_mapped'
      : 'bundle';

    if (!$this->paragraphsMigrationPrepareProcess($migration['process'], $bundle_process_key_to_alter)) {
      return;
    }

    switch ($source_entity_type_id) {
      case 'field_collection_item':
        self::addEntityBundleLookup($migration, $bundle_process_key_to_alter, 'field_collection_item');
        $migration['migration_dependencies']['required'] = array_unique(
          array_merge(
            $migration['migration_dependencies']['required'] ?? [],
             [self::PARAGRAPHS_ENTITY_TYPE_ID_MAP['field_collection_item']['bundle_migration']]
          )
        );
        break;

      case 'paragraphs_item':
        self::addEntityBundleLookup($migration, $bundle_process_key_to_alter, 'paragraphs_item');
        $migration['migration_dependencies']['required'] = array_unique(
          array_merge(
            $migration['migration_dependencies']['required'] ?? [],
            [self::PARAGRAPHS_ENTITY_TYPE_ID_MAP['paragraphs_item']['bundle_migration']]
          )
        );
        break;

      case 'multifield':
        self::addEntityBundleLookup($migration, $bundle_process_key_to_alter, 'multifield');
        $migration['migration_dependencies']['required'] = array_unique(
          array_merge(
            $migration['migration_dependencies']['required'] ?? [],
            [self::PARAGRAPHS_ENTITY_TYPE_ID_MAP['multifield']['bundle_migration']]
          )
        );
        break;

      case NULL:
        self::addEntityBundleLookup($migration, $bundle_process_key_to_alter, 'field_collection_item');
        self::addEntityBundleLookup($migration, $bundle_process_key_to_alter, 'paragraphs_item');
        self::addEntityBundleLookup($migration, $bundle_process_key_to_alter, 'multifield');
        $migration['migration_dependencies']['optional'] = array_unique(
          array_merge(
            $migration['migration_dependencies']['optional'] ?? [],
            array_reduce(self::PARAGRAPHS_ENTITY_TYPE_ID_MAP, function (array $carry, array $data) {
              $carry[] = $data['bundle_migration'];
              return $carry;
            }, [])
          )
        );
        break;
    }
  }

  /**
   * Adds field collection destination bundle lookup to the (field) migration.
   *
   * @param array $migration
   *   The migration configuration to process.
   * @param string $bundle_process_key_to_alter
   *   The bundle destination property.
   * @param string $source_entity_type_id
   *   The entity type ID on the source site. Must be a mapped key in
   *   PARAGRAPHS_ENTITY_TYPE_ID_MAP.
   */
  protected static function addEntityBundleLookup(array &$migration, string $bundle_process_key_to_alter, string $source_entity_type_id) {
    if (!isset($migration['process'][$bundle_process_key_to_alter]["{$source_entity_type_id}_bundle"])) {
      $migration['process'][$bundle_process_key_to_alter]["{$source_entity_type_id}_bundle"] = [
        'plugin' => 'pm_paragraphs_process_on_value',
        'source_value' => 'entity_type',
        'expected_value' => $source_entity_type_id,
        'process' => [
          'plugin' => 'migration_lookup',
          'migration' => self::PARAGRAPHS_ENTITY_TYPE_ID_MAP[$source_entity_type_id]['bundle_migration'],
          'no_stub' => TRUE,
        ],
      ];
    }
  }

  /**
   * Converts a migration process to array for adding another process elements.
   *
   * @param array $process
   *   The array of process definitions of a migration.
   * @param string $property
   *   The property which process definition should me converted to an array of
   *   process definitions.
   *
   * @return bool
   *   TRUE when the action was successful, FALSE otherwise.
   */
  public static function paragraphsMigrationPrepareProcess(array &$process, $property): bool {
    if (!array_key_exists($property, $process)) {
      return FALSE;
    }

    $process_element = &$process[$property];

    // Try to play with other modules altering this, and don't replace it
    // outright unless it's unchanged.
    if (is_string($process_element)) {
      $process_element = [
        [
          'plugin' => 'get',
          'source' => $process_element,
        ],
      ];
    }
    elseif (is_array($process_element) && array_key_exists('plugin', $process_element)) {
      $process_element = [$process_element];
    }

    if (!is_array($process_element)) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Replaces callback process plugin in paragraphs migrations if needed.
   *
   * Paragraphs adds an improved version of 'callback' process plugin from
   * Drupal core 9.2.0+ for prior core releases. That version comes with a new
   * option that makes us able to use the return value of functions with
   * multiple arguments, meaning that we can remove the 'field_' prefix of field
   * collection source 'bundles'.
   *
   * @param array[] $migrations
   *   An associative array of migrations keyed by migration ID, the same that
   *   is passed to hook_migration_plugins_alter() hooks.
   *
   * @see paragraphs_migrate_process_info_alter()
   * @see https://www.drupal.org/node/3205079
   */
  protected function replaceCallbackPluginsInParagraphsMigrations(array &$migrations) {
    if (version_compare(\Drupal::VERSION, '9.2.x-dev', 'ge')) {
      return;
    }

    $paragraphs_migrations = array_filter($migrations, function ($definition) {
      return $definition['provider'] === 'paragraphs';
    });
    foreach ($paragraphs_migrations as $plugin_id => $definition) {
      $this->replaceProcessPluginInPipelines($migrations[$plugin_id]['process'], 'callback', 'paragraphs_callback');
    }
  }

  /**
   * Logs data integrity issues into "migrate" log channel.
   *
   * There might be data integrity issues on Drupal 7 sites which have been
   * using the Paragraphs module since its initial releases – meaning that the
   * referred paragraph entity of an older node revision might be deleted
   * completely. By default, paragraphs (revision) migrations are saving migrate
   * messages in these situations, but that means that devs might see a tons of
   * messages they cannot solve (because its a data integrity issue of the
   * source Drupal 7 instance), and it might be very frustrating.
   *
   * This method changes this default behavior. Instead of saving migrate
   * messages, this will log the issues directly into the "migrate" logger
   * channel if Migrate Magician module's "migmag_logger_log" process plugin is
   * present.
   *
   * @param array[] $migrations
   *   An associative array of migrations keyed by migration ID, the same that
   *   is passed to hook_migration_plugins_alter() hooks.
   */
  protected function logDataIntegrityIssuesIfPossible(array &$migrations) {
    $process_plugin_manager = \Drupal::service('plugin.manager.migrate.process');
    assert($process_plugin_manager instanceof MigratePluginManagerInterface);
    $migmag_logger_log_available = $process_plugin_manager->hasDefinition('migmag_logger_log');
    if (!$migmag_logger_log_available) {
      return;
    }
    $paragraphs_revision_migrations = array_filter($migrations, function ($definition) {
      $tags = $definition['migration_tags'] ?? [];
      return in_array('Field Collection Revisions Content', $tags, TRUE) ||
        in_array('Paragraphs Revisions Content', $tags, TRUE);
    });
    foreach ($paragraphs_revision_migrations as $plugin_id => $definition) {
      if (
        isset($definition['process']['missing_revision_logging']['message']) &&
        isset($definition['process']['id_lookup'])
      ) {
        $message_default = $definition['process']['missing_revision_logging']['message'];
        $migrations[$plugin_id]['process']['missing_revision_logging'] = [
          [
            'plugin' => 'callback',
            'callable' => [get_class($this), 'isEmpty'],
            'source' => '@id_lookup',
          ],
          // Skip continuing this process pipeline if lookup has results.
          [
            'plugin' => 'skip_on_empty',
            'method' => 'process',
          ],
          // Use MigMagLoggerLog to log a message to a logger channel.
          [
            'plugin' => 'migmag_logger_log',
            'logger_channel' => 'migrate',
            'source' => [
              'item_id',
              'revision_id',
            ],
            'message' => 'ID %s, revision ID %s: ' . $message_default,
          ],
          // We just logged a message, now we have to skip this the current row.
          [
            'plugin' => 'callback',
            'callable' => [get_class($this), 'isEmpty'],
          ],
          [
            'plugin' => 'skip_on_empty',
            'method' => 'row',
          ],
        ];
      }
    }
  }

  /**
   * Replaces the specified process plugin ID in the given process pipeline def.
   *
   * @param array $process_pipelines
   *   The whole migration process definition, keyed by the migration
   *   destination properties.
   * @param string $original_process_plugin_id
   *   The original process plugin ID to replace.
   * @param string $replacement_process_plugin_id
   *   The new process plugin ID to use instead of the original plugin ID.
   *
   * @internal
   *   This method should only be called from
   *   \Drupal\paragraphs\MigrationPluginsAlterer::doReplaceProcessPlugin() or
   *   \Drupal\paragraphs\MigrationPluginsAlterer::replaceCallbackPluginsInParagraphsMigrations().
   */
  protected function replaceProcessPluginInPipelines(array &$process_pipelines, string $original_process_plugin_id, string $replacement_process_plugin_id) {
    foreach ($process_pipelines as &$process_pipeline) {
      if (is_string($process_pipeline)) {
        continue;
      }

      if (!empty($process_pipeline['plugin'])) {
        $this->doReplaceProcessPlugin($process_pipeline, $original_process_plugin_id, $replacement_process_plugin_id);
      }
      else {
        foreach ($process_pipeline as &$subprocess_config) {
          $this->doReplaceProcessPlugin($subprocess_config, $original_process_plugin_id, $replacement_process_plugin_id);
        }
      }
    }
  }

  /**
   * Replaces the specified process plugin ID in a single process configuration.
   *
   * @param array $process_config
   *   A single process plugin configuration to parse.
   * @param string $original_process_plugin_id
   *   The original process plugin ID to replace.
   * @param string $replacement_process_plugin_id
   *   The new process plugin ID to use instead of the original plugin ID.
   *
   * @internal
   *   This method should only be called from
   *   \Drupal\paragraphs\MigrationPluginsAlterer::replaceProcessPluginInPipelines().
   */
  protected function doReplaceProcessPlugin(array &$process_config, string $original_process_plugin_id, string $replacement_process_plugin_id) {
    switch ($process_config['plugin']) {
      case $original_process_plugin_id:
        $process_config['plugin'] = $replacement_process_plugin_id;
        break;

      case 'sub_process':
        if ($original_process_plugin_id !== 'sub_process') {
          $this->replaceProcessPluginInPipelines($process_config['process'], $original_process_plugin_id, $replacement_process_plugin_id);
        }
        break;
    }
  }

  /**
   * Gets the value of a process property if it is not dynamically calculated.
   *
   * @param array $migration
   *   The migration plugin's definition array.
   * @param string $process_property_key
   *   The property to check.
   *
   * @return mixed|null
   *   The value of the property if it can be determined, or NULL if it seems
   *   to be dynamic.
   *
   * @throws \LogicException.
   *   When the process property does not exists.
   */
  public static function getSourceValueOfMigrationProcess(array $migration, string $process_property_key) {
    if (
      !array_key_exists('process', $migration) ||
      !is_array($migration['process']) ||
      !array_key_exists($process_property_key, $migration['process'])
    ) {
      throw new \LogicException('No corresponding process found');
    }

    if (!self::paragraphsMigrationPrepareProcess($migration['process'], $process_property_key)) {
      throw new \LogicException('No corresponding process found');
    }

    $property_processes = $migration['process'][$process_property_key];
    $the_first_process = reset($property_processes);
    $property_value = NULL;

    if (
      !array_key_exists('source', $migration) ||
      count($property_processes) !== 1 ||
      $the_first_process['plugin'] !== 'get' ||
      empty($the_first_process['source'])
    ) {
      return NULL;
    }

    $process_value_source = $the_first_process['source'];

    // Parsing string values like "whatever" or "constants/whatever/key".
    // If the property is set to an already available value (e.g. a constant),
    // we don't need our special mapping applied.
    $property_value = NestedArray::getValue($migration['source'], explode(Row::PROPERTY_SEPARATOR, $process_value_source), $key_exists);

    // Migrations using the "embedded_data" source plugin actually contain
    // rows with source values.
    if (!$key_exists && $migration['source']['plugin'] === 'embedded_data') {
      $embedded_rows = $migration['source']['data_rows'] ?? [];
      $embedded_property_values = array_reduce($embedded_rows, function (array $carry, array $row) use ($process_value_source) {
        $embedded_value = NestedArray::getValue($row, explode(Row::PROPERTY_SEPARATOR, $process_value_source));
        $carry = array_unique(array_merge($carry, [$embedded_value]));
        return $carry;
      }, []);
      if (count($embedded_property_values) === 1 && $embedded_property_values[0] !== NULL) {
        $property_value = $embedded_property_values[0];
        $key_exists = TRUE;
      }

    }
    return $key_exists ? $property_value : NULL;
  }

  /**
   * Static function around PHP's empty().
   *
   * Since "empty" is not a PHP function (it is a language construct), we cannot
   * use it with core's "callback" process plugin. But we can use this method
   * instead.
   *
   * @param mixed $variable
   *   A variable.
   *
   * @return bool
   *   Whether the given variable is empty or not.
   */
  public static function isEmpty($variable): bool {
    return empty($variable);
  }

}
