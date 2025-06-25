<?php

namespace Drupal\Tests\acquia_migrate\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\acquia_migrate\Traits\MigrateDatabaseFixtureTrait;
use Drupal\Tests\acquia_migrate\Traits\MigrateJsUiTrait;
use Drupal\Tests\paragraphs_migration\Traits\ParagraphsNodeMigrationAssertionsTrait;

/**
 * Conditional base class for Paragraphs migration integration test.
 *
 * Paragraphs cannot be our test dependency, but we still want to have a test
 * that can be run at least locally.
 */
if (trait_exists(ParagraphsNodeMigrationAssertionsTrait::class)) {
  abstract class ParagraphsMigrationTestBase extends WebDriverTestBase {

    use MigrateDatabaseFixtureTrait;
    use MigrateJsUiTrait;
    use ParagraphsNodeMigrationAssertionsTrait;

  }
}
else {
  abstract class ParagraphsMigrationTestBase extends WebDriverTestBase {}
}
