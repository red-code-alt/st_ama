<?php

namespace Drupal\Tests\acquia_migrate\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\acquia_migrate\Traits\MigrateDatabaseFixtureTrait;
use Drupal\Tests\acquia_migrate\Traits\MigrateJsUiTrait;
use Drupal\Tests\pathauto\Traits\PathautoMigrationAssertionsTrait;

/**
 * Conditional base class for pathauto migration integration test.
 *
 * Pathauto Migration shouldn't be our test dependency, but we still want to
 * have a test that can be run locally.
 */
if (trait_exists(PathautoMigrationAssertionsTrait::class)) {
  abstract class PathautoMigrationTestBase extends WebDriverTestBase {

    use MigrateJsUiTrait;
    use MigrateDatabaseFixtureTrait;
    use PathautoMigrationAssertionsTrait;

  }
}
else {
  abstract class PathautoMigrationTestBase extends WebDriverTestBase {}
}
