<?php

namespace Drupal\Tests\acquia_migrate\Traits;

use Drupal\Core\Database\Database;

/**
 * Provides properties and methods for testing migrations using a test fixture.
 *
 * All properties and methods are copied verbatim from
 * \Drupal\Tests\migrate_drupal_ui\Functional\MigrateUpgradeTestBase with the
 * exception of the database key. That has been changed from `migrate_drupal_ui`
 * to `migrate_test`.
 *
 * Users of this trait MUST include the following code during the setUp()
 * method's execution phase:
 *
 * @code
 *   $this->createMigrationConnection();
 *   $this->sourceDatabase = Database::getConnection('default', 'migrate_test');
 *   \Drupal::state()->set('acquia_migrate_test_database', [
 *     'target' => $this->sourceDatabase->getTarget(),
 *     'key' => $this->sourceDatabase->getKey(),
 *   ]);
 *   \Drupal::state()->set('migrate.fallback_state_key', 'acquia_migrate_test_database');
 * @endcode
 *
 * For the *tested* site to have access to the migrate database, a connection
 * *MUST* be established in the tested site. See
 * \Drupal\Tests\acquia_migrate\Functional\HttpApiTest::writeSettings() for an
 * example of how to accomplish that.
 *
 * Users of this trait MUST implement tearDown() and include the following code:
 *
 * @code
 *   Database::removeConnection('migrate_test')
 * @endcode
 *
 * To load a database fixture, particularly the Drupal 7 fixture provided by
 * Drupal core, use:
 *
 * @code
 *   $this->loadFixture(drupal_get_path('module', 'migrate_drupal') . '/tests/fixtures/drupal7.php');
 * @endcode
 *
 * @internal
 */
trait MigrateDatabaseFixtureTrait {

  /**
   * The source database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $sourceDatabase;

  /**
   * Returns the path to the fixture file.
   */
  protected function getFixtureFilePath() {
    return drupal_get_path('module', 'migrate_drupal') . '/tests/fixtures/drupal7.php';
  }

  /**
   * Loads a database fixture into the source database connection.
   *
   * @param string $path
   *   Path to the dump file.
   */
  protected function loadFixture($path) {
    $default_db = Database::getConnection()->getKey();
    Database::setActiveConnection($this->sourceDatabase->getKey());

    if (substr($path, -3) == '.gz') {
      $path = 'compress.zlib://' . $path;
    }
    // @codingStandardsIgnoreStart
    require $path;
    // @codingStandardsIgnoreEnd

    Database::setActiveConnection($default_db);
  }

  /**
   * Changes the database connection to the prefixed one.
   *
   * @todo Remove when we don't use global. https://www.drupal.org/node/2552791
   */
  protected function createMigrationConnection() {
    $connection_info = Database::getConnectionInfo('default')['default'];
    if ($connection_info['driver'] === 'sqlite') {
      // Create database file in the test site's public file directory so that
      // \Drupal\simpletest\TestBase::restoreEnvironment() will delete this once
      // the test is complete.
      $file = $this->publicFilesDirectory . '/' . $this->testId . '-migrate.db.sqlite';
      touch($file);
      $connection_info['database'] = $file;
      $connection_info['prefix'] = '';
      $connection_info['init_commands'] = [
        'wal' => "PRAGMA journal_mode=OFF",
      ];
    }
    else {
      $prefix = is_array($connection_info['prefix']) ? $connection_info['prefix']['default'] : $connection_info['prefix'];
      // Simpletest uses fixed length prefixes. Create a new prefix for the
      // source database. Adding to the end of the prefix ensures that
      // \Drupal\simpletest\TestBase::restoreEnvironment() will remove the
      // additional tables.
      $connection_info['prefix'] = $prefix . '0';
    }

    Database::addConnectionInfo('migrate_test', 'default', $connection_info);
  }

  /**
   * Set up migration connection.
   *
   * @param string|null $fixture_file_path
   *   Path of the database fixture to use.
   */
  protected function setupMigrationConnection(string $fixture_file_path = NULL): void {
    $this->ensureSitesDirectoryExists();

    // Create test database connection.
    $this->createMigrationConnection();

    $this->sourceDatabase = Database::getConnection('default', 'migrate_test');
    $this->loadFixture($fixture_file_path ?? $this->getFixtureFilePath());
    // @see \Drupal\migrate\Plugin\migrate\source\SqlBase::getDatabase()
    \Drupal::state()->set('acquia_migrate_test_database', [
      'target' => $this->sourceDatabase->getTarget(),
      'key' => $this->sourceDatabase->getKey(),
    ]);
    \Drupal::state()->set('migrate.fallback_state_key', 'acquia_migrate_test_database');

    // Get its credentials.
    $migrate_database_info = Database::getConnectionInfo('migrate_test');
    // Add them to the settings array so that they will be appended to the
    // settings.php file belonging to the *tested* site.
    $settings['databases']['migrate_test']['default'] = (object) [
      'value'    => $migrate_database_info['default'],
      'required' => TRUE,
    ];

    $settings['migrate_source_base_path'] = (object) [
      'value'    => $this->root . DIRECTORY_SEPARATOR . 'd7_files',
      'required' => TRUE,
    ];

    $this->writeSettings($settings);
  }

  /**
   * Ensure that the sites directory exists.
   */
  protected function ensureSitesDirectoryExists() {
    // Creating a migrate database sometimes needs to create a sqlite file,
    // ensure that the directory in which it will be created exists.
    $files_directory = $this->root . DIRECTORY_SEPARATOR . $this->publicFilesDirectory;
    if (!file_exists($files_directory)) {
      mkdir($files_directory);
    }

    $source_files_directory = $this->root . DIRECTORY_SEPARATOR . 'd7_files';
    if (!file_exists($source_files_directory)) {
      mkdir($source_files_directory);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown() {
    Database::removeConnection('migrate_test');
    parent::tearDown();
  }

}
