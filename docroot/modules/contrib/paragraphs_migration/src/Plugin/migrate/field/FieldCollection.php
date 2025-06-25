<?php

namespace Drupal\paragraphs_migration\Plugin\migrate\field;

use Drupal\migrate\Plugin\MigrationInterface;

/**
 * Field Plugin for field collection migrations.
 *
 * @MigrateField(
 *   id = "pm_field_collection",
 *   core = {7},
 *   type_map = {
 *     "field_collection" = "entity_reference_revisions",
 *   },
 *   source_module = "field_collection",
 *   destination_module = "paragraphs",
 * )
 */
class FieldCollection extends ParagraphsFieldPluginBase {

  /**
   * {@inheritdoc}
   */
  protected $migrationDependencyBaseId = 'd7_pm_field_collection';

  /**
   * {@inheritdoc}
   */
  protected $revisionMigrationDependencyBaseId = 'd7_pm_field_collection_revisions';

  /**
   * {@inheritdoc}
   */
  public function defineValueProcessPipeline(MigrationInterface $migration, $field_name, $data) {
    $process = [
      // The "pm_paragraphs_delta_sort" plugin sorts field values for PostgreSQL
      // sources.
      // @see \Drupal\paragraphs\Plugin\migrate\process\ParagraphsDeltaSort
      // @todo remove when https://drupal.org/i/3164520 is fixed.
      [
        'plugin' => 'pm_paragraphs_delta_sort',
        'source' => $field_name,
      ],
    ];
    $process[] = [
      'plugin' => 'sub_process',
      'process' => [
        'id_lookup' => [
          'plugin' => 'migmag_lookup',
          'migration' => 'd7_pm_field_collection',
          'source' => 'value',
        ],
        'rev_lookup' => [
          'plugin' => 'migmag_lookup',
          'migration' => 'd7_pm_field_collection_revisions',
          'source' => 'revision_id',
        ],
        'target_id' => [
          'plugin' => 'skip_on_empty',
          'method' => 'process',
          'source' => '@id_lookup/0',
        ],
        // Try to restore some level of data integrity if the corresponding
        // field collection revision ID is missing.
        'target_revision_id' => [
          [
            'plugin' => 'null_coalesce',
            'source' => [
              '@rev_lookup/1',
              '@id_lookup/1',
            ],
            'default_value' => NULL,
          ],
          [
            'plugin' => 'skip_on_empty',
            'method' => 'process',
          ],
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
    ];
    $migration->setProcessOfProperty($field_name, $process);

    // Add the respective field collection migration as a dependency.
    $dependency_type = static::getComponentDependencyType($migration->getBaseId(), $migration->getSourceConfiguration());
    $dependencies = $migration->getMigrationDependencies();
    $required_migrations = $this->getParentBasedMigrationDependencies($migration, $field_name);
    $dependencies[$dependency_type] = array_unique(
      array_merge(
        array_values($dependencies[$dependency_type]),
        $required_migrations
      )
    );
    $migration->set('migration_dependencies', $dependencies);
  }

  /**
   * {@inheritdoc}
   */
  public function alterFieldFormatterMigration(MigrationInterface $migration) {
    $this->addViewModeProcess($migration);
    parent::alterFieldFormatterMigration($migration);
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldFormatterMap() {
    return [
      'field_collection_view' => 'entity_reference_revisions_entity_view',
      'field_collection_fields' => 'entity_reference_revisions_entity_view',
    ] + parent::getFieldFormatterMap();
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldWidgetMap() {
    return ['field_collection_embed' => 'entity_reference_paragraphs']
      + parent::getFieldWidgetMap();
  }

  /**
   * {@inheritdoc}
   */
  public function alterFieldMigration(MigrationInterface $migration) {
    $settings = [
      'field_collection' => [
        'plugin' => 'pm_field_collection_field_settings',
      ],
    ];
    $migration->mergeProcessOfProperty('settings', $settings);
  }

  /**
   * {@inheritdoc}
   */
  public function alterFieldInstanceMigration(MigrationInterface $migration) {
    $settings = [
      'field_collection' => [
        'plugin' => 'pm_field_collection_field_instance_settings',
      ],
    ];
    $migration->mergeProcessOfProperty('settings', $settings);
  }

  /**
   * Adds process for view mode settings.
   *
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The migration.
   */
  protected function addViewModeProcess(MigrationInterface $migration) {
    $view_mode = [
      'field_collection' => [
        'plugin' => 'pm_paragraphs_process_on_value',
        'source_value' => 'type',
        'expected_value' => 'field_collection',
        'process' => [
          'plugin' => 'get',
          'source' => 'formatter/settings/view_mode',
        ],
      ],
    ];
    $migration->mergeProcessOfProperty('options/settings/view_mode', $view_mode);
  }

}
