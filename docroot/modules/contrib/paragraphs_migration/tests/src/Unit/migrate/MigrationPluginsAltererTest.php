<?php

namespace Drupal\Tests\paragraphs_migration\Unit\migrate;

use Drupal\paragraphs_migration\MigrationPluginsAlterer;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the MigrationPluginsAlterer service.
 *
 * @todo Cover every method.
 *
 * @coversDefaultClass \Drupal\paragraphs_migration\MigrationPluginsAlterer
 *
 * @group paragraphs_migration
 */
class MigrationPluginsAltererTest extends UnitTestCase {

  /**
   * The migration plugin alterer.
   *
   * @var \Drupal\paragraphs_migration\MigrationPluginsAlterer
   */
  protected $paragraphsMigrationPluginsAlterer;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->paragraphsMigrationPluginsAlterer = new MigrationPluginsAlterer();
  }

  /**
   * Tests that migration processes are transformed to an array of processors.
   *
   * @dataProvider providerParagraphsMigrationPrepareProcess
   * @covers ::paragraphsMigrationPrepareProcess
   */
  public function testParagraphsMigrationPrepareProcess(array $input, array $expected) {
    ['process' => $process, 'property' => $property] = $input;
    $success = $this->paragraphsMigrationPluginsAlterer->paragraphsMigrationPrepareProcess($process, $property);
    $this->assertSame($expected['return'], $success);
    $this->assertEquals($expected['process'], $process);
  }

  /**
   * Provides data and expected results for testing the prepare process method.
   *
   * @return array[]
   *   Data and expected results.
   */
  public function providerParagraphsMigrationPrepareProcess() {
    return [
      // Missing property (no change).
      [
        'input' => [
          'process' => [
            'catname' => 'Picurka',
            'wont/touch' => 'this',
          ],
          'property' => 'missing',
        ],
        'expected' => [
          'return' => FALSE,
          'process' => [
            'catname' => 'Picurka',
            'wont/touch' => 'this',
          ],
        ],
      ],
      // Existing string property.
      [
        'input' => [
          'process' => [
            'catname' => 'Picurka',
            'wont/touch' => 'this',
          ],
          'property' => 'catname',
        ],
        'expected' => [
          'return' => TRUE,
          'process' => [
            'catname' => [
              [
                'plugin' => 'get',
                'source' => 'Picurka',
              ],
            ],
            'wont/touch' => 'this',
          ],
        ],
      ],
      // Single process plugin.
      [
        'input' => [
          'process' => [
            'cat' => [
              'plugin' => 'migration_lookup',
              'migration' => 'cats',
              'source' => 'cat_id',
            ],
          ],
          'property' => 'cat',
        ],
        'expected' => [
          'return' => TRUE,
          'process' => [
            'cat' => [
              [
                'plugin' => 'migration_lookup',
                'migration' => 'cats',
                'source' => 'cat_id',
              ],
            ],
          ],
        ],
      ],
      // Array of process plugins (no change).
      [
        'input' => [
          'process' => [
            'catname' => [
              [
                'plugin' => 'migration_lookup',
                'migration' => 'cats',
                'source' => 'cat_id',
              ],
              [
                'plugin' => 'extract',
                'index' => ['name'],
              ],
              [
                'plugin' => 'callback',
                'callable' => 'ucfirst',
              ],
            ],
          ],
          'property' => 'catname',
        ],
        'expected' => [
          'return' => TRUE,
          'process' => [
            'catname' => [
              [
                'plugin' => 'migration_lookup',
                'migration' => 'cats',
                'source' => 'cat_id',
              ],
              [
                'plugin' => 'extract',
                'index' => ['name'],
              ],
              [
                'plugin' => 'callback',
                'callable' => 'ucfirst',
              ],
            ],
          ],
        ],
      ],
      // Invalid type.
      [
        'input' => [
          'process' => [
            'invalid' => (object) [
              [
                'not a' => 'kitten',
              ],
            ],
          ],
          'property' => 'invalid',
        ],
        'expected' => [
          'return' => FALSE,
          'process' => [
            'invalid' => (object) [
              [
                'not a' => 'kitten',
              ],
            ],
          ],
        ],
      ],
    ];
  }

  /**
   * Tests getSourceValueOfMigrationProcess().
   *
   * @covers ::getSourceValueOfMigrationProcess
   *
   * @dataProvider getSourceValueOfMigrationProcessProvider
   */
  public function testGetSourceValueOfMigrationProcess(array $migration, string $process_property_key, $expected_return, $expected_exception) {
    if (!empty($expected_exception)) {
      $this->expectException($expected_exception['class']);
      $this->expectExceptionMessage($expected_exception['message']);
    }
    $this->assertSame($expected_return, MigrationPluginsAlterer::getSourceValueOfMigrationProcess($migration, $process_property_key));
  }

  /**
   * Data provider for ::testGetSourceValueOfMigrationProcess.
   */
  public function getSourceValueOfMigrationProcessProvider() {
    $embedded_data_source = [
      'source' => [
        'plugin' => 'embedded_data',
        'data_rows' => [
          [
            'same' => 'same_val',
            'dynamic' => 'dynamic_one',
            'partial' => 'partial_val',
          ],
          [
            'same' => 'same_val',
            'dynamic' => 'dynamic_two',
          ],
        ],
        'constants' => [
          'foo' => 'foo_val',
        ],
      ],
    ];

    $migration = [
      'source' => [
        'plugin' => 'plugin_id',
        'foo' => 'foo_val',
        'bar' => 'bar_val',
        'foobar' => 'foobar_val',
        'constants' => [
          'foo' => [
            'bar' => [
              'baz' => 'foobarbaz_val',
            ],
          ],
        ],
      ],
      'process' => [
        'fooproc' => 'foo',
        'barproc' => [
          'plugin' => 'get',
          'source' => 'bar',
        ],
        'foobarproc' => [
          [
            'plugin' => 'get',
            'source' => 'foobar',
          ],
        ],
        'dynamic' => [
          [
            'plugin' => 'get',
            'source' => 'foobar',
          ],
          [
            'plugin' => 'static_map',
            'map' => [
              'mapthis' => 'tothis',
            ],
          ],
        ],
        'foobarbazproc' => 'constants/foo/bar/baz',
        'anotherproc' => 'constants/foo',
        'foo/bar/baz/proc' => 'foo',
        'missing_source' => 'missing_source_prop',
        'missing_from_constants' => 'constants/missing_prop',
        'embedsameproc' => [
          [
            'plugin' => 'get',
            'source' => 'same',
          ],
        ],
        'embeddynamicproc' => [
          'plugin' => 'get',
          'source' => 'dynamic',
        ],
        'embedpartialproc' => 'partial',
      ],
    ];

    return [
      'Property not available' => [
        'migration' => $migration,
        'property' => 'missing_process',
        'expected' => '',
        'exception' => [
          'class' => \LogicException::class,
          'message' => 'No corresponding process found',
        ],
      ],
      'Property process is a string' => [
        'migration' => $migration,
        'property' => 'fooproc',
        'expected' => 'foo_val',
        'exception' => NULL,
      ],
      'Property process is a plugin definition array' => [
        'migration' => $migration,
        'property' => 'barproc',
        'expected' => 'bar_val',
        'exception' => NULL,
      ],
      'Property process is an array of a single plugin definition array' => [
        'migration' => $migration,
        'property' => 'foobarproc',
        'expected' => 'foobar_val',
        'exception' => NULL,
      ],
      'Property process is an array of a multiple plugin definitions' => [
        'migration' => $migration,
        'property' => 'dynamic',
        'expected' => NULL,
        'exception' => NULL,
      ],
      'Property value is a multi-level constant defined with "Row::PROPERTY_SEPARATOR"' => [
        'migration' => $migration,
        'property' => 'foobarbazproc',
        'expected' => 'foobarbaz_val',
        'exception' => NULL,
      ],
      'Property value is a multi-level constant defined as array' => [
        'migration' => $migration,
        'property' => 'anotherproc',
        'expected' => [
          'bar' => [
            'baz' => 'foobarbaz_val',
          ],
        ],
        'exception' => NULL,
      ],
      'Property name contains "Row::PROPERTY_SEPARATOR"' => [
        'migration' => $migration,
        'property' => 'foo/bar/baz/proc',
        'expected' => 'foo_val',
        'exception' => NULL,
      ],
      'Property source is not available' => [
        'migration' => $migration,
        'property' => 'missing_source',
        'expected' => NULL,
        'exception' => NULL,
      ],
      'Property source is not available in a source property array' => [
        'migration' => $migration,
        'property' => 'missing_from_constants',
        'expected' => NULL,
        'exception' => NULL,
      ],
      'Embedded_data plugin, existing property value' => [
        'migration' => $embedded_data_source + $migration,
        'property' => 'embedsameproc',
        'expected' => 'same_val',
        'exception' => NULL,
      ],
      'Embedded_data plugin, existing property with dynamic value' => [
        'migration' => $embedded_data_source + $migration,
        'property' => 'embeddynamicproc',
        'expected' => NULL,
        'exception' => NULL,
      ],
      'Embedded_data plugin, existing property with partially avaliable value' => [
        'migration' => $embedded_data_source + $migration,
        'property' => 'embedpartialproc',
        'expected' => NULL,
        'exception' => NULL,
      ],
    ];
  }

}
