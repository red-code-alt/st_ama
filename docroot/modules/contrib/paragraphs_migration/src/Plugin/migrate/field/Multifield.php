<?php

namespace Drupal\paragraphs_migration\Plugin\migrate\field;

use Drupal\Component\Plugin\PluginBase;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_drupal\Plugin\migrate\field\FieldPluginBase;

/**
 * Field Plugin for multifield.
 *
 * @MigrateField(
 *   id = "pm_multifield",
 *   core = {7},
 *   type_map = {
 *     "multifield" = "entity_reference_revisions",
 *   },
 *   source_module = "multifield",
 *   destination_module = "paragraphs",
 * )
 */
class Multifield extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function defineValueProcessPipeline(MigrationInterface $migration, $field_name, $data) {
    // Multifield field value migration needs the source entity_type,
    // source entity ID, revision ID, delta and language code.
    // Requires Entity Reference Revisions patch https://drupal.org/i/3218312
    // and Drupal core patch https://drupal.org/i/3218294.
    // @see paragraphs_query_migrate_field_values_alter()
    // @see Drupal\paragraphs\Utility\MultifieldMigration::addCruicalMultifieldFieldProperties()
    $lookup_migration = implode(PluginBase::DERIVATIVE_SEPARATOR, [
      'pm_multifield',
      $data['entity_type'],
      $data['bundle'],
      $field_name,
    ]);
    $process = [
      // The "pm_paragraphs_delta_sort" plugin sorts field values for PostgreSQL
      // sources.
      // @see \Drupal\paragraphs\Plugin\migrate\process\ParagraphsDeltaSort
      // @todo remove when https://drupal.org/i/3164520 is fixed.
      [
        'plugin' => 'pm_paragraphs_delta_sort',
        'source' => $field_name,
      ],
      [
        'plugin' => 'sub_process',
        'process' => [
          'source_field_name' => [
            'plugin' => 'default_value',
            'default_value' => $field_name,
          ],
          'lookup_result' => [
            [
              'plugin' => 'migration_lookup',
              'migration' => $lookup_migration,
              'no_stub' => TRUE,
              'source' => [
                'entity_type',
                'entity_id',
                '@source_field_name',
                'delta',
                'revision_id',
                'language',
              ],
            ],
            [
              'plugin' => 'skip_on_empty',
              'method' => 'process',
            ],
          ],
          'target_id' => [
            'plugin' => 'extract',
            'source' => '@lookup_result',
            'index' => [0],
          ],
          'target_revision_id' => [
            'plugin' => 'extract',
            'source' => '@lookup_result',
            'index' => [1],
          ],
          // This "needs_resave" tells Entity Reference Revisions to not
          // force-create new paragraphs revision while a migration adds a new
          // revision to the host entity.
          // @see https://drupal.org/i/3218312
          'needs_resave' => [
            'plugin' => 'default_value',
            'default_value' => FALSE,
          ],
        ],
      ],
    ];
    $migration->setProcessOfProperty($field_name, $process);
    $dependencies = $migration->getMigrationDependencies();
    $dependencies['required'] = array_unique(
      array_merge(
        $dependencies['required'],
        [$lookup_migration]
      )
    );
    $migration->set('migration_dependencies', $dependencies);
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldWidgetMap() {
    return [
      'multifield_default' => 'entity_reference_paragraphs',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldFormatterMap() {
    return [
      'multifield_default' => 'entity_reference_revisions_entity_view',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function alterFieldMigration(MigrationInterface $migration) {
    $original_process = $migration->getProcess()['settings'] ?? [];
    if (!self::processIsPresent($original_process, 'pm_paragraphs_field_settings')) {
      $new_process = array_merge(
        $original_process,
        [
          [
            'plugin' => 'pm_paragraphs_field_settings',
            'source_type' => 'multifield',
          ],
        ]
      );
      $migration->mergeProcessOfProperty('settings', $new_process);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function alterFieldInstanceMigration(MigrationInterface $migration) {
    $original_settings_process = $migration->getProcess()['settings'] ?? [];
    if (!self::processIsPresent($original_settings_process, 'pm_multifield_field_instance_settings')) {
      $new_process = array_merge(
        $original_settings_process,
        [
          [
            'plugin' => 'pm_multifield_field_instance_settings',
          ],
        ]
      );
      $migration->mergeProcessOfProperty('settings', $new_process);
    }

    $original_translatable_process = $migration->getProcess()['translatable'] ?? [];
    if (!self::processIsPresent($original_translatable_process, 'pm_multifield_field_translatable')) {
      $new_process = array_merge(
        $original_translatable_process,
        [
          [
            'plugin' => 'pm_multifield_field_translatable',
          ],
        ]
      );
      $migration->mergeProcessOfProperty('translatable', $new_process);
    }
  }

  /**
   * Checks whether a migration process is present in the given pipeline.
   *
   * @param array[] $process_pipeline
   *   A migration process pipeline (ofa destination property).
   * @param string|array[] $plugin
   *   The ID of the migrate process plugin to check for, or a complete process
   *   plugin configuration.
   *
   * @return bool
   *   TRUE if the plugin is used in the given process pipeline, FALSE if not.
   */
  protected static function processIsPresent(array $process_pipeline, $plugin) {
    return is_string($plugin)
      ? array_reduce($process_pipeline, function (bool $carry, array $process) use ($plugin) {
        $carry = $carry || $process['plugin'] === $plugin;
        return $carry;
      }, FALSE)
      : array_reduce($process_pipeline, function (bool $carry, array $process) use ($plugin) {
        $carry = $carry || $process === $plugin;
        return $carry;
      }, FALSE);
  }

}
