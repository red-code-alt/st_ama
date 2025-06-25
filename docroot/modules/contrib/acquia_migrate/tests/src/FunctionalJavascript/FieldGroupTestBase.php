<?php

namespace Drupal\Tests\acquia_migrate\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\acquia_migrate\Traits\MigrateDatabaseFixtureTrait;
use Drupal\Tests\acquia_migrate\Traits\MigrateJsUiTrait;
use Drupal\Tests\field_group_migrate\Traits\FieldGroupMigrationAssertionsTrait;

/**
 * Conditional base class for Field Group migration integration test.
 *
 * Field Group Migrate cannot be our test dependency, but we still want to have
 * a test that can be run at least locally.
 */
if (trait_exists(FieldGroupMigrationAssertionsTrait::class)) {
  abstract class FieldGroupTestBase extends WebDriverTestBase {

    use MigrateDatabaseFixtureTrait;
    use MigrateJsUiTrait;
    use FieldGroupMigrationAssertionsTrait;

  }
}
else {
  abstract class FieldGroupTestBase extends WebDriverTestBase {}
}
