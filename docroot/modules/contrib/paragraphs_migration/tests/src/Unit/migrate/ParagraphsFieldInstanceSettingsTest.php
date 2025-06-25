<?php

namespace Drupal\Tests\paragraphs_migration\Unit\migrate;

use Drupal\migrate\MigrateLookupInterface;
use Drupal\paragraphs_migration\Plugin\migrate\process\ParagraphsFieldInstanceSettings;
use Prophecy\Argument;

/**
 * Test the ParagraphFieldInstanceSettings Process Plugin.
 *
 * @group paragraphs_migration
 * @coversDefaultClass \Drupal\paragraphs\Plugin\migrate\process\ParagraphsFieldInstanceSettings
 */
class ParagraphsFieldInstanceSettingsTest extends ProcessTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->row->expects($this->any())
      ->method('getSourceProperty')
      ->with('type')
      ->willReturn('paragraphs');
    $lookup = $this->prophesize(MigrateLookupInterface::class);
    $lookup->lookup('d7_pm_paragraphs_type', Argument::type('array'))
      ->will(function () {
        return [
          0 => [
            'item_id' => func_get_args()[0][1][0],
          ],
        ];
      });

    $this->plugin = new ParagraphsFieldInstanceSettings([], 'pm_paragraphs_field_instance_settings', [], $this->entityTypeBundleInfo, $lookup->reveal());
  }

  /**
   * Test settings for paragraphs field instances.
   *
   * @param array $source
   *   The data source.
   * @param array $expected
   *   The expected result.
   *
   * @dataProvider getData
   */
  public function testParagraphsInstanceFieldSettings(array $source, array $expected) {
    $value = $this->plugin->transform($source, $this->migrateExecutable, $this->row, 'settings');

    $this->assertEquals($expected, $value);
  }

  /**
   * Data provider for unit test.
   *
   * @return array
   *   The source data and expected data.
   */
  public function getData() {
    $data = [
      'With one bundle allowed' => [
        'source_data' => [
          'allowed_bundles' => [
            'paragraph_bundle_one' => 'paragraph_bundle_one',
            'paragraph_bundle_two' => -1,
          ],
          'bundle_weights' => [
            'paragraph_bundle_one' => 1,
            'paragraph_bundle_two' => 2,
          ],
        ],
        'expected_results' => [
          'handler_settings' => [
            'negate' => 0,
            'target_bundles' => [
              'paragraph_bundle_one' => 'paragraph_bundle_one',
            ],
            'target_bundles_drag_drop' => [
              'paragraph_bundle_one' => [
                'enabled' => TRUE,
                'weight' => 1,
              ],
              'paragraph_bundle_two' => [
                'enabled' => FALSE,
                'weight' => 2,
              ],
              'field_collection_bundle_one' => [
                'enabled' => FALSE,
                'weight' => 3,
              ],
              'field_collection_bundle_two' => [
                'enabled' => FALSE,
                'weight' => 4,
              ],
              'prexisting_bundle_one' => [
                'enabled' => FALSE,
                'weight' => 5,
              ],
              'prexisting_bundle_two' => [
                'enabled' => FALSE,
                'weight' => 6,
              ],
            ],
          ],
        ],
      ],
      'With all bundles allowed' => [
        'source_data' => [
          'allowed_bundles' => [
            'paragraph_bundle_one' => -1,
            'paragraph_bundle_two' => -1,
          ],
          'bundle_weights' => [
            'paragraph_bundle_one' => 1,
            'paragraph_bundle_two' => 2,
          ],
        ],
        'expected_results' => [
          'handler_settings' => [
            'negate' => 0,
            'target_bundles' => NULL,
            'target_bundles_drag_drop' => [
              'paragraph_bundle_one' => [
                'enabled' => FALSE,
                'weight' => 1,
              ],
              'paragraph_bundle_two' => [
                'enabled' => FALSE,
                'weight' => 2,
              ],
              'field_collection_bundle_one' => [
                'enabled' => FALSE,
                'weight' => 3,
              ],
              'field_collection_bundle_two' => [
                'enabled' => FALSE,
                'weight' => 4,
              ],
              'prexisting_bundle_one' => [
                'enabled' => FALSE,
                'weight' => 5,
              ],
              'prexisting_bundle_two' => [
                'enabled' => FALSE,
                'weight' => 6,
              ],
            ],
          ],
        ],
      ],
    ];
    return $data;
  }

}
