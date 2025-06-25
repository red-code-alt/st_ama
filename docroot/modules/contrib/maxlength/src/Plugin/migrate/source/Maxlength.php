<?php

namespace Drupal\maxlength\Plugin\migrate\source;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Maxlength migrate source plugin.
 *
 * @MigrateSource(
 *   id = "maxlength_title_settings",
 *   source_module = "maxlength"
 * )
 */
class Maxlength extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'name' => 'Name',
      'value' => 'Value',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'name' => [
        'type' => 'string',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('variable', 'v')
      ->fields('v', ['name', 'value'])
      ->condition('name', 'maxlength_js_label_%', 'LIKE');
    if (!empty($this->configuration['node_type'])) {
      $query->condition('name', 'maxlength_js_label_' . $this->configuration['node_type']);
    }
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $bundle = substr($row->getSourceProperty('name'), 19);
    $row->setSourceProperty('mx_length', $this->variableGet('maxlength_js_' . $bundle, ''));
    $row->setSourceProperty('mx_label', $row->getSourceProperty('value'));
    $row->setSourceProperty('mx_bundle', $bundle);
    return parent::prepareRow($row);
  }

}
