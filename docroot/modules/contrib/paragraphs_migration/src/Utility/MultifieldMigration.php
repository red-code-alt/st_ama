<?php

namespace Drupal\paragraphs_migration\Utility;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\migrate\Plugin\MigrationDeriverTrait;
use Drupal\migrate\Row;

/**
 * Utility class for multifield related migrations.
 */
final class MultifieldMigration {
  use MigrationDeriverTrait;
  /**
   * Field names of multifields in the migration source DB.
   *
   * @var string[]
   */
  protected static $multifields;

  /**
   * Returns the field names of multifields in the migration source DB.
   *
   * @return string[]
   */
  public static function getMultifieldFields() {
    if (!isset(self::$multifields)) {
      if (!\Drupal::moduleHandler()->moduleExists('migrate_drupal')) {
        return self::$multifields = [];
      }

      try {
        $multifield_type_source_plugin = static::getSourcePlugin('pm_multifield_type');
      }
      catch (PluginNotFoundException $e) {
        // If multifield type plugin requirements aren't met, then the plugin
        // manager throws a plugin not found exception.
        return self::$multifields = [];
      }

      self::$multifields = array_map(
        function ($mf_row) {
          assert($mf_row instanceof Row);
          return $mf_row->getSourceProperty('field_name');
        },
        iterator_to_array(
          $multifield_type_source_plugin,
          FALSE
        )
      );
    }

    return self::$multifields;
  }

  /**
   * @param \Drupal\Core\Database\Query\SelectInterface $query
   */
  public static function addCruicalMultifieldFieldProperties(SelectInterface $query) {
    if (empty(self::getMultifieldFields())) {
      return;
    }

    $tables = $query->getTables();

    // Table alias is not the same as the one used in.
    if (!isset($tables['t']) || count($tables) !== 1) {
      return;
    }

    if ($tables['t']['table'] instanceof SelectInterface) {
      return;
    }

    $table_name = $tables['t']['table'];
    $field_name = self::removePrefix(
      $table_name,
      ['field_data_', 'field_revision_']
    );

    // Target table is not a DB table which contains field values.
    if ($table_name === $field_name) {
      return;
    }

    // The field of the target table is not a multifield field.
    if (!in_array($field_name, self::getMultifieldFields())) {
      return;
    }

    foreach (['entity_type', 'entity_id', 'bundle', 'delta', 'revision_id',
      'language',
    ] as $property) {
      $query->addField(
        't',
        $property,
        "{$field_name}_$property"
      );
    }
  }

  /**
   * Removes the given prefix from a string if present.
   *
   * @param string $string
   *   The string to process.
   * @param string|string[] $prefix
   *   The prefix to remove, defaults to 'field_'.
   *
   * @return string
   *   A string without the first occurrence of the given prefix.
   */
  public static function removePrefix($string, $prefix = 'field_') {
    $patterns = array_map(function ($prefix) {
      return '/^' . preg_quote($prefix) . '/';
    }, (array) $prefix);
    return preg_replace($patterns, '', $string);
  }

}
