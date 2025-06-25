<?php

namespace Drupal\paragraphs_migration\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\migrate\process\MigrationLookup;
use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Process plugin which returns data of the source entity.
 *
 * The multifield field value process pipeline needs the source entity_type,
 * source entity ID and revision ID.
 *
 * Unfortunately, these values are explicitly excluded from the field values
 * be FieldableEntity::getFieldValues.
 *
 * @see \Drupal\migrate_drupal\Plugin\migrate\source\d7\FieldableEntity::getFieldValues
 * @see \paragraphs_query_migrate_field_values_alter()
 *
 * @MigrateProcessPlugin(
 *   id = "pm_multifield",
 *   handle_multiples = TRUE
 * )
 */
class Multifield extends MigrationLookup {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $this->configuration['no_stub'] = TRUE;
    $source_field_name = $this->configuration['source_field_name'];
    ksort($value);
    $source = $this->migration->getSourcePlugin();
    assert($source instanceof DrupalSqlBase);
    $field_value = [];

    foreach ($value as $delta => $item) {
      $lookup_results = parent::transform(
        [
          $item['entity_type'],
          $item['entity_id'],
          $source_field_name,
          $item['delta'],
          $item['revision_id'],
          $item['language'],
        ],
        $migrate_executable,
        $row,
        $destination_property
      );

      $field_value[$delta] = [
        'target_id' => $lookup_results[0],
        'target_revision_id' => $lookup_results[1],
        'needs_resave' => FALSE,
      ];
    }

    return $field_value;
  }

}
