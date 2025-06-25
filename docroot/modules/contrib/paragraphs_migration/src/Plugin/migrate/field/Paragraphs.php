<?php

namespace Drupal\paragraphs_migration\Plugin\migrate\field;

use Drupal\migrate\Plugin\MigrationInterface;

/**
 * Field Plugin for paragraphs migrations.
 *
 * @MigrateField(
 *   id = "pm_paragraphs",
 *   core = {7},
 *   type_map = {
 *     "paragraphs" = "entity_reference_revisions",
 *   },
 *   source_module = "paragraphs",
 *   destination_module = "paragraphs",
 * )
 */
class Paragraphs extends ParagraphsFieldPluginBase {

  /**
   * {@inheritdoc}
   */
  protected $migrationDependencyBaseId = 'd7_pm_paragraphs';

  /**
   * {@inheritdoc}
   */
  protected $revisionMigrationDependencyBaseId = 'd7_pm_paragraphs_revisions';

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
          'migration' => 'd7_pm_paragraphs',
          'source' => 'value',
        ],
        'rev_lookup' => [
          'plugin' => 'migmag_lookup',
          'migration' => 'd7_pm_paragraphs_revisions',
          'source' => 'revision_id',
        ],
        'target_id' => [
          'plugin' => 'skip_on_empty',
          'method' => 'process',
          'source' => '@id_lookup/0',
        ],
        // Try to restore some level of data integrity if the corresponding
        // paragraphs revision ID is missing.
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

    // Add paragraphs migration as a dependency (if this is not a paragraph
    // migration).
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
  public function alterFieldWidgetMigration(MigrationInterface $migration) {
    parent::alterFieldWidgetMigration($migration);
    $this->paragraphAlterFieldWidgetMigration($migration);
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
      'paragraphs_view' => 'entity_reference_revisions_entity_view',
    ] + parent::getFieldFormatterMap();
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldWidgetMap() {
    return [
      'paragraphs_embed' => 'entity_reference_paragraphs',
    ] + parent::getFieldWidgetMap();
  }

  /**
   * {@inheritdoc}
   */
  public function alterFieldMigration(MigrationInterface $migration) {
    $settings = [
      'paragraphs' => [
        'plugin' => 'pm_paragraphs_field_settings',
      ],
    ];
    $migration->mergeProcessOfProperty('settings', $settings);
  }

  /**
   * {@inheritdoc}
   */
  public function alterFieldInstanceMigration(MigrationInterface $migration) {
    $settings = [
      'paragraphs' => [
        'plugin' => 'pm_paragraphs_field_instance_settings',
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
      'paragraphs' => [
        'plugin' => 'pm_paragraphs_process_on_value',
        'source_value' => 'type',
        'expected_value' => 'paragraphs',
        'process' => [
          'plugin' => 'get',
          'source' => 'formatter/settings/view_mode',
        ],
      ],
    ];
    $migration->mergeProcessOfProperty('options/settings/view_mode', $view_mode);
  }

  /**
   * Adds processes for paragraphs field widgets.
   *
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The migration.
   */
  protected function paragraphAlterFieldWidgetMigration(MigrationInterface $migration) {
    $title = [
      'paragraphs' => [
        'plugin' => 'pm_paragraphs_process_on_value',
        'source_value' => 'type',
        'expected_value' => 'paragraphs',
        'process' => [
          'plugin' => 'get',
          'source' => 'settings/title',
        ],
      ],
    ];
    $title_plural = [
      'paragraphs' => [
        'plugin' => 'pm_paragraphs_process_on_value',
        'source_value' => 'type',
        'expected_value' => 'paragraphs',
        'process' => [
          'plugin' => 'get',
          'source' => 'settings/title_multiple',
        ],
      ],
    ];
    $edit_mode = [
      'paragraphs' => [
        'plugin' => 'pm_paragraphs_process_on_value',
        'source_value' => 'type',
        'expected_value' => 'paragraphs',
        'process' => [
          'plugin' => 'get',
          'source' => 'settings/default_edit_mode',
        ],
      ],
    ];
    $add_mode = [
      'paragraphs' => [
        'plugin' => 'pm_paragraphs_process_on_value',
        'source_value' => 'type',
        'expected_value' => 'paragraphs',
        'process' => [
          'plugin' => 'get',
          'source' => 'settings/add_mode',
        ],
      ],
    ];

    $migration->mergeProcessOfProperty('options/settings/title', $title);
    $migration->mergeProcessOfProperty('options/settings/title_plural', $title_plural);
    $migration->mergeProcessOfProperty('options/settings/edit_mode', $edit_mode);
    $migration->mergeProcessOfProperty('options/settings/add_mode', $add_mode);
  }

}
