<?php

namespace Drupal\paragraphs_migration\Plugin\migrate\source\d7;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\d7\FieldableEntity as CoreFieldableEntity;
use Drupal\paragraphs_migration\Utility\MultifieldMigration;

/**
 * Multifield Item source plugin.
 *
 * Available configuration keys:
 * - field_name: (optional) If supplied, this will only return multifields
 *   of that particular type.
 *
 * @MigrateSource(
 *   id = "pm_multifield",
 *   source_module = "multifield",
 * )
 */
class Multifield extends CoreFieldableEntity {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $field_names = !empty($this->configuration['field_name'])
      ? (array) $this->configuration['field_name']
      : MultifieldMigration::getMultifieldFields();
    $entity_type = !empty($this->configuration['entity_type'])
      ? $this->configuration['entity_type']
      : NULL;
    $bundle = !empty($this->configuration['bundle'])
      ? $this->configuration['bundle']
      : NULL;

    $query = NULL;
    foreach ($field_names as $field_name) {
      $subquery = $this->select($this->getFieldDataTableName($field_name), 't')
        // For first, only get the crucial data (we want to reduce the SQL
        // packet size).
        ->fields('t', [
          'entity_type',
          'entity_id',
          'revision_id',
          'delta',
          'language',
        ])
        ->condition('t.deleted', 0)
        ->orderBy('t.revision_id');
      $subquery->leftJoin('field_config', 'fc', 'fc.field_name = :field_name', [
        ':field_name' => $field_name,
      ]);
      if ($entity_type) {
        $subquery->condition('t.entity_type', $entity_type);
      }
      if ($bundle) {
        $subquery->condition('t.bundle', $bundle);
      }

      if (!$query instanceof SelectInterface) {
        $query = $subquery;
        continue;
      }

      $query->union($subquery);
    }

    // If the host entity is translated, then we want to make sure that the
    // default translation's revision gets migrated first.
    if ($this->getDatabase()->schema()->tableExists('entity_translation')) {
      $query->leftJoin('entity_translation', 'et', 't.entity_type = et.entity_type AND t.entity_id = et.entity_id AND t.revision_id = et.revision_id AND t.language = et.language');
      $query->addField('et', 'source', 'source_language');
      $query->orderBy('et.source');
    }

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    [
      'entity_type' => $host_entity_type,
      'entity_id' => $host_entity_id,
      'revision_id' => $host_entity_revision_id,
      'field_name' => $field_name,
      'delta' => $field_delta,
      'language' => $field_language,
    ] = $row->getSource();

    if ($field_language === LanguageInterface::LANGCODE_NOT_SPECIFIED) {
      $default_langcode = ((array) $this->variableGet('language_default', ['language' => 'en']))['language'];
      $row->setSourceProperty('host_language', $default_langcode);
    }

    foreach ($this->getSubfieldsValues($host_entity_type, $host_entity_id, $host_entity_revision_id, $field_name, $field_delta, $field_language) as $source_property => $source_value) {
      $row->setSourceProperty($source_property, $source_value);
    }

    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'entity_type' => $this->t('The entity type.'),
      'entity_id' => $this->t('The entity identifier.'),
      'field_name' => $this->t('The host field.'),
      'delta' => $this->t('The delta of the current multifield.'),
      'revision_id' => $this->t('The entity revision identifier.'),
      'language' => $this->t('The language code.'),
    ];

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'entity_type' => [
        'type' => 'string',
        'alias' => 't',
      ],
      'entity_id' => [
        'type' => 'integer',
        'alias' => 't',
      ],
      'field_name' => [
        'type' => 'string',
        'alias' => 'fc',
      ],
      'delta' => [
        'type' => 'integer',
        'alias' => 't',
      ],
      'revision_id' => [
        'type' => 'integer',
        'alias' => 't',
      ],
      'language' => [
        'type' => 'string',
        'alias' => 't',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function checkRequirements() {
    parent::checkRequirements();
    if (empty(MultifieldMigration::getMultifieldFields())) {
      throw new RequirementsException(
        sprintf(
          "No multifield fields were found in the source database."
        )
      );
    }
  }

  /**
   * Returns the table name to use for getting the source values.
   *
   * @param string $field_name
   *   The multifield's field name.
   *
   * @return string
   *   The table name to use for getting the source values.
   */
  protected function getFieldDataTableName($field_name) {
    return empty($this->configuration['exclude_revisions'])
      ? "field_revision_{$field_name}"
      : "field_data_{$field_name}";
  }

  /**
   * Returns the specified multifield's subfields' configuration.
   *
   * @param string $field_name
   *   The multifield's field name.
   *
   * @return array
   *   An associative array with the subfields configuration.
   */
  protected function getSubfieldConfigurations($field_name) {
    $query = $this->select('field_config_instance', 'fci')
      ->fields('fci', ['field_id', 'field_name'])
      ->fields('fc', ['data'])
      ->condition('fci.entity_type', 'multifield')
      ->condition('fci.bundle', $field_name)
      ->condition('fci.deleted', 0);
    $query->innerJoin('field_config', 'fc', 'fci.field_id = fc.id');
    $fields = $query->execute()->fetchAllAssoc('field_name');

    foreach ($fields as $field_name => $field_data) {
      $fields[$field_name]['data'] = unserialize($field_data['data']);
    }

    return $fields;
  }

  /**
   * Returns values of the specified subfield record.
   *
   * @param string $host_entity_type
   *   The entity type.
   * @param int $host_entity_id
   *   The entity ID.
   * @param int $host_entity_revision_id
   *   The entity revision ID.
   * @param string $field_name
   *   The multifield's field name.
   * @param $field_delta
   * @param string $field_language
   *   The field language.
   *
   * @return array
   *   An associative array with subfield values.
   */
  protected function getSubfieldsValues($host_entity_type, $host_entity_id, $host_entity_revision_id, $field_name, $field_delta, $field_language) {
    $field_data = $this->getFieldValues($host_entity_type, $field_name, $host_entity_id, $host_entity_revision_id, $field_language)[$field_delta] ?? NULL;

    // Subfield values returned by FieldableEntity::getFieldValues are
    // structured as <subfield_name>_<subfield_property>.
    $subfield_values = [];
    foreach ($this->getSubfieldConfigurations($field_name) as $subfield_name => $subfield_config) {
      $subfield_storage = $subfield_config['data']['storage'] ?? NULL;
      $subfield_value = [];

      if (
        $subfield_storage &&
        $subfield_storage['type'] === 'field_sql_storage' &&
        is_array($subfield_storage_info = $subfield_storage['details']['sql']['FIELD_LOAD_CURRENT']["field_data_{$subfield_name}"] ?? NULL)
      ) {
        foreach (array_keys($subfield_storage_info) as $subfield_prop) {
          $subfield_value[$subfield_prop] = $field_data["{$subfield_name}_{$subfield_prop}"] ?? NULL;
        }
      }
      // Trying to fall back to the "value" property – if any.
      elseif (array_key_exists("{$subfield_name}_value", $field_data)) {
        $subfield_value['value'] = $field_data["{$subfield_name}_value"] ?? NULL;
      }

      if (!empty(array_filter($subfield_value))) {
        // The explicit "0" key represents the (sub)field value delta.
        $subfield_values[$subfield_name] = [
          0 => $subfield_value,
        ];
      }
    }

    return $subfield_values;
  }

}
