<?php

namespace Drupal\Tests\acquia_migrate;

use Drupal\Tests\acquia_migrate\Traits\MigrateDatabaseFixtureTrait;
use Drupal\TestSite\TestSetupInterface;

/**
 * Nightwatch setup file.
 *
 * This class largely must replicate FunctionalTestSetupTrait and HttpApiTest
 * in order to create and configured the test fixture database and its settings.
 *
 * @internal
 */
final class NightwatchTestSetupFile implements TestSetupInterface {

  use MigrateDatabaseFixtureTrait;

  /**
   * The test ID.
   *
   * @var string
   */
  protected $testId;

  /**
   * The site root path.
   *
   * @var string
   */
  protected $root;

  /**
   * The site directory path.
   *
   * @var string
   */
  protected $siteDirectory;

  /**
   * The public files directory path.
   *
   * @var string
   */
  protected $publicFilesDirectory;

  /**
   * {@inheritdoc}
   */
  public function setup() {
    \Drupal::service('module_installer')->install([
      'field',
      'user',
      'node',
      'file',
      'acquia_migrate',
      // Without these, we won't be able to import articles and reach the
      // minimum import ratio to consider dependencies to have been sufficiently
      // met.
      // @see \Drupal\acquia_migrate\Migration::MINIMUM_IMPORT_RATIO
      'link',
      'options',
      'telephone',
      'datetime',
      'taxonomy',
      // Note: we intentionally do not install the `image` module to have >0
      // error messages for the 'Shared structure for content items' migration.
      // We use this to test the filtering functionality.
    ]);

    $this->siteDirectory = \Drupal::service('site.path');
    $this->root = \Drupal::service('app.root');
    $this->testId = basename($this->siteDirectory);
    $this->publicFilesDirectory = "{$this->siteDirectory}/files";

    $this->setupMigrationConnection();
  }

  /**
   * Copied verbatim from FunctionalTestSetupTrait::writeSettings().
   *
   * @see \Drupal\Core\Test\FunctionalTestSetupTrait::writeSettings()
   */
  protected function writeSettings(array $settings) {
    include_once DRUPAL_ROOT . '/core/includes/install.inc';
    $filename = $this->siteDirectory . '/settings.php';
    // system_requirements() removes write permissions from settings.php
    // whenever it is invoked.
    // Not using File API; a potential error must trigger a PHP warning.
    chmod($filename, 0666);
    drupal_rewrite_settings($settings, $filename);
  }

}
