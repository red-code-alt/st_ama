<?php

namespace Drupal\redirect;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\migrate_drupal\NodeMigrateType;

/**
 * Migration plugin alterer service for redirect migrations.
 *
 * @internal
 */
final class MigrationPluginAlterer {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The current active database's master connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The migration plugin manager, if available.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   */
  protected $migrationPluginManager;

  /**
   * Constructs a new MigrationPluginAlterer.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Database\Connection $connection
   *   The active database connection.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler, Connection $connection) {
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler= $module_handler;
    $this->connection = $connection;
  }

  /**
   * Sets the migration plugin manager.
   *
   * @param \Drupal\migrate\Plugin\MigrationPluginManagerInterface $migration_plugin_manager
   *   The migration plugin manager.
   */
  public function setMigrationPluginManager(MigrationPluginManagerInterface $migration_plugin_manager) {
    $this->migrationPluginManager = $migration_plugin_manager;
  }

  /**
   * Adds dependency to the redirect migration derivatives of content entities.
   *
   * @param array $migrations
   */
  public function alterMigrationPlugins(array &$migrations) {
    // When the migration plugin manager isn't available, we don't have to do
    // anything.
    if (!$this->migrationPluginManager) {
      return;
    }
    $redirect_destination_migration_ids = [];
    $locatable_entity_type_destination_migration_ids = [];
    $optional_migrations_per_entity_type = [];
    // Migration plugin IDs of content entity migrations not having a
    // corresponding URL alias (path alias) migration. These will be used as the
    // optional migration dependencies of the 'catch-all' url alias migration
    // derivative.
    $other_entity_destination_migrations = [];
    $complete_node_migration = FALSE;
    if (
      $this->moduleHandler->moduleExists('migrate_drupal') &&
      $this->moduleHandler->moduleExists('node') &&
      class_exists(NodeMigrateType::class)
    ) {
      $source_drupal_version = $this->getSourceDrupalInstanceVersion($migrations);
      $node_migrate_type = NodeMigrateType::getNodeMigrateType($this->connection, $source_drupal_version);
      $complete_node_migration = $node_migrate_type === NodeMigrateType::NODE_MIGRATE_TYPE_COMPLETE;
    }

    // Get path_alias migrations and their related entity type IDs.
    foreach ($migrations as $migration_plugin_id => $migration) {
      // Track which migrations have \Drupal\redirect\Entity\Redirect as their
      // destination.
      if ($migration['destination']['plugin'] === 'entity:redirect') {
        $redirect_destination_migration_ids[] = $migration_plugin_id;

        // Track which content entity migrations have an entity type specific
        // path alias migration derivative. Here we just pre-populate with an
        // empty array.
        if (!empty($migration['source']['entity_type_id']) && !isset($optional_migrations_per_entity_type[$migration['source']['entity_type_id']])) {
          $optional_migrations_per_entity_type[$migration['source']['entity_type_id']] = [];
        }
      }
    }

    // Collect every content entity migration ID which has link templates.
    foreach ($migrations as $migration_plugin_id => $migration) {
      if (!empty($migration['destination'])) {
        $destination_plugin_parts = explode(':', $migration['destination']['plugin']);
        $entity_destination_plugins = ['entity', 'entity_complete'];
        $entity_type_id = in_array($destination_plugin_parts[0], $entity_destination_plugins, TRUE) ?
          $destination_plugin_parts[1] : NULL;

        // Skip if the destination of this migration is not an entity.
        if (!$entity_type_id) {
          continue;
        }

        // Skip if the entity type definition is not available
        // (e.g. d7_node_translation migrations without enabled node module).
        if (!($entity_type_definition = $this->entityTypeManager->getDefinition($entity_type_id, FALSE))) {
          continue;
        }

        // Skip entity types that are not content entities.
        if (!is_subclass_of($entity_type_definition->getClass(), ContentEntityInterface::class)) {
          continue;
        }

        // Skip path redirect and URL alias migrations.
        if (in_array($entity_type_id, ['redirect', 'path_alias'], TRUE)) {
          continue;
        }

        // Get only those link templates that use the entity type ID as a
        // parameter; so we don't care about collection or create templates.
        $link_templates_filtered = array_filter($entity_type_definition->getLinkTemplates(), function (string $template) use ($entity_type_id) {
          return mb_strpos($template, '{' . $entity_type_id . '}') !== FALSE;
        });

        if (!empty($link_templates_filtered)) {
          // Only locatable entity types can have redirects.
          if (!array_key_exists('canonical', $link_templates_filtered)) {
            continue;
          }
          // Locatable entity types may only seem locatable: if their edit-form
          // link template matches their canonical link template, then it's
          // really an admin-only entity type, where redirects do not make
          // sense.
          if (array_key_exists('edit-form', $link_templates_filtered) && $link_templates_filtered['canonical'] === $link_templates_filtered['edit-form']) {
            continue;
          }

          // If this migration's entity type does not have a corresponding path
          // redirect migration, it will be used as optional dependency.
          if (!isset($optional_migrations_per_entity_type[$entity_type_id])) {
            $other_entity_destination_migrations[] = $migration_plugin_id;
          }
          // But if the entity type has a corresponding path redirect migration,
          // we will add it as a dependency to the related redirect migration.
          else {
            $optional_migrations_per_entity_type[$entity_type_id][$migration_plugin_id] = $migration_plugin_id;
          }

          // For a non-derived path redirect migration, we need all of the
          // discovered content entity migrations as dependency.
          // @todo Remove this when the d6_path_redirect migration also is being
          //   derived.
          $locatable_entity_type_destination_migration_ids[] = $migration_plugin_id;
        }
      }
    }

    // Assign the optional migration dependencies to all migrations that are
    // generating redirect entities: it makes no sense to have the redirect
    // migrated when its destination is missing.
    foreach ($redirect_destination_migration_ids as $migration_plugin_id) {
      $migration_plugin = &$migrations[$migration_plugin_id];
      $migration_plugin += ['migration_dependencies' => []];
      $migration_plugin['migration_dependencies'] += [
        'required' => [],
        'optional' => [],
      ];

      // @todo The d6_path_redirect migration is not derived yet.
      if (!in_array('Drupal 7', $migration_plugin['migration_tags']) || empty($migration_plugin['deriver'])) {
        $migration_plugin['migration_dependencies']['optional'] = array_unique(array_merge($migration_plugin['migration_dependencies']['optional'], $locatable_entity_type_destination_migration_ids));
      }
      else {
        // If this path redirect migration is not entity type specific, add the
        // previously discovered optional migration dependencies.
        if (empty($migration_plugin['source']['entity_type_id'])) {
          $migration_plugin['migration_dependencies']['optional'] = array_unique(array_merge($migration_plugin['migration_dependencies']['optional'], $other_entity_destination_migrations));
        }
        // If the path redirect migration is derived by entity type, we can be
        // more specific.
        else {
          $redirect_target_entity_type_id = $migration_plugin['source']['entity_type_id'];
          $redirect_target_entity_bundle = $migration_plugin['source']['bundle'] ?? NULL;
          $content_entity_migration_id = NULL;
          $required_dependencies = [];

          switch ($redirect_target_entity_type_id) {
            case 'node':
              $content_entity_migration_id = $complete_node_migration ? 'd7_node_complete' : 'd7_node';
              break;

            case 'taxonomy_term':
              $content_entity_migration_id = 'd7_taxonomy_term';
              break;

            case 'user':
              $content_entity_migration_id = 'd7_user';
              break;
          }

          // If the path redirect migration is derived by entity type AND
          // bundle, we filter on the potential derived entity migrations, and
          // only add what we need.
          if ($redirect_target_entity_bundle) {
            if ($content_entity_migration_id) {
              if (isset($migrations["$content_entity_migration_id:$redirect_target_entity_bundle"])) {
                $required_dependencies[] = "$content_entity_migration_id:$redirect_target_entity_bundle";
              }
              elseif (isset($migrations[$content_entity_migration_id])) {
                $required_dependencies[] = $content_entity_migration_id;
              }
            }

            $optional_dependencies = array_reduce($optional_migrations_per_entity_type[$redirect_target_entity_type_id], function (array $carry, string $item) use ($redirect_target_entity_bundle) {
              $parts = explode(':', $item);
              // We will add the dependency requirement in two cases:
              // - When the migration plugin is not derived by entity bundle – so
              //   $parts[1] is not available.
              // - When $parts[1] is equals the current target entity bundle.
              if (!isset($parts[1]) || $parts[1] === $redirect_target_entity_bundle) {
                $carry[$item] = $item;
              }
              return $carry;
            }, []);
          }
          // If this migration is derived only by entity type, we already know the
          // dependencies.
          else {
            if ($content_entity_migration_id && isset($migrations[$content_entity_migration_id])) {
              $required_dependencies[] = $content_entity_migration_id;
            }
            $optional_dependencies = $optional_migrations_per_entity_type[$redirect_target_entity_type_id] ?? [];
          }

          $migration_plugin['migration_dependencies']['required'] = array_unique(array_merge($migration_plugin['migration_dependencies']['required'], array_values($required_dependencies)));
          $migration_plugin['migration_dependencies']['optional'] = array_unique(array_merge($migration_plugin['migration_dependencies']['optional'], array_values($optional_dependencies)));
        }
      }
    }
  }

