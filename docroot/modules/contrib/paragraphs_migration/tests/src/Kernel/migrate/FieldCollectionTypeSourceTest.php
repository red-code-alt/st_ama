<?php

namespace Drupal\Tests\paragraphs_migration\Kernel\migrate;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;
use Drupal\Tests\paragraphs_migration\Traits\FieldCollectionSourceData;

/**
 * Test the field_collection_type source plugin.
 *
 * @covers \Drupal\paragraphs_migration\Plugin\migrate\source\d7\FieldCollectionType
 * @group paragraphs_migration
 */
class FieldCollectionTypeSourceTest extends MigrateSqlSourceTestBase {

  use FieldCollectionSourceData;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['migrate_drupal', 'paragraphs',
    'paragraphs_migration',
  ];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    return [
      [
        'source_data' => $this->getSourceData(),
        'expected_results' => [
          [
            'id' => '1',
            'field_name' => 'field_field_collection_field',
            'module' => 'field_collection',
            'active' => '1',
            'data' => 'serialized field collection field data',
            'name' => 'Field collection field',
            'description' => '',
          ],
        ],
      ],
    ];
  }

}
