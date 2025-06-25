<?php

namespace Drupal\Tests\acquia_migrate\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\acquia_migrate\Traits\MigrateDatabaseFixtureTrait;
use Drupal\Tests\acquia_migrate\Traits\MigrateJsUiTrait;
use Drupal\Tests\media_migration\Traits\MediaMigrationAssertionsForNonMediaSourceTrait;
use Drupal\Tests\media_migration\Traits\MediaMigrationTestTrait;

/**
 * Conditional base class for the media migration integration test.
 *
 * Media Migration cannot be our test dependency, but we still want to have a
 * test that can be run at least locally.
 */
if (trait_exists(MediaMigrationTestTrait::class)) {
  abstract class MediaMigrationPlainTestBase extends WebDriverTestBase {

    use MigrateJsUiTrait, MediaMigrationTestTrait, MigrateDatabaseFixtureTrait, MediaMigrationAssertionsForNonMediaSourceTrait {
      MediaMigrationTestTrait::getFixtureFilePath insteadof MigrateDatabaseFixtureTrait;
    }

  }
}
else {
  abstract class MediaMigrationPlainTestBase extends WebDriverTestBase {}
}
