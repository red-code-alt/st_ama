<?php

namespace Drupal\paragraphs_migration\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Configure field instance settings for paragraphs.
 *
 * @MigrateProcessPlugin(
 *   id = "pm_paragraphs_field_settings"
 * )
 */
class ParagraphsFieldSettings extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $source_type = $this->configuration['source_type'] ?? 'paragraphs';

    if ($row->getSourceProperty('type') == $source_type) {
      $value['target_type'] = 'paragraph';
    }
    return $value;
  }

}
