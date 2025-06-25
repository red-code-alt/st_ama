<?php

namespace Drupal\Tests\acquia_migrate\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\acquia_migrate\Traits\MigrateDatabaseFixtureTrait;
use Drupal\Tests\acquia_migrate\Traits\MigrateJsUiTrait;
use Drupal\Tests\webform_migrate\Traits\WebformMigrateAssertionsTrait;

/**
 * Conditional base class for Webform migration integration test.
 *
 * Webform: Migrate cannot be our test dependency, but we still want to have a
 * test that can be run at least locally.
 */
if (trait_exists(WebformMigrateAssertionsTrait::class)) {
  abstract class WebformTestBase extends WebDriverTestBase {

    use MigrateDatabaseFixtureTrait;
    use MigrateJsUiTrait;
    use WebformMigrateAssertionsTrait;

  }
}
else {
  abstract class WebformTestBase extends WebDriverTestBase {}
}
