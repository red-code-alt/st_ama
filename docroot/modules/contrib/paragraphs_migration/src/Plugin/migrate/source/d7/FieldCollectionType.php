<?php

namespace Drupal\paragraphs_migration\Plugin\migrate\source\d7;

use Drupal\migrate\Row;
use Drupal\paragraphs_migration\Plugin\migrate\source\DrupalSqlBase;

/**
 * Field Collection Type source plugin.
 *
 * Available configuration keys:
 * - add_description: (bool) (optional) If enabled this will add a default
 *   description to the source data. default:FALSE.
 *
 * @MigrateSource(
 *   id = "d7_pm_field_collection_type",
 *   source_module = "field_collection"
 * )
 */
class FieldCollectionType extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'add_description' => FALSE,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('field_config', 'fc')
      ->fields('fc');
    $query->condition('fc.type', 'field_collection');
    $query->condition('fc.active', TRUE);
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $name = $row->getSourceProperty('field_name');

    // Field collections don't have descriptions, optionally add one.
    if ($this->configuration['add_description']) {
      $row->setSourceProperty('description', 'Migrated from field_collection ' . $name);
    }
    else {
      $row->setSourceProperty('description', '');
    }

    // Set label from bundle because we don't have a label in D7 field
    // collections.
    $row->setSourceProperty(
      'name',
      ucfirst(
        preg_replace(
          ['/^field_/', '/_+/'],
          ['', ' '],
          $name
        )
      )
    );

    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'field_name' => $this->t('Original field collection bundle/field_name'),
      'bundle' => $this->t('Paragraph type machine name'),
      'name' => $this->t('Paragraph type label'),
      'description' => $this->t('Paragraph type description'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['field_name']['type'] = 'string';

    return $ids;
  }

}
