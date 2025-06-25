<?php

namespace Drupal\Tests\acquia_migrate\Kernel\Migrate;

use Drupal\migmag_rollbackable\RollbackableInterface;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Tests formatter and widget fallback functionality.
 *
 * @group acquia_migrate
 * @group acquia_migrate__core
 * @group acquia_migrate__mysql
 */
class FormatterWidgetFallbackTest extends MigrateDrupal7TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'acquia_migrate',
    'migmag',
    'migmag_rollbackable',
    'migmag_rollbackable_replace',
    'node',
    'syslog',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installSchema('acquia_migrate', ['acquia_migrate_migration_flags']);
    $this->installSchema('migmag_rollbackable', [RollbackableInterface::ROLLBACK_DATA_TABLE, RollbackableInterface::ROLLBACK_STATE_TABLE]);

    if (!empty(\Drupal::service('extension.list.module')->getList()['field_plugin_migrate_fallback_test'])) {
      $this->enableModules(['field_plugin_migrate_fallback_test']);
    }
  }

  /**
   * Tests that field formatter alter of field migrate plugins are invoked.
   *
   * @see \Drupal\field_plugin_migrate_fallback_test\D7TextField::alterFieldFormatterMigration()
   */
  public function testFormatterFallback(): void {
    $formatter_migration = $this->getMigration('d7_field_formatter_settings:node:book');
    $definition_before = $formatter_migration->getPluginDefinition();
    $original_settings_process = static::normalizeProcessPipeline($definition_before['process']['options/settings']);

    $formatter_migration->getProcess();

    $definition_after = $formatter_migration->getPluginDefinition();
    $this->assertEquals(
      array_keys($definition_before['process']),
      array_keys($definition_after['process'])
    );
    $this->assertEquals(
      $original_settings_process + ['formatter_fallback_test' => ['plugin' => 'get']],
      $definition_after['process']['options/settings']
    );
  }

  /**
   * Tests that field widget alter of field migrate plugins are invoked.
   *
   * @see \Drupal\field_plugin_migrate_fallback_test\D7TextField::alterFieldWidgetMigration()
   */
  public function testWidgetFallback(): void {
    $widget_migration = $this->getMigration('d7_field_instance_widget_settings:node:book');
    $definition_before = $widget_migration->getPluginDefinition();
    $original_settings_process = static::normalizeProcessPipeline($definition_before['process']['options/settings']);

    $widget_migration->getProcess();

    $definition_after = $widget_migration->getPluginDefinition();
    $this->assertEquals(
      array_keys($definition_before['process']),
      array_keys($definition_after['process'])
    );
    $this->assertEquals(
      $original_settings_process + ['widget_fallback_test' => ['plugin' => 'get']],
      $definition_after['process']['options/settings']
    );
  }

  /**
   * Normalizes the given process pipeline.
   *
   * Ensures that the given process pipeline is a list of process pipeline
   * configurations.
   *
   * @param string|array $process_pipeline
   *   The process pipeline to normalize.
   *
   * @return array[]
   *   The normalized process pipeline.
   */
  protected static function normalizeProcessPipeline($process_pipeline) {
    if (is_string($process_pipeline)) {
      return [['plugin' => 'get', 'source' => $process_pipeline]];
    }
    if (isset($process_pipeline['plugin'])) {
      return [$process_pipeline];
    }
    return $process_pipeline;
  }

}
