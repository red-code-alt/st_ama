<?php

namespace Drupal\Tests\acquia_migrate\Kernel\Migrate\process;

use Drupal\acquia_migrate\Plugin\migrate\process\EnsurePluginAvailable;
use Drupal\KernelTests\KernelTestBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;

/**
 * Tests Menu link migration.
 *
 * @coversDefaultClass \Drupal\acquia_migrate\Plugin\migrate\process\EnsurePluginAvailable
 * @group acquia_migrate
 * @group acquia_migrate__core
 */
class EnsurePluginAvailableTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'user',
    'block',
    'system',
    'field',
  ];

  /**
   * Tests Route plugin based on providerTestRoute() values.
   *
   * @param mixed $value
   *   Input value for the Route process plugin.
   * @param array $plugin_config
   *   Configuration for the process plugin.
   * @param array $expected
   *   The expected results from the Route transform process.
   *   Should contain an expected "value", or an expected "exception" and an
   *   expected "exception_message".
   *
   * @dataProvider providerTestEnsurePluginAvailablePlugin
   */
  public function testEnsurePluginAvailablePlugin($value, array $plugin_config, array $expected) {
    if (!array_key_exists('value', $expected)) {
      $this->expectException($expected['exception']);
      $this->expectExceptionMessage($expected['exception_message']);
      $this->doTransform($value, $plugin_config);
    }
    else {
      $this->assertSame($expected['value'], $this->doTransform($value, $plugin_config));
    }
  }

  /**
   * Performs the plugin's transformation.
   *
   * @param mixed $value
   *   The source (an existing or missing plugin ID).
   * @param array $plugin_config
   *   The configuration of the process plugin.
   *
   * @return string
   *   The route information based on the source link_path.
   */
  protected function doTransform($value, array $plugin_config) {
    $row = new Row([
      'property' => 'unchanged property value',
      'property_obj' => (object) ['prop' => 'val'],
      'property_arr' => ['prop' => 'val'],
    ]);
    $row->setDestinationProperty('property', 'processed property value');
    $migration = $this->prophesize(MigrationInterface::class)->reveal();
    $executable = $this->prophesize(MigrateExecutableInterface::class)->reveal();

    $plugin = new EnsurePluginAvailable($plugin_config, 'ensure_plugin_available', [], $migration);
    $actual = $plugin->transform($value, $executable, $row, 'destinationproperty');
    return $actual;
  }

  public function providerTestEnsurePluginAvailablePlugin() {
    return [
      'Null value, no override' => [
        'value' => NULL,
        'plugin_config' => [
          'plugin_manager_id' => 'plugin.manager.mail',
        ],
        'expected' => [
          'exception' => 'Drupal\migrate\MigrateException',
          'exception_message' => "The incoming value of ensure_plugin_available migration process plugin must be a non-empty string. The current value type is NULL.",
        ],
      ],
      'Object value, no override' => [
        'value' => (object) ['foo' => 'bar'],
        'plugin_config' => [
          'plugin_manager_id' => 'plugin.manager.mail',
        ],
        'expected' => [
          'exception' => 'Drupal\migrate\MigrateException',
          'exception_message' => "The incoming value of ensure_plugin_available migration process plugin must be a non-empty string. The current value type is object.",
        ],
      ],
      'Null value, object override' => [
        'value' => NULL,
        'plugin_config' => [
          'plugin_manager_id' => 'plugin.manager.mail',
          'source_override' => 'property_obj',
        ],
        'expected' => [
          'exception' => 'Drupal\migrate\MigrateException',
          'exception_message' => "The incoming value of ensure_plugin_available migration process plugin must be a non-empty string. The current value type is object.",
        ],
      ],
      'Object value, object override' => [
        'value' => (object) ['foo' => 'bar'],
        'plugin_config' => [
          'plugin_manager_id' => 'plugin.manager.mail',
          'source_override' => 'property_obj',
        ],
        'expected' => [
          'exception' => 'Drupal\migrate\MigrateException',
          'exception_message' => "The incoming value of ensure_plugin_available migration process plugin must be a non-empty string. The current value type is object.",
        ],
      ],
      'Null value, array override' => [
        'value' => NULL,
        'plugin_config' => [
          'plugin_manager_id' => 'plugin.manager.mail',
          'source_override' => 'property_arr',
        ],
        'expected' => [
          'exception' => 'Drupal\migrate\MigrateException',
          'exception_message' => "The incoming value of ensure_plugin_available migration process plugin must be a non-empty string. The current value type is array.",
        ],
      ],
      'Missing mandatory "plugin_manager_id" configuration' => [
        'value' => 'foo',
        'plugin_config' => [],
        'expected' => [
          'exception' => 'Drupal\migrate\MigrateException',
          'exception_message' => "The 'plugin_manager_id' configuration has to be defined for the 'ensure_plugin_available' migration process plugin.",
        ],
      ],
      'Missing plugin manager' => [
        'value' => 'foo',
        'plugin_config' => [
          'plugin_manager_id' => 'missing_plugin_manager',
        ],
        'expected' => [
          'exception' => 'Drupal\migrate\MigrateException',
          'exception_message' => "The 'plugin_manager_id' configuration refers to a missing plugin manager. The current value is 'missing_plugin_manager'.",
        ],
      ],
      'Existing service, but not a plugin manager' => [
        'value' => 'foo',
        'plugin_config' => [
          'plugin_manager_id' => 'config.storage',
        ],
        'expected' => [
          'exception' => 'Drupal\migrate\MigrateException',
          'exception_message' => "The 'plugin_manager_id' configuration refers to a service that is not a plugin manager. The current value is 'config.storage'.",
        ],
      ],
      'Existing block plugin ID (with an invalid source override that should be omitted), no process plugin config' => [
        'value' => 'user_login_block',
        'plugin_config' => [
          'plugin_manager_id' => 'plugin.manager.block',
          'source_override' => 'property_obj',
        ],
        'expected' => [
          'value' => 'user_login_block',
        ],
      ],
      'Existing field type plugin ID, no process plugin config' => [
        'value' => 'integer',
        'plugin_config' => [
          'plugin_manager_id' => 'plugin.manager.field.field_type',
        ],
        'expected' => [
          'value' => 'integer',
        ],
      ],
      'Missing field type plugin ID, with customized message' => [
        'value' => '_missing_field_type',
        'plugin_config' => [
          'plugin_manager_id' => 'plugin.manager.field.field_type',
          'message_template' => 'Customized message',
        ],
        'expected' => [
          'exception' => 'Drupal\migrate\MigrateSkipRowException',
          'exception_message' => "Customized message",
        ],
      ],
      'Missing plugin ID with customized message and args' => [
        'value' => '_missing_field_type',
        'plugin_config' => [
          'plugin_manager_id' => 'plugin.manager.field.field_type',
          'message_template' => "Message: '<raw>', '<processed>'",
          'message_args' => ['raw' => 'property', 'processed' => '@property'],
        ],
        'expected' => [
          'exception' => 'Drupal\migrate\MigrateSkipRowException',
          'exception_message' => "Message: 'unchanged property value', 'processed property value'",
        ],
      ],
      'Missing field type plugin ID, ony custom args' => [
        'value' => 'number',
        'plugin_config' => [
          'plugin_manager_id' => 'plugin.manager.field.field_type',
          'message_args' => ['processed' => '@property'],
        ],
        'expected' => [
          'exception' => 'Drupal\migrate\MigrateSkipRowException',
          'exception_message' => 'The "number" plugin does not exist. Valid plugin IDs for Drupal\Core\Field\FieldTypePluginManager are:',
        ],
      ],
    ];
  }

}