  /**
   * Gets the version of the migration's Drupal source instance.
   *
   * @see \Drupal\migrate_drupal\MigrationConfigurationTrait::getLegacyDrupalVersion()
   *
   * @param array[] $migrations
   *   Migration plugins, keyed by the migration plugin ID.
   *
   * @return string|false
   *   The version of the source Drupal instance ('5', '6' or '7'), or FALSE if
   *   it cannot be determined.
   */
  protected function getSourceDrupalInstanceVersion(array $migrations) {
    $version = FALSE;
    // We use the source plugin of the system_site migration only for getting
    // the database connection.
    $system_site_migration = $migrations['system_site'] ?? NULL;

    if (!$system_site_migration) {
      return FALSE;
    }

    // We need to get the version of the source database in order to check if
    // the classic or complete node tables have been used in a migration.
    $source_plugin = $this->migrationPluginManager
      ->createStubMigration($system_site_migration)
      ->getSourcePlugin();
    if (!($source_plugin instanceof SqlBase)) {
      return FALSE;
    };

    try {
      $source_connection = $source_plugin->getDatabase();
      $version_string = FALSE;

      if ($source_connection->schema()->tableExists('system')) {
        try {
          $version_string = $source_connection
            ->select('system', 's')
            ->fields('s', ['schema_version'])
            ->condition('s.name', 'system')
            ->execute()
            ->fetchField();
          if ($version_string && $version_string[0] == '1' && (int) $version_string >= 1000) {
            $version_string = '5';
          }
        }
        catch (\PDOException $e) {
        }
      }

      $version = $version_string ? substr($version_string, 0, 1) : FALSE;
    }
    catch (\Exception $e) {
    }

    return $version;
  }
}
