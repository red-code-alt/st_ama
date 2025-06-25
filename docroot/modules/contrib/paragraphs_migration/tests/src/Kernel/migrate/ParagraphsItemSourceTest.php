<?php

namespace Drupal\Tests\paragraphs_migration\Kernel\migrate;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;
use Drupal\Tests\paragraphs_migration\Traits\ParagraphsSourceData;

/**
 * Test the paragraphs_item source plugin.
 *
 * @covers \Drupal\paragraphs_migration\Plugin\migrate\source\d7\ParagraphsItem
 * @group paragraphs_migration
 */
class ParagraphsItemSourceTest extends MigrateSqlSourceTestBase {

  use ParagraphsSourceData;

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
    $source_data = $this->getSourceData();

    $expected_for_node = [
      'item_id' => '1',
      'revision_id' => '1',
      'field_name' => 'field_paragraphs_field',
      'bundle' => 'paragraphs_bundle',
      'archived' => '0',
      'parent_id' => '5',
      'parent_type' => 'node',
      'field_text' => [
        0 => [
          'value' => 'PID1R1 text',
        ],
      ],
    ];
    $expected_for_term = [
      'item_id' => '2',
      'revision_id' => '3',
      'field_name' => 'field_paragraphs_field',
      'bundle' => 'paragraphs_bundle',
      'archived' => '0',
      'parent_id' => '42',
      'parent_type' => 'taxonomy_term',
      'field_text' => [
        0 => [
          'value' => 'PID2R3 text',
        ],
      ],
    ];

    return [
      // Without explicit configuration.
      [
        'source_data' => $source_data,
        'expected_results' => [
          $expected_for_node,
          $expected_for_term,
        ],
        'expected_count' => 2,
        'config' => [],
      ],
      // Configuration limited to a missing paragraphs bundle.
      [
        'source_data' => $source_data,
        'expected_results' => [],
        'expected_count' => 0,
        'config' => [
          'bundle' => 'missing_paragraph_bundle',
        ],
      ],
      // Configuration limited to an existing paragraphs bundle.
      [
        'source_data' => $source_data,
        'expected_results' => [
          $expected_for_node,
          $expected_for_term,
        ],
        'expected_count' => 2,
        'config' => [
          'bundle' => 'paragraphs_bundle',
        ],
      ],
      // Configuration limited to a specific field, valid parent entity type,
      // parent entity bundle and paragraphs bundle.
      [
        'source_data' => $source_data,
        'expected_results' => [
          $expected_for_node,
        ],
        'expected_count' => 1,
        'config' => [
          'field_name' => 'field_paragraphs_field',
          'parent_type' => 'node',
          'parent_bundle' => 'landing',
          'bundle' => 'paragraphs_bundle',
        ],
      ],
      // Configuration limited to a specific field, valid parent entity type,
      // parent entity bundle, not limiting paragraphs bundle.
      [
        'source_data' => $source_data,
        'expected_results' => [
          $expected_for_node,
        ],
        'expected_count' => 1,
        'config' => [
          'field_name' => 'field_paragraphs_field',
          'parent_type' => 'node',
          'parent_bundle' => 'landing',
        ],
      ],
      // Configuration limited to a specific field, term and vocabulary ID.
      [
        'source_data' => $source_data,
        'expected_results' => [
          $expected_for_term,
        ],
        'expected_count' => 1,
        'config' => [
          'field_name' => 'field_paragraphs_field',
          'parent_type' => 'taxonomy_term',
          'parent_bundle' => 'category_landing',
          'bundle' => 'paragraphs_bundle',
        ],
      ],
      // Configuration with invalid entity type.
      [
        'source_data' => $source_data,
        'expected_results' => [],
        'expected_count' => 0,
        'config' => [
          'field_name' => 'field_paragraphs_field',
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
          'field_name' => 'field_paragraphs_field',
          'parent_type' => 'node',
          'parent_bundle' => 'singularity',
        ],
      ],
      // Limited only to a specific entity type ID (node).
      [
        'source_data' => $source_data,
        'expected_results' => [
          $expected_for_node,
        ],
        'expected_count' => 1,
        'config' => [
          'field_name' => 'field_paragraphs_field',
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
          'field_name' => 'field_paragraphs_field',
          'parent_type' => 'taxonomy_term',
        ],
      ],
    ];
  }

}
