<?php

namespace Drupal\Tests\acquia_migrate\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\acquia_migrate\Traits\MigrateDatabaseFixtureTrait;
use Drupal\Tests\acquia_migrate\Traits\MigrateJsUiTrait;
use Drupal\Tests\bean_migrate\Traits\BeanMigrateAssertionsTrait;

/**
 * Conditional base class for Bean migration integration test.
 *
 * Bean Migrate cannot be our test dependency, but we still want to have a test
 * that can be run at least locally.
 */
if (trait_exists(BeanMigrateAssertionsTrait::class)) {
  abstract class BeanMigrationTestBase extends WebDriverTestBase {

    use MigrateDatabaseFixtureTrait;
    use MigrateJsUiTrait;
    use BeanMigrateAssertionsTrait;

  }
}
else {
  abstract class BeanMigrationTestBase extends WebDriverTestBase {}
}
