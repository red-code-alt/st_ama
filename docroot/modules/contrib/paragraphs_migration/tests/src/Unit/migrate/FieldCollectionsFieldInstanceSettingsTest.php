<?php

namespace Drupal\Tests\paragraphs_migration\Unit\migrate;

use Drupal\migrate\MigrateLookupInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\paragraphs_migration\Plugin\migrate\process\FieldCollectionFieldInstanceSettings;
use Prophecy\Argument;

/**
 * Test the ParagraphFieldInstanceSettings Process Plugin.
 *
 * @group paragraphs_migration
 * @coversDefaultClass \Drupal\paragraphs\Plugin\migrate\process\FieldCollectionFieldInstanceSettings
 */
class FieldCollectionsFieldInstanceSettingsTest extends ProcessTestCase {

  /**
   * MigrateLookup object prophecy.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $lookup;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->lookup = $this->prophesize(MigrateLookupInterface::class);

    $this->plugin = new FieldCollectionFieldInstanceSettings([], 'pm_field_collection_field_instance_settings', [], $this->entityTypeBundleInfo, $this->lookup->reveal());
  }

  /**
   * Test settings for field_collection field instances.
   *
   * @param array $source
   *   The data source.
   * @param array $expected
   *   The expected result.
   *
   * @dataProvider getData
   */
  public function testFieldCollectionInstanceFieldSettings(array $source, array $expected) {
    $this->row->expects($this->any())
      ->method('getSourceProperty')
      ->willReturnMap([
        ['type', 'field_collection'],
        ['field_name', 'field_field_collection_bundle_one'],
      ]);

    $this->lookup->lookup('d7_pm_field_collection_type', Argument::type('array'))
      ->will(function () {
        $source_bundle = func_get_args()[0][1][0];
        return [
          0 => [
            'item_id' => preg_replace('/^field_/', '', $source_bundle),
          ],
        ];
      });

    $this->plugin = new FieldCollectionFieldInstanceSettings([], 'pm_field_collection_field_instance_settings', [], $this->entityTypeBundleInfo, $this->lookup->reveal());
    $actual_result = $this->plugin->transform($source, $this->migrateExecutable, $this->row, 'settings');

    $this->assertEquals($expected, $actual_result);
  }

  /**
   * Test that unexpected bundles trigger an exception.
   */
  public function testFieldCollectionBadBundle() {
    $this->row->expects($this->any())
      ->method('getSourceProperty')
      ->willReturnMap([
        ['type', 'field_collection'],
        ['field_name', 'field_unmigrated_collection_field'],
      ]);
    $this->expectException(MigrateSkipRowException::class);
    $this->expectExceptionMessage("No target paragraph bundle found for field_collection");
    $this->plugin->transform([], $this->migrateExecutable, $this->row, 'settings');
  }

  /**
   * Data provider for unit test.
   *
   * @return array
   *   The source data and expected data.
   */
  public function getData() {
    $data = [
      'With no data' => [
        'source_data' => [],
        'expected_results' => [
          'handler_settings' => [
            'negate' => 0,
            'target_bundles' => [
              'field_collection_bundle_one' => 'field_collection_bundle_one',
            ],
            'target_bundles_drag_drop' => [
              'field_collection_bundle_one' => [
                'enabled' => TRUE,
                'weight' => 1,
              ],
              'paragraph_bundle_one' => [
                'enabled' => FALSE,
                'weight' => 2,
              ],
              'paragraph_bundle_two' => [
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
