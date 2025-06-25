<?php

namespace Drupal\Tests\pathauto\Kernel\Plugin\migrate\source;

use Drupal\Core\Database\Database;
use Drupal\migrate\Plugin\MigrateDestinationInterface;
use Drupal\migrate\Row;
use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Base class for testing pathauto source plugins with native databases.
 *
 * Most of the methods are copied from MigrateTestBase and are slightly
 * modified.
 *
 * @see \Drupal\Tests\migrate\Kernel\MigrateTestBase
 */
abstract class PathautoSourceTestBase extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   *
   * @see https://www.drupal.org/node/2909426
   * @todo This should be changed to "protected" after Drupal core 8.x security
   *   support ends.
   */
  public static $modules = [
    'ctools',
    'path',
    'path_alias',
    'pathauto',
    'system',
    'token',
    'migrate_drupal',
  ];

  /**
   * The source database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $sourceDatabase;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $destination_plugin = $this->prophesize(MigrateDestinationInterface::class);
    $destination_plugin->getPluginId()
      ->willReturn($this->randomMachineName(16));
    $this->migration->getDestinationPlugin()->willReturn(
      $destination_plugin->reveal()
    );

    $this->createMigrationConnection();
    $this->sourceDatabase = Database::getConnection('default', 'migrate');
  }

  /**
   * Changes the database connection to the prefixed one.
   *
   * @see \Drupal\Tests\migrate\Kernel\MigrateTestBase::createMigrationConnection()
   *
   * @todo Refactor when core doesn't use global.
   *   https://www.drupal.org/node/2552791
   */
  private function createMigrationConnection() {
    // If the backup already exists, something went terribly wrong.
    // This case is possible, because database connection info is a static
    // global state construct on the Database class, which at least persists
    // for all test methods executed in one PHP process.
    if (Database::getConnectionInfo('simpletest_original_migrate')) {
      throw new \RuntimeException("Bad Database connection state: 'simpletest_original_migrate' connection key already exists. Broken test?");
    }

    // Clone the current connection and replace the current prefix.
    $connection_info = Database::getConnectionInfo('migrate');
    if ($connection_info) {
      Database::renameConnection('migrate', 'simpletest_original_migrate');
    }
    $connection_info = Database::getConnectionInfo('default');
    foreach ($connection_info as $target => $value) {
      $prefix = is_array($value['prefix']) ? $value['prefix']['default'] : $value['prefix'];
      // Simpletest uses 7 character prefixes at most so this can't cause
      // collisions.
      $connection_info[$target]['prefix']['default'] = $prefix . '0';

      // Add the original simpletest prefix so SQLite can attach its database.
      // @see \Drupal\Core\Database\Driver\sqlite\Connection::init()
      $connection_info[$target]['prefix'][$value['prefix']['default']] = $value['prefix']['default'];
    }
    Database::addConnectionInfo('migrate', 'default', $connection_info['default']);
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\Tests\migrate\Kernel\MigrateTestBase::cleanupMigrateConnection()
   */
  protected function tearDown() {
    $this->cleanupMigrateConnection();
    parent::tearDown();
  }

  /**
   * Cleans up the test migrate connection.
   *
   * @see \Drupal\Tests\migrate\Kernel\MigrateTestBase::cleanupMigrateConnection()
   *
   * @todo Refactor when core doesn't use global.
   *   https://www.drupal.org/node/2552791
   */
  private function cleanupMigrateConnection() {
    Database::removeConnection('migrate');
    $original_connection_info = Database::getConnectionInfo('simpletest_original_migrate');
    if ($original_connection_info) {
      Database::renameConnection('simpletest_original_migrate', 'migrate');
    }
  }

  /**
   * Loads a database fixture into the source database connection.
   *
   * @param array $database
   *   The source data, keyed by table name. Each table is an array containing
   *   the rows in that table.
   */
  protected function importSourceDatabase(array $database): void {
    // Create the tables and fill them with data.
    foreach ($database as $table => $rows) {
      // Use the biggest row to build the table schema.
      $counts = array_map('count', $rows);
      asort($counts);
      end($counts);
      $pilot = $rows[key($counts)];
      $schema = array_map(function ($value) {
        $type = is_numeric($value) && !is_float($value + 0)
          ? 'int'
          : 'text';
        return ['type' => $type];
      }, $pilot);

      $this->sourceDatabase->schema()
        ->createTable($table, [
          'fields' => $schema,
        ]);

      $fields = array_keys($pilot);
      $insert = $this->sourceDatabase->insert($table)->fields($fields);
      array_walk($rows, [$insert, 'values']);
      $insert->execute();
    }
  }

  /**
   * {@inheritdoc}
   *
   * @dataProvider providerSource
   */
  public function testSource(array $source_data, array $expected_data, $expected_count = NULL, array $configuration = [], $high_water = NULL) {
    $this->importSourceDatabase($source_data);
    $plugin = $this->getPlugin($configuration);
    $clone_plugin = clone $plugin;

    // All source plugins must define IDs.
    $this->assertNotEmpty($plugin->getIds());

    // If there is a high water mark, set it in the high water storage.
    if (isset($high_water)) {
      $this->container
        ->get('keyvalue')
        ->get('migrate:high_water')
        ->set($this->migration->reveal()->id(), $high_water);
    }

    if (is_null($expected_count)) {
      $expected_count = count($expected_data);
    }
    // If an expected count was given, assert it only if the plugin is
    // countable.
    if (is_numeric($expected_count)) {
      assert($plugin instanceof \Countable);
      $this->assertCount($expected_count, $plugin);
    }

    $i = 0;
    $actual_source_data = [];
    foreach ($plugin as $row) {
      assert($row instanceof Row);
      $actual_source_data[$i++] = $row->getSource();
    }

    $this->assertEquals($expected_data, $actual_source_data, "Source values are different then expected.");

    // False positives occur if the foreach is not entered. So, confirm the
    // foreach loop was entered if the expected count is greater than 0.
    if ($expected_count > 0) {
      $this->assertGreaterThan(0, $i);

      // Test that we can skip all rows.
      \Drupal::state()->set('migrate_skip_all_rows_test_migrate_prepare_row', TRUE);
      foreach ($clone_plugin as $row) {
        $this->fail('Row not skipped');
      }
    }
  }

}
