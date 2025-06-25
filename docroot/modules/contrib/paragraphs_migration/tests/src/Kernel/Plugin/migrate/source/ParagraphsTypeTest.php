<?php

namespace Drupal\Tests\paragraphs_migration\Kernel\Plugin\migrate\source;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests the "paragraphs_type" migrate source plugin.
 *
 * @covers \Drupal\paragraphs_migration\Plugin\migrate\source\d7\ParagraphsType
 * @group paragraphs_migration
 */
class ParagraphsTypeTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'migrate_drupal',
    'paragraphs',
    'paragraphs_migration',
  ];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    $default_db = [
      'system' => [
        [
          'name' => 'paragraphs',
          'status' => 1,
        ],
      ],
      'paragraphs_bundle' => [
        [
          'bundle' => 'bundle_one',
          'name' => 'Bundle One',
          'locked' => 1,
        ],
        [
          'bundle' => 'bundle_two',
          'name' => 'Bundle Two',
          'locked' => 1,
        ],
      ],
      'paragraphs_item' => [
        [
          'bundle' => 'bundle_one',
        ],
      ],
    ];

    return [
      'Without missing items' => [
        'Database' => [
          'paragraphs_item' => [
            [
              'bundle' => 'bundle_one',
            ],
            [
              'bundle' => 'bundle_two',
            ],
          ],
        ] + $default_db,
        'Expected results' => [
          [
            'bundle' => 'bundle_one',
            'name' => 'Bundle One',
            'locked' => 1,
            'description' => '',
          ],
          [
            'bundle' => 'bundle_two',
            'name' => 'Bundle Two',
            'locked' => 1,
            'description' => '',
          ],
        ],
      ],
      'Bundles without instances, with description' => [
        'Database' => $default_db,
        'Expected results' => [
          [
            'bundle' => 'bundle_one',
            'name' => 'Bundle One',
            'locked' => 1,
            'description' => '',
          ],
          [
            'bundle' => 'bundle_two',
            'name' => 'Bundle Two',
            'locked' => 1,
            'description' => '',
          ],
        ],
      ],
      'Test with missing bundle' => [
        'Database' => [
          'paragraphs_item' => [
            [
              'bundle' => 'bundle_two',
            ],
            [
              'bundle' => 'missing_bundle',
            ],
          ],
        ] + $default_db,
        'Expected results' => [
          [
            'bundle' => 'bundle_one',
            'name' => 'Bundle One',
            'locked' => 1,
            'description' => 'Migrated from paragraph bundle Bundle One',
          ],
          [
            'bundle' => 'bundle_two',
            'name' => 'Bundle Two',
            'locked' => 1,
            'description' => 'Migrated from paragraph bundle Bundle Two',
          ],
          [
            'bundle' => 'missing_bundle',
            'name' => NULL,
            'locked' => NULL,
            'description' => 'Migrated from paragraph bundle missing bundle',
          ],
        ],
        'Expected count' => NULL,
        'Plugin config' => [
          'add_description' => TRUE,
        ],
      ],
    ];
  }

}
