<?php

namespace Drupal\Tests\acquia_migrate\Traits;

use Drupal\acquia_migrate\Migration;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\migrate\Plugin\migrate\id_map\Sql;
use Drupal\migrate\Plugin\Migration as CoreMigration;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;

/**
 * Provides methods to assert the initial AMA import.
 *
 * @internal
 */
trait InitialImportAssertionTrait {

  /**
   * Asserts that the initial import ran (or not), and the expected row counts.
   *
   * @throws \PHPUnit\Framework\ExpectationFailedException
   */
  protected function assertInitialImport(bool $expected_completed, int $expected_total, int $expected_processed, int $expected_imported) : void {
    assert($expected_total >= $expected_processed);
    assert($expected_processed >= $expected_imported);

    $query = \Drupal::database()->select('acquia_migrate_migration_flags', 'f')
      ->condition('migration_id', Migration::generateIdFromLabel('_INITIAL_'))
      ->fields('f', ['completed']);
    $query->addExpression("SUBSTRING_INDEX(f.last_computed_fingerprint, '/', 1)", 'processed');
    $query->addExpression("SUBSTRING_INDEX(f.last_import_fingerprint, '/', 1)", 'imported');
    // @codingStandardsIgnoreStart
    // @todo Drupal core's \Drupal\Core\Database\Driver\sqlite\Connection::sqlFunctionSubstringIndex() does not support negative indexes!
    //$query->addExpression("SUBSTRING_INDEX(f.last_computed_fingerprint, '/', -1)", 'total');
    // To make this EXTRA fun: SUBSTRING() does not exist in SQLite <3.34, only
    // SUBSTR() exists 🤦‍♂️
    $query->addExpression("SUBSTR(f.last_computed_fingerprint, INSTR(f.last_computed_fingerprint, '/') + 1)", 'total');
    // @codingStandardsIgnoreEnd
    $result = $query->execute()->fetchObject();

    // @codingStandardsIgnoreLine
    //var_dump(\Drupal::database()->select('acquia_migrate_migration_flags', 'f')->condition('migration_id', '0a106348c77ed4ae33a43466dc0c6e09-_INITIAL_')->fields('f')->execute()->fetchObject());

    // Comparing these values in one step reduces maintenance efforts.
    $this->assertEquals(
      [
        'Completed' => $expected_completed,
        'Total' => $expected_total,
        'Processed' => $expected_processed,
        'Imported' => $expected_imported,
      ],
      [
        'Completed' => (bool) $result->completed,
        'Total' => (int) $result->total,
        'Processed' => (int) $result->processed,
        'Imported' => (int) $result->imported,
      ]
    );
  }

  /**
   * Asserts the total/processed/imported/to-update/errored count of migrations.
   *
   * @param array $expected
   *   An array of the total/processed/imported/to-update/errored count per
   *   migrations, keyed by the migration plugin ID.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function assertStrictInitialImport(array $expected) {
    $plugin_manager = $this->container->get('plugin.manager.migration');
    $parsed_expected = [];
    $expected_computed_map_tables = [];
    ksort($expected);
    assert($plugin_manager instanceof MigrationPluginManagerInterface);
    foreach ($expected as $plugin_id => $counts) {
      $migration = $plugin_manager->createInstance($plugin_id);
      if (!($migration instanceof CoreMigration)) {
        $migrations_missing[] = $plugin_id;
        continue;
      }
      $id_map = $migration->getIdMap();
      $expected_computed_map_tables[] = $id_map->mapTableName();
      $total_count = $migration->getSourcePlugin()->count();
      $processed = $id_map->processedCount();
      $imported = $id_map->importedCount();
      $to_update = $id_map->updateCount();
      $errored = $id_map->errorCount();

      $actual[$plugin_id] = [
        'total rows' => $total_count,
        'processed rows' => $processed,
        'imported items' => $imported,
        'items need update' => $to_update,
        'errored items' => $errored,
      ];
      $parsed_expected[$plugin_id] = [
        'total rows' => $counts[0],
        'processed rows' => $counts[1],
        'imported items' => $counts[2],
        'items need update' => $counts[3],
        'errored items' => $counts[4],
      ];
    }

    $this->assertEquals($parsed_expected, $actual ?? []);

    // All expected initial migrations should be present.
    $this->assertEquals([], $migrations_missing ?? [], "Migrate map tables of some initial migrations aren't present.");

    $actual_map_tables = \Drupal::database()->schema()->findTables('migrate_map_%');
    $unexpected_map_tables = array_values(array_diff(array_values($actual_map_tables), $expected_computed_map_tables));
    $unexpected_migrations = [];

    // Try to find out that which migrations the unexpected tables belong to.
    if (!empty($unexpected_map_tables)) {
      $manager = $this->container->get('plugin.manager.migration');
      assert($manager instanceof PluginManagerInterface);
      assert($manager instanceof MigrationPluginManagerInterface);
      $all_migration_plugin_ids = $manager->getDefinitions();
      foreach ($unexpected_map_tables as $unexpected_map_table_name) {
        // Converts
        // 'migrate_map_d7_field_instance__node__articl0907ca4fae9f2402a21d' to
        // 'd7_field_instance:node:artic'.
        $partial_migration_plugin_id =
          // The last underscore might be the first piece of a derivative
          // separator.
          rtrim(
            preg_replace(
              '/__/',
              ':',
              // Cut off 'migrate_map_' prefix (12 characters) and the training
              // hash '0907ca4fae9f2402a21d' (20 charaters).
              substr($unexpected_map_table_name, 12, -20)
            ),
            '_'
          );
        $potentially_matching_definitions = array_filter(
          $all_migration_plugin_ids,
          function ($key) use ($partial_migration_plugin_id) {
            return strpos($key, $partial_migration_plugin_id) === 0;
          },
          ARRAY_FILTER_USE_KEY
        );

        foreach ($potentially_matching_definitions as $plugin_id => $potential_definition) {
          $migration = $manager->createInstance($plugin_id, $potential_definition);
          $this->assertInstanceof(CoreMigration::class, $migration);
          $id_map = $migration->getIdMap();
          assert($id_map instanceof Sql);
          if ($unexpected_map_table_name === $id_map->mapTableName()) {
            $unexpected_migrations[$unexpected_map_table_name] = $plugin_id;
            break 1;
          }
        }
      }

      $this->assertCount(count($unexpected_migrations), $unexpected_map_tables);
    }

    $this->assertEquals([], $unexpected_migrations, 'Unexpected migrate map tables of initial migrations are present.');
  }

}
