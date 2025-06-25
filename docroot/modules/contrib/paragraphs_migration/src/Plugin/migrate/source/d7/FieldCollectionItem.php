<?php

namespace Drupal\paragraphs_migration\Plugin\migrate\source\d7;

use Drupal\migrate\Row;

/**
 * Field Collection Item source plugin.
 *
 * Available configuration keys:
 * - field_name: (optional) If supplied, this will only return field collections
 *   of that particular type.
 *
 * @MigrateSource(
 *   id = "d7_pm_field_collection_item",
 *   source_module = "field_collection",
 * )
 */
class FieldCollectionItem extends FieldableEntity {

  /**
   * Join string for getting current revisions.
   */
  const JOIN = 'f.revision_id = fr.revision_id';

  /**
   * The prefix of the field table that contains the corresponding field values.
   *
   * @var string
   */
  const PARENT_FIELD_TABLE_PREFIX = 'field_data_';

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'field_name' => '',
      'parent_type' => NULL,
      'parent_bundle' => NULL,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    [
      'field_name' => $field_name,
      'parent_type' => $parent_type,
      'parent_bundle' => $parent_bundle,
    ] = $this->configuration;

    // Derived by parent entity type.
    if ($field_name && $parent_type) {
      $field_table = static::PARENT_FIELD_TABLE_PREFIX . $field_name;
      $query = $this->select($field_table, 'fd')
        ->fields('f', [
          'item_id',
          'field_name',
          'archived',
        ])
        ->fields('fr', ['revision_id'])
        ->condition('fd.entity_type', $parent_type);
      if ($parent_bundle) {
        $query->condition('fd.bundle', $parent_bundle);
      }
      $query->addField('fd', 'entity_type', 'parent_type');
      $query->addField('fd', 'entity_id', 'parent_id');

      $query->join('field_collection_item', 'f', "f.field_name = :field_name AND f.item_id = fd.{$field_name}_value AND f.revision_id = fd.{$field_name}_revision_id", [
        ':field_name' => $field_name,
      ]);
    }
    else {
      $query = $this->select('field_collection_item', 'f')
        ->fields('f', [
          'item_id',
          'field_name',
          'archived',
        ])
        ->fields('fr', ['revision_id']);

      // The parent field name may be set by a deriver to restrict the bundles
      // retrieved.
      if ($field_name) {
        $query->condition('f.field_name', $field_name);
        $query->addField('fc', 'entity_type', 'parent_type');
        $query->addField('fc', 'entity_id', 'parent_id');
        $query->innerJoin('field_revision_' . $field_name, 'fc', 'fc.' . $field_name . '_value = f.item_id and fc.' . $field_name . '_revision_id = f.revision_id');
      }
    }

    $query->innerJoin('field_collection_item_revision', 'fr', static::JOIN);

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $source_field_name = $row->getSourceProperty('field_name');

    // Get Field API field values.
    $field_names = array_keys($this->getFields('field_collection_item', $source_field_name));
    $item_id = $row->getSourceProperty('item_id');
    $revision_id = $row->getSourceProperty('revision_id');

    foreach ($field_names as $field_name) {
      $value = $this->getFieldValues('field_collection_item', $field_name, $item_id, $revision_id);
      $row->setSourceProperty($field_name, $value);
    }

    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'item_id' => $this->t('The field_collection_item id'),
      'revision_id' => $this->t('The field_collection_item revision id'),
      'bundle' => $this->t('The field_collection bundle'),
      'field_name' => $this->t('The field_collection field_name'),
      'parent_type' => $this->t('The type of the parent entity'),
      'parent_id' => $this->t('The identifier of the parent entity'),
    ];

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'item_id' => [
        'type' => 'integer',
        'alias' => 'f',
      ],
    ];
  }

}
