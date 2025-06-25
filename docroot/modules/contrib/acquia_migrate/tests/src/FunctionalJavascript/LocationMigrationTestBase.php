<?php

namespace Drupal\Tests\acquia_migrate\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\acquia_migrate\Traits\MigrateDatabaseFixtureTrait;
use Drupal\Tests\acquia_migrate\Traits\MigrateJsUiTrait;
use Drupal\Tests\location_migration\Traits\LocationMigrationAssertionsTrait;

/**
 * Conditional base class for location migration integration test.
 *
 * Location Migration shouldn't be our test dependency, but we still want to
 * have a test that can be run locally.
 */
if (trait_exists(LocationMigrationAssertionsTrait::class)) {
  abstract class LocationMigrationTestBase extends WebDriverTestBase {

    use MigrateJsUiTrait;
    use MigrateDatabaseFixtureTrait;
    use LocationMigrationAssertionsTrait;

  }
}
else {
  abstract class LocationMigrationTestBase extends WebDriverTestBase {}
}
