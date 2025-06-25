<?php

namespace Drupal\Tests\paragraphs_migration\Kernel\migrate;

/**
 * Test the field_collection_item_revision source plugin.
 *
 * @covers \Drupal\paragraphs_migration\Plugin\migrate\source\d7\FieldCollectionItemRevision
 * @group paragraphs_migration
 */
class FieldCollectionItemRevisionSourceTest extends FieldCollectionItemSourceTest {

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    $source_data = $this->getSourceData();

    $expected_for_term = [
      'item_id' => '2',
      'revision_id' => '2',
      'field_name' => 'field_field_collection_field',
      'archived' => '0',
      'field_text' => [
        0 => [
          'value' => 'FCID2R2 text',
        ],
      ],
    ];

    return [
      // Without explicit configuration.
      [
        'source_data' => $source_data,
        'expected_results' => [
          $expected_for_term,
        ],
        'expected_count' => 1,
        'config' => [],
      ],
      // Configuration limited to a specific field.
      [
        'source_data' => $source_data,
        'expected_results' => [
          $expected_for_term,
        ],
        'expected_count' => 1,
        'config' => [
          'field_name' => 'field_field_collection_field',
        ],
      ],
      // Configuration limited to a specific field, valid entity type and
      // bundle.
      [
        'source_data' => $source_data,
        'expected_results' => [
          $expected_for_term,
        ],
        'expected_count' => 1,
        'config' => [
          'field_name' => 'field_field_collection_field',
          'parent_type' => 'taxonomy_term',
          'parent_bundle' => 'category_landing',
        ],
      ],
      // Configuration with invalid entity type.
      [
        'source_data' => $source_data,
        'expected_results' => [],
        'expected_count' => 0,
        'config' => [
          'field_name' => 'field_field_collection_field',
          'parent_type' => 'singularity',
          'parent_bundle' => 'singularity',
        ],
      ],
      // Configuration with valid entity type, but with invalid bundle.
      [
        'source_data' => $source_data,
        'expected_results' => [],
        'expected_count' => 0,
        'config' => [
          'field_name' => 'field_field_collection_field',
          'parent_type' => 'taxonomy_term',
          'parent_bundle' => 'singularity',
        ],
      ],
      // Limited only to a specific entity type ID (node).
      [
        'source_data' => $source_data,
        'expected_results' => [],
        'expected_count' => 0,
        'config' => [
          'field_name' => 'field_field_collection_field',
          'parent_type' => 'node',
        ],
      ],
      // Limited only to a specific entity type ID (taxonomy term).
      [
        'source_data' => $source_data,
        'expected_results' => [
          $expected_for_term,
        ],
        'expected_count' => 1,
        'config' => [
          'field_name' => 'field_field_collection_field',
          'parent_type' => 'taxonomy_term',
        ],
      ],
    ];
  }

}
