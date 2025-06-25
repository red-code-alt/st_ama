<?php

namespace Drupal\pathauto\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Fallback plugin implementation for "pathauto_forum_vocabulary".
 *
 * If taxonomy module is enabled, this implementation is replaced with
 * \Drupal\taxonomy\Plugin\migrate\process\ForumVocabulary.
 *
 * @MigrateProcessPlugin(
 *   id = "pathauto_forum_vocabulary"
 * )
 */
class PathautoForumVocabularyFallback extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    return $value;
  }

}
