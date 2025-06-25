<?php

namespace Drupal\paragraphs_migration\Plugin\migrate\source\d7;

use Drupal\migrate\Row;

/**
 * Multifield Type source plugin.
 *
 * @MigrateSource(
 *   id = "pm_multifield_type",
 *   source_module = "multifield"
 * )
 */
class MultifieldType extends MultifieldTypeSqlSourceBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $field_name = $this->configuration['field_name'] ?? NULL;
    $query = parent::query();
    $query->condition('fc.type', 'multifield');
    if ($field_name) {
      $query->condition('fc.field_name', $field_name);
    }
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  protected function initializeIterator() {
    $source_records = [];
    foreach (parent::initializeIterator() as $item) {
      if (!empty($source_records[$item['field_name']])) {
        continue;
      }
      $source_records[$item['field_name']] = $item;
    }
    return new \ArrayIterator($source_records);
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row, $keep = TRUE) {
    // Add label and description.
    foreach ($this->getMetadata($row->getSourceProperty('field_name')) as $source_property => $source_property_value) {
      $row->setSourceProperty($source_property, $source_property_value);
    }

    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return parent::fields() + [
      'label' => $this->t('The label of the multifield.'),
      'description' => $this->t('The description of the multifield.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'field_name' => [
        'type' => 'string',
        'alias' => 'fc',
      ],
    ];
  }

  /**
   * Returns a label and a description for the actual multifield.
   *
   * @param string $field_name
   *   The field's machine name.
   *
   * @return string[]
   *   An array with label (at label key) and description (at description key).
   */
  protected function getMetadata($field_name) :array {
    $field_instance_data = $this->select('field_config_instance', 'fci')
      ->fields('fci', ['data'])
      ->condition('fci.field_name', $field_name)
      ->execute()
      ->fetchCol();
    foreach ($field_instance_data as $data_serialized) {
      $data = unserialize($data_serialized);
      $labels[] = $data['label'];
      $descriptions[] = $data['description'];
    }

    if (empty($non_empty_descriptions = array_filter($descriptions))) {
      // Every single description we discovered is empty – lets return the first
      // label.
      return [
        'label' => $labels[0],
        'description' => $descriptions[0],
      ];
    }

    // Return the first label - description pair where the description is not
    // empty.
    return [
      'label' => $labels[key($non_empty_descriptions)],
      'description' => $descriptions[key($non_empty_descriptions)],
    ];
  }

}
