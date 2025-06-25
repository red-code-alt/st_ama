<?php

namespace Drupal\Tests\paragraphs_migration\Kernel\migrate;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;
use Drupal\Tests\paragraphs_migration\Traits\ParagraphsSourceData;

/**
 * Test the paragraphs_type source plugin.
 *
 * @covers \Drupal\paragraphs_migration\Plugin\migrate\source\d7\ParagraphsType
 * @group paragraphs_migration
 */
class ParagraphsTypeSourceTest extends MigrateSqlSourceTestBase {

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
    return [
      [
        'source_data' => $this->getSourceData(),
        'expected_results' => [
          [
            'bundle' => 'paragraphs_bundle',
            'name' => 'Paragraphs Bundle',
            'locked' => '1',
            'description' => '',
          ],
        ],
      ],
    ];
  }

}
