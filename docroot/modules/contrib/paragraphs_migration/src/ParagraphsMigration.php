<?php

namespace Drupal\paragraphs_migration;

/**
 * Helper class for paragraphs entity migration derivation strategy.
 */
final class ParagraphsMigration {

  /**
   * Key of the paragraphs migration's raw (unfinalized) migration dependencies.
   *
   * @var string
   */
  const PARAGRAPHS_RAW_DEPENDENCIES = 'paragraphs_required_migration_unfinalized_dependencies';

}
