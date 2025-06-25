<?php

namespace Drupal\Tests\acquia_migrate\Functional;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Driver\pgsql\Connection as PostgreSqlConnection;
use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests the upgrade path for updating outdated rollbackable data tables.
 *
 * @group acquia_migrate
 * @group acquia_migrate__core
 */
class RollbackableTablesUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      implode(DIRECTORY_SEPARATOR, [
        DRUPAL_ROOT,
        'core',
        'modules',
        'system',
        'tests',
        'fixtures',
        'update',
        'drupal-9.0.0.bare.standard.php.gz',
      ]),
      implode(DIRECTORY_SEPARATOR, [
        __DIR__,
        '..',
        'fixtures',
        'update',
        'drupal-9.acquia_migrate-rollbackable-tables-9201.php',
      ]),
    ];
  }

  /**
   * Tests acquia_migrate_update_9202().
   */
  public function testAcquiaMigrateUpdate9202() {
    $connection = $this->container->get('database');
    $is_pgsql = $connection instanceof PostgreSqlConnection;
    assert($connection instanceof Connection);
    $module_installer = $this->container->get('module_installer');
    assert($module_installer instanceof ModuleInstallerInterface);
    $module_installer->install(['acquia_migrate']);
    $this->resetAll();

    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('acquia_migrate'));
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('migmag_rollbackable_replace'));
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('migmag_rollbackable'));
    $this->assertTrue($connection->schema()->tableExists('migmag_rollbackable_new_targets'));
    $this->assertTrue($connection->schema()->tableExists('migmag_rollbackable_data'));

    // Disable migmag modules.
    $migmag_modules = [
      'migmag_rollbackable_replace',
      'migmag_rollbackable',
    ];
    foreach ($migmag_modules as $migmag_module) {
      $module_installer->uninstall([$migmag_module], FALSE);
    }
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('acquia_migrate'));
    $this->assertFalse(\Drupal::moduleHandler()->moduleExists('migmag_rollbackable_replace'));
    $this->assertFalse(\Drupal::moduleHandler()->moduleExists('migmag_rollbackable'));
    $this->resetAll();

    $this->assertFalse($connection->schema()->tableExists('migmag_rollback_new_targets'));
    $this->assertFalse($connection->schema()->tableExists('migmag_rollback_data'));

    // We expect 8 preexisting state records, with 'config_id' and 'langcode'
    // columns.
    $preexisting_state_records = $connection->select('acquia_migrate_config_new')
      ->fields('acquia_migrate_config_new')
      ->orderBy('config_id')
      ->orderBy('langcode')
      ->execute()
      ->fetchAll(\PDO::FETCH_ASSOC);
    $this->assertCount(8, $preexisting_state_records);
    $this->assertEquals(
      ['config_id', 'langcode'],
      array_keys($preexisting_state_records[0])
    );

    // 25 preexisting data records expected with 'migration_plugin_id',
    // 'config_id', 'langcode', 'field_name' and 'rollback_data' columns.
    $preexisting_data_records = $connection->select('acquia_migrate_config_rollback_data')
      ->fields('acquia_migrate_config_rollback_data')
      ->orderBy('migration_plugin_id')
      ->orderBy('config_id')
      ->orderBy('langcode')
      ->orderBy('field_name')
      ->execute()
      ->fetchAll(\PDO::FETCH_ASSOC);
    $this->assertCount(25, $preexisting_data_records);
    $this->assertEquals(
      [
        'migration_plugin_id',
        'config_id',
        'langcode',
        'field_name',
        'rollback_data',
      ],
      array_keys($preexisting_data_records[0])
    );

    // Execute the update hook.
    module_load_include('install', 'acquia_migrate');
    acquia_migrate_update_9202();

    // Assert that the new dependencies are enabled, and their tables exist.
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('migmag_rollbackable_replace'));
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('migmag_rollbackable'));
    $this->assertTrue($connection->schema()->tableExists('migmag_rollbackable_new_targets'));
    $this->assertTrue($connection->schema()->tableExists('migmag_rollbackable_data'));

    // Check schema of the state table.
    $introspect_index_schema = new \ReflectionMethod($connection->schema(), 'introspectIndexSchema');
    $introspect_index_schema->setAccessible(TRUE);
    $actual_state_index_schema = $introspect_index_schema->invoke($connection->schema(), 'migmag_rollbackable_new_targets');
    $this->assertEquals(
      [
        // Core's postgresql driver returns primary keys sorted by their column
        // name, unfortunately.
        'primary key' => $is_pgsql
          ? [
            'component',
            'langcode',
            'target_id',
          ]
          : [
            'target_id',
            'langcode',
            'component',
          ],
        'unique keys' => [],
        'indexes' => [],
      ],
      $actual_state_index_schema
    );

    // Check schema of the rollback data table.
    $actual_data_index_schema = $introspect_index_schema->invoke($connection->schema(), 'migmag_rollbackable_data');
    $this->assertEquals(
      [
        // Core's postgresql driver returns primary keys sorted by their column
        // name, unfortunately.
        'primary key' => $is_pgsql
          ? [
            'component',
            'langcode',
            'migration_plugin_id',
            'target_id',
          ]
          : [
            'migration_plugin_id',
            'target_id',
            'langcode',
            'component',
          ],
        'unique keys' => [],
        'indexes' => [],
      ],
      $actual_data_index_schema
    );

    // The updated state table must contain the same amount of data as it had
    // before the update, but there must be a new 'component' column added with
    // an empty string value.
    $updated_state_records = $connection->select('migmag_rollbackable_new_targets')
      ->fields('migmag_rollbackable_new_targets')
      ->orderBy('target_id')
      ->orderBy('langcode')
      ->execute()
      ->fetchAll(\PDO::FETCH_ASSOC);
    $this->assertCount(count($preexisting_state_records), $updated_state_records);
    foreach ($updated_state_records as $index => $updated_state_record) {
      $preexisting_state_record = $preexisting_state_records[$index];
      $actual_new_columns = array_values(
        array_diff(
          array_keys($updated_state_record),
          array_keys($preexisting_state_record)
        )
      );
      sort($actual_new_columns, SORT_STRING);
      $this->assertEquals(
        ['component', 'target_id'],
        array_values($actual_new_columns)
      );
      $this->assertEquals(
        ['config_id'],
        array_values(
          array_diff(
            array_keys($preexisting_state_record),
            array_keys($updated_state_record)
          )
        )
      );

      // Check data integrity.
      $this->assertEquals([
        'target_id' => $preexisting_state_record['config_id'],
        'langcode' => $preexisting_state_record['langcode'],
        'component' => '',
      ], $updated_state_record);
    }

    $updated_data_records = $connection->select('migmag_rollbackable_data')
      ->fields('migmag_rollbackable_data')
      ->orderBy('migration_plugin_id')
      ->orderBy('target_id')
      ->orderBy('langcode')
      ->orderBy('component')
      ->execute()
      ->fetchAll(\PDO::FETCH_ASSOC);
    foreach ($updated_data_records as $index => $updated_data_record) {
      $preexisting_data_record = $preexisting_data_records[$index];
      $actual_new_columns = array_values(
        array_diff(
          array_keys($updated_data_record),
          array_keys($preexisting_data_record)
        )
      );
      sort($actual_new_columns, SORT_STRING);
      $this->assertEquals(
        ['component', 'target_id'],
        $actual_new_columns
      );

      $renamed_old_columns = array_values(
        array_diff(
          array_keys($preexisting_data_record),
          array_keys($updated_data_record)
        )
      );
      sort($renamed_old_columns, SORT_STRING);
      $this->assertEquals(
        ['config_id', 'field_name'],
        $renamed_old_columns
      );

      // Check data integrity.
      $this->assertEquals([
        'migration_plugin_id' => $preexisting_data_record['migration_plugin_id'],
        'target_id' => $preexisting_data_record['config_id'],
        'langcode' => $preexisting_data_record['langcode'],
        'component' => $preexisting_data_record['field_name'],
        'rollback_data' => $preexisting_data_record['rollback_data'],
      ], $updated_data_record);
    }
  }

}
