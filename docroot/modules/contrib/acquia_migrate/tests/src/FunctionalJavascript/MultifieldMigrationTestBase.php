<?php

namespace Drupal\Tests\acquia_migrate\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\acquia_migrate\Traits\MigrateDatabaseFixtureTrait;
use Drupal\Tests\acquia_migrate\Traits\MigrateJsUiTrait;
use Drupal\Tests\paragraphs_migration\Traits\MultifieldMigrationsTrait;

/**
 * Conditional base class for Multifield migration integration test.
 */
if (trait_exists(MultifieldMigrationsTrait::class)) {
  abstract class MultifieldMigrationTestBase extends WebDriverTestBase {

    use MigrateDatabaseFixtureTrait;
    use MigrateJsUiTrait;
    use MultifieldMigrationsTrait;

  }
}
else {
  abstract class MultifieldMigrationTestBase extends WebDriverTestBase {}
}
