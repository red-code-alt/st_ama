<?php

declare(strict_types = 1);

namespace Drupal\Tests\acquia_migrate\Traits;

use Drupal\Core\Database\Database;
use Drupal\Core\Site\Settings;
use Drupal\Core\Test\FunctionalTestSetupTrait;
use Drupal\Core\Test\TestDatabase;
use org\bovigo\vfs\vfsStream;

/**
 * Trait for setting up an AMA test environment which uses the file system.
 */
trait AmaKernelTestSetupTrait {

  use FunctionalTestSetupTrait;

  /**
   * Path to the files folder of the source Drupal instance.
   *
   * @return string
   *   Path to the files folder of the source Drupal instance.
   */
  protected function fileSourceBasePath(): string {
    return DRUPAL_ROOT . '/core/modules/migrate_drupal_ui/tests/src/Functional/files';
  }

  /**
   * Loads a database fixture into the source database connection.
   *
   * @param string $fixture_file_path
   *   Path to the dump file.
   * @param bool $in_joinable_source
   *   Whether the given fixture should be imported into a joinable source
   *   database or not. For BC, defaults to TRUE.
   */
  protected function loadFixture($fixture_file_path, bool $in_joinable_source = TRUE): void {
    $this->assertTrue(method_exists(get_parent_class($this), 'loadFixture'));

    if (!$in_joinable_source) {
      $this->enableModules(['test_database_drivers']);
      $migration_connection = Database::getConnectionInfo('migrate');
      $source_connection = Database::getConnection('default', 'migrate');
      Database::removeConnection('migrate');

      // This will make our connections unjoinable.
      $source_connection_driver = $source_connection->getConnectionOptions()['driver'];
      $class = 'Drupal\test_database_drivers\Driver\\' . $source_connection_driver;
      $migration_connection['default']['namespace'] = $class;

      Database::addConnectionInfo('migrate', 'default', $migration_connection['default']);

      $this->sourceDatabase = Database::getConnection('default', 'migrate');
    }

    parent::loadFixture($fixture_file_path);
  }

  /**
   * {@inheritdoc}
   *
   * Instead of relying on virtual file system, we need real "physical" files in
   * order being able to ask the actual kernel to handle our requests.
   *
   * @see \Drupal\Tests\acquia_migrate\Traits\AmaHttpApiTrait::requestAndHandle()
   */
  protected function setUpFilesystem(): void {
    $this->assertTrue(method_exists(get_parent_class($this), 'setUpFilesystem'));
    $test_db = new TestDatabase($this->databasePrefix);
    $test_site_path = $test_db->getTestSitePath();

    $this->vfsRoot = vfsStream::setup('root');
    $this->vfsRoot->addChild(vfsStream::newDirectory($test_site_path));
    $this->siteDirectory = $test_site_path;

    mkdir($this->siteDirectory . '/tmp', 0775, TRUE);
    mkdir($this->siteDirectory . '/files/config/sync', 0775, TRUE);

    $this->publicFilesDirectory = $this->siteDirectory . '/files';
    $this->privateFilesDirectory = $this->siteDirectory . '/files/config/sync';
    $this->tempFilesDirectory = $this->siteDirectory . '/tmp';
    $this->originalSite = '';
    $this->prepareSettings();

    $this->writeSettings([
      'migrate_source_base_path' => (object) [
        'value' => $this->fileSourceBasePath(),
        'required' => TRUE,
      ],
      'settings' => [
        'hash_salt' => (object) [
          'value' => $this->databasePrefix,
          'required' => TRUE,
        ],
      ],
    ]);
    Settings::initialize(DRUPAL_ROOT, $this->siteDirectory, $this->classLoader);
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    // Delete test site directory.
    \Drupal::service('file_system')->deleteRecursive($this->siteDirectory, [$this, 'filePreDeleteCallback']);

    $this->cleanupCustomMigrationConnection();

    parent::tearDown();
  }

  /**
   * Removes the custom databases used for testing.
   */
  protected function cleanupCustomMigrationConnection(): void {
    // Remove all prefixed tables.
    $original_connection_info = Database::getConnectionInfo('simpletest_original_migrate');
    $original_prefix = $original_connection_info['default']['prefix'] ?? NULL;
    $test_connection_info = Database::getConnectionInfo('migrate');
    $test_prefix = $test_connection_info['default']['prefix'] ?? NULL;
    if ($original_prefix !== $test_prefix) {
      $this->emptyConnection('default', 'migrate');
    }
  }

  /**
   * Empties the given database connection.
   *
   * @param string $target
   *   The target of the connection.
   * @param string $key
   *   The connection key, like 'migrate'.
   */
  protected function emptyConnection(string $target, string $key): void {
    $connection = Database::getConnection($target, $key);
    $tables = $connection->schema()->findTables('%');
    foreach ($tables as $table_key => $table) {
      if ($connection->schema()->dropTable($table)) {
        unset($tables[$table_key]);
      }
    }
  }

  /**
   * Ensures test files are deletable.
   *
   * @param string $path
   *   The file path.
   *
   * @see \Drupal\Tests\BrowserTestBase::filePreDeleteCallback()
   */
  public static function filePreDeleteCallback($path): void {
    // When the webserver runs with the same system user as phpunit, we can
    // make read-only files writable again. If not, chmod will fail while the
    // file deletion still works if file permissions have been configured
    // correctly. Thus, we ignore any problems while running chmod.
    @chmod($path, 0700);
  }

}
