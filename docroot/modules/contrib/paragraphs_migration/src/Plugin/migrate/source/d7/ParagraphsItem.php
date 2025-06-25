<?php

namespace Drupal\paragraphs_migration\Plugin\migrate\source\d7;

use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\migrate\Row;

/**
 * Paragraphs Item source plugin.
 *
 * Available configuration keys:
 * - bundle: (optional) If supplied, this will only return paragraphs
 *   of that particular type.
 *
 * @MigrateSource(
 *   id = "d7_pm_paragraphs_item",
 *   source_module = "paragraphs",
 * )
 */
class ParagraphsItem extends FieldableEntity {

  /**
   * Join string for getting current revisions.
   *
   * @var string
   */
  const JOIN = "p.revision_id = pr.revision_id";

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
      'bundle' => '',
      'parent_type' => NULL,
      'parent_bundle' => NULL,
      'field_name' => NULL,
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
      'bundle' => $bundle,
    ] = $this->configuration;

    // Derived by parent entity type.
    if ($field_name && $parent_type) {
      $field_table = static::PARENT_FIELD_TABLE_PREFIX . $field_name;
      $query = $this->select($field_table, 'fd')
        ->fields('p', [
          'item_id',
          'bundle',
          'field_name',
          'archived',
        ])
        ->fields('pr', ['revision_id'])
        ->condition('fd.entity_type', $parent_type);
      if ($parent_bundle) {
        $query->condition('fd.bundle', $parent_bundle);
      }
      $query->addField('fd', 'entity_type', 'parent_type');
      $query->addField('fd', 'entity_id', 'parent_id');

      $query->join('paragraphs_item', 'p', "p.field_name = :field_name AND p.item_id = fd.{$field_name}_value AND p.revision_id = fd.{$field_name}_revision_id", [
        ':field_name' => $field_name,
      ]);
    }
    else {
      $query = $this->select('paragraphs_item', 'p')
        ->fields('p',
          [
            'item_id',
            'bundle',
            'field_name',
            'archived',
          ])
        ->fields('pr', ['revision_id']);
    }

    $query->join('paragraphs_item_revision', 'pr', static::JOIN);

    // The paragraph bundle may be set by a deriver to restrict the bundles
    // retrieved.
    if ($bundle) {
      $query->condition('p.bundle', $bundle);
    }

    $query->condition('p.archived', 1, '<>');

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    [
      'item_id' => $paragraph_id,
      'revision_id' => $paragraph_revision_id,
      'field_name' => $paragraph_parent_field_name,
      'bundle' => $bundle,
    ] = $row->getSource();

    if (!$paragraph_parent_field_name || !is_string($paragraph_parent_field_name)) {
      return FALSE;
    }

    // Get Field API field values.
    foreach (array_keys($this->getFields('paragraphs_item', $bundle)) as $field_name) {
      $row->setSourceProperty($field_name, $this->getFieldValues('paragraphs_item', $field_name, $paragraph_id, $paragraph_revision_id));
    }

    if (!empty($row->getSourceProperty('parent_type')) && !empty($row->getSourceProperty('parent_id'))) {
      return parent::prepareRow($row);
    }

    // We have to find the corresponding parent entity (which might be an
    // another paragraph). Active revision only.
    try {
      $parent_data_query = $this->getDatabase()->select(static::PARENT_FIELD_TABLE_PREFIX . $paragraph_parent_field_name, 'fd');
      $parent_data_query->addField('fd', 'entity_type', 'parent_type');
      $parent_data_query->addField('fd', 'entity_id', 'parent_id');
      $parent_data = $parent_data_query
        ->condition("fd.{$paragraph_parent_field_name}_value", $paragraph_id)
        ->condition("fd.{$paragraph_parent_field_name}_revision_id", $paragraph_revision_id)
        ->execute()->fetchAssoc();
    }
    catch (DatabaseExceptionWrapper $e) {
      // The paragraphs field data|revision table is missing, we cannot get
      // the parent entity identifiers. This is a corrupted database.
      // @todo Shouldn't we have to throw an exception instead?
      return FALSE;
    }

    if (!is_iterable($parent_data)) {
      // We cannot get the parent entity identifiers.
      return FALSE;
    }

    foreach ($parent_data as $property_name => $property_value) {
      $row->setSourceProperty($property_name, $property_value);
    }

    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'item_id' => $this->t('The paragraph_item id'),
      'revision_id' => $this->t('The paragraph_item revision id'),
      'bundle' => $this->t('The paragraph bundle'),
      'field_name' => $this->t('The paragraph field_name'),
      'parent_type' => $this->t('Parent entity type ID'),
      'parent_id' => $this->t('Parent entity ID'),
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
        'alias' => 'p',
      ],
    ];
  }

}
