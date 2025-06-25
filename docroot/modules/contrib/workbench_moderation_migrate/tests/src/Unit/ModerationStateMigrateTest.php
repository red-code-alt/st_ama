<?php

namespace Drupal\Tests\workbench_moderation_migrate\Unit;

use Drupal\Tests\migrate\Unit\MigrateTestCase;
use Drupal\workbench_moderation_migrate\ModerationStateMigrate;

/**
 * Tests the WorkbenchModerationMigrate class.
 *
 * @coversDefaultClass \Drupal\workbench_moderation_migrate\ModerationStateMigrate
 *
 * @group workbench_moderation_migrate
 */
class ModerationStateMigrateTest extends MigrateTestCase {

  /**
   * A sorted history for testing simple getters.
   *
   * @const array[]
   */
  const TEST_HISTORY = [
    ['hid' => NULL, 'vid' => 1, 'stamp_calc' => 10, 'state' => 'draft'],
    ['hid' => NULL, 'vid' => 2, 'stamp_calc' => 20, 'state' => 'published'],
    ['hid' => NULL, 'vid' => 3, 'stamp_calc' => 30, 'state' => 'published'],
    ['hid' => NULL, 'vid' => 4, 'stamp_calc' => 40, 'state' => 'published'],
    ['hid' => 1, 'vid' => 5, 'stamp_calc' => 50, 'state' => 'draft'],
    ['hid' => 2, 'vid' => 6, 'stamp_calc' => 60, 'state' => 'published'],
    ['hid' => 3, 'vid' => 7, 'stamp_calc' => 70, 'state' => 'published'],
    ['hid' => NULL, 'vid' => 8, 'stamp_calc' => 80, 'state' => 'draft'],
    ['hid' => NULL, 'vid' => 9, 'stamp_calc' => 80, 'state' => 'published'],
    ['hid' => NULL, 'vid' => 8, 'stamp_calc' => 90, 'state' => 'draft'],
    ['hid' => NULL, 'vid' => 10, 'stamp_calc' => 90, 'state' => 'published'],
    ['hid' => 4, 'vid' => 11, 'stamp_calc' => 100, 'state' => 'published'],
  ];

  /**
   * Tests transition history getter.
   *
   * @param array[][] $source_db
   *   Test database records.
   * @param string|int $nid
   *   The node ID to pass to ::getAllTransitionOfMigratableRevisions.
   * @param array[] $expected
   *   The expected sorted history.
   *
   * @covers ::getAllTransitionOfMigratableRevisions
   *
   * @dataProvider providerGetAllTransitionOfMigratableRevisions
   */
  public function testGetAllTransitionOfMigratableRevisions(array $source_db, $nid, array $expected) {
    $reflector = new \ReflectionClass(ModerationStateMigrate::class);
    $method = $reflector->getMethod('getAllTransitionOfMigratableRevisions');
    $method->setAccessible(TRUE);

    $prop = $reflector->getProperty('applicableUnpublishedStates');
    $prop->setAccessible(TRUE);
    $prop->setValue([
      'foo_type' => 'computed_unpublished_state',
    ]);

    $this->assertEquals(
      $expected,
      $method->invoke(NULL, $this->getDatabase($source_db), $nid, 'foo_type')
    );
  }

  /**
   * Tests ::isPublishedClone.
   *
   * @param array[] $history
   *   History to test with.
   * @param string|int $revision_id
   *   The revision ID to check.
   * @param bool $expected
   *   Whether the provided revision ID must be identified as a clone or not.
   *
   * @covers ::isPublishedClone
   * @covers ::getLastTransitionRelativeToRevision
   *
   * @dataProvider providerIsPublishedClone
   */
  public function testIsPublishedClone(array $history, $revision_id, bool $expected) {
    $reflector = new \ReflectionClass(ModerationStateMigrate::class);
    // Get the last transition of the specified revision ID.
    $transition_getter = $reflector->getMethod('getLastTransitionRelativeToRevision');
    $transition_getter->setAccessible(TRUE);
    $test_transition = $transition_getter->invoke(NULL, $history, $revision_id, 'current');

    // Prepare the primarily tested method.
    $method = $reflector->getMethod('isPublishedClone');
    $method->setAccessible(TRUE);
    $this->assertEquals(
      $expected,
      $method->invoke(NULL, $history, $test_transition)
    );
  }

  /**
   * Data provider for ::testIsPublishedClone.
   *
   * @return array[]
   *   The test cases.
   */
  public function providerIsPublishedClone(): array {
    return [
      'Revision ID 1' => [
        'History' => self::TEST_HISTORY,
        'Revision ID' => 1,
        'Expectation' => FALSE,
      ],
      'Revision ID "2"' => [
        'History' => self::TEST_HISTORY,
        'Revision ID' => '2',
        'Expectation' => FALSE,
      ],
      'Revision ID 3' => [
        'History' => self::TEST_HISTORY,
        'Revision ID' => 3,
        'Expectation' => FALSE,
      ],
      'Revision ID "4"' => [
        'History' => self::TEST_HISTORY,
        'Revision ID' => '4',
        'Expectation' => FALSE,
      ],
      'Revision ID 5' => [
        'History' => self::TEST_HISTORY,
        'Revision ID' => 5,
        'Expectation' => FALSE,
      ],
      'Revision ID 6' => [
        'History' => self::TEST_HISTORY,
        'Revision ID' => 6,
        'Expectation' => TRUE,
      ],
      'Revision ID 7' => [
        'History' => self::TEST_HISTORY,
        'Revision ID' => 7,
        'Expectation' => FALSE,
      ],
      'Revision ID 8' => [
        'History' => self::TEST_HISTORY,
        'Revision ID' => 8,
        'Expectation' => FALSE,
      ],
      'Revision ID 9' => [
        'History' => self::TEST_HISTORY,
        'Revision ID' => 9,
        'Expectation' => TRUE,
      ],
      'Revision ID "10"' => [
        'History' => self::TEST_HISTORY,
        'Revision ID' => '10',
        'Expectation' => TRUE,
      ],
      'Revision ID 11' => [
        'History' => self::TEST_HISTORY,
        'Revision ID' => 11,
        'Expectation' => FALSE,
      ],
    ];
  }

  /**
   * Tests last transition getter.
   *
   * @param array[] $history
   *   History to test with.
   * @param string|int $vid
   *   The node revision ID argument to pass to the function.
   * @param string $which
   *   The "which" argument to pass to the function.
   * @param array|null $expected
   *   The expected result.
   *
   * @covers ::getLastTransitionRelativeToRevision
   * @covers ::getLastTransitions
   *
   * @dataProvider providerTestLastHistory
   */
  public function testGetLastTransitionRelativeToRevision(array $history, $vid, string $which, $expected) {
    $reflector = new \ReflectionClass(ModerationStateMigrate::class);
    $method = $reflector->getMethod('getLastTransitionRelativeToRevision');
    $method->setAccessible(TRUE);

    $this->assertEquals(
      $expected,
      $method->invoke(NULL, $history, $vid, $which)
    );
  }

  /**
   * Data provider for ::testGetLastTransitionRelativeToRevision.
   *
   * @return array[]
   *   The test cases.
   */
  public function providerTestLastHistory(): array {
    return [
      'Current - 1' => [
        'History' => self::TEST_HISTORY,
        'Rev ID' => 1,
        'Which' => 'current',
        'Expected' => [
          'hid' => NULL,
          'vid' => 1,
          'stamp_calc' => 10,
          'state' => 'draft',
        ],
      ],
      'Previous - 1' => [
        'History' => self::TEST_HISTORY,
        'Rev ID' => 1,
        'Which' => 'prev',
        'Expected' => NULL,
      ],
      'Next - 1' => [
        'History' => self::TEST_HISTORY,
        'Rev ID' => 1,
        'Which' => 'next',
        'Expected' => [
          'hid' => NULL,
          'vid' => 2,
          'stamp_calc' => 20,
          'state' => 'published',
        ],
      ],
      'Current - 5' => [
        'History' => self::TEST_HISTORY,
        'Rev ID' => 5,
        'Which' => 'current',
        'Expected' => [
          'hid' => 1,
          'vid' => 5,
          'stamp_calc' => 50,
          'state' => 'draft',
        ],
      ],
      'Next - 5' => [
        'History' => self::TEST_HISTORY,
        'Rev ID' => 5,
        'Which' => 'next',
        'Expected' => [
          'hid' => 2,
          'vid' => 6,
          'stamp_calc' => 60,
          'state' => 'published',
        ],
      ],
      'Previous - 5' => [
        'History' => self::TEST_HISTORY,
        'Rev ID' => 5,
        'Which' => 'prev',
        'Expected' => [
          'hid' => NULL,
          'vid' => 4,
          'stamp_calc' => 40,
          'state' => 'published',
        ],
      ],
      'Previous - 9' => [
        'History' => self::TEST_HISTORY,
        'Rev ID' => 9,
        'Which' => 'prev',
        'Expected' => [
          'hid' => NULL,
          'vid' => 8,
          'stamp_calc' => 90,
          'state' => 'draft',
        ],
      ],
      'Next - 7' => [
        'History' => self::TEST_HISTORY,
        'Rev ID' => 7,
        'Which' => 'next',
        'Expected' => [
          'hid' => NULL,
          'vid' => 8,
          'stamp_calc' => 90,
          'state' => 'draft',
        ],
      ],
      'Current - 11' => [
        'History' => self::TEST_HISTORY,
        'Rev ID' => 11,
        'Which' => 'current',
        'Expected' => [
          'hid' => 4,
          'vid' => 11,
          'stamp_calc' => 100,
          'state' => 'published',
        ],
      ],
      'Previous - 11' => [
        'History' => self::TEST_HISTORY,
        'Rev ID' => 11,
        'Which' => 'prev',
        'Expected' => [
          'hid' => NULL,
          'vid' => 10,
          'stamp_calc' => 90,
          'state' => 'published',
        ],
      ],
      'Next - 11' => [
        'History' => self::TEST_HISTORY,
        'Rev ID' => 11,
        'Which' => 'next',
        'Expected' => NULL,
      ],
    ];
  }

  /**
   * Data provider for ::testGetAllTransitionOfMigratableRevisions.
   *
   * @return array[]
   *   The test cases.
   */
  public function providerGetAllTransitionOfMigratableRevisions(): array {
    $node_1_db = [
      'node_revision' => [
        ['nid' => 1, 'vid' => 1, 'status' => 0, 'timestamp' => 100],
        ['nid' => 1, 'vid' => 2, 'status' => 1, 'timestamp' => 110],
      ],
      'workbench_moderation_node_history' => [
        [
          'hid' => 1,
          'vid' => 1,
          'nid' => 1,
          'stamp' => 90,
          'state' => 'needs_review',
        ],
        [
          'hid' => 2,
          'vid' => 1,
          'nid' => 1,
          'stamp' => 100,
          'state' => 'draft',
        ],
        [
          'hid' => 3,
          'vid' => 2,
          'nid' => 1,
          'stamp' => 110,
          'state' => 'published',
        ],
      ],
    ];
    return [
      'Node with some history' => [
        'DB' => $node_1_db,
        'Node ID' => 1,
        'Expected' => [
          [
            'hid' => '1',
            'vid' => '1',
            'nid' => '1',
            'stamp' => '90',
            'state' => 'needs_review',
            'status' => '0',
            'timestamp' => '100',
            'stamp_calc' => '90',
          ],
          [
            'hid' => '2',
            'vid' => '1',
            'nid' => '1',
            'stamp' => '100',
            'state' => 'draft',
            'status' => '0',
            'timestamp' => '100',
            'stamp_calc' => '100',
          ],
          [
            'hid' => '3',
            'vid' => '2',
            'nid' => '1',
            'stamp' => '110',
            'state' => 'published',
            'status' => '1',
            'timestamp' => '110',
            'stamp_calc' => '110',
          ],
        ],
      ],
      'No matching node' => [
        'DB' => $node_1_db,
        'Node ID' => 2,
        'Expected' => [],
      ],
      'No history for earlier revisions' => [
        'DB' => [
          'node_revision' => [
            ['nid' => 1, 'vid' => 1, 'status' => 0, 'timestamp' => 10],
            ['nid' => 1, 'vid' => 2, 'status' => 1, 'timestamp' => 20],
            ['nid' => 1, 'vid' => 3, 'status' => 1, 'timestamp' => 30],
            ['nid' => 1, 'vid' => 4, 'status' => 1, 'timestamp' => 40],
          ],
          'workbench_moderation_node_history' => [
            [
              'hid' => 1,
              'vid' => 3,
              'nid' => 1,
              'stamp' => 30,
              'state' => 'draft',
            ],
            [
              'hid' => 2,
              'vid' => 4,
              'nid' => 1,
              'stamp' => 40,
              'state' => 'published',
            ],
          ],
        ],
        'Node ID' => 1,
        'Expected' => [
          [
            'hid' => NULL,
            'vid' => '1',
            'nid' => '1',
            'stamp' => NULL,
            'state' => 'computed_unpublished_state',
            'status' => '0',
            'timestamp' => '10',
            'stamp_calc' => '10',
          ],
          [
            'hid' => NULL,
            'vid' => '2',
            'nid' => '1',
            'stamp' => NULL,
            'state' => 'published',
            'status' => '1',
            'timestamp' => '20',
            'stamp_calc' => '20',
          ],
          [
            'hid' => '1',
            'vid' => '3',
            'nid' => '1',
            'stamp' => '30',
            'state' => 'draft',
            'status' => '1',
            'timestamp' => '30',
            'stamp_calc' => '30',
          ],
          [
            'hid' => '2',
            'vid' => '4',
            'nid' => '1',
            'stamp' => '40',
            'state' => 'published',
            'status' => '1',
            'timestamp' => '40',
            'stamp_calc' => '40',
          ],
        ],
      ],
      'History is missing from the middle' => [
        'DB' => [
          'node_revision' => [
            ['nid' => 1, 'vid' => 1, 'status' => 0, 'timestamp' => 10],
            ['nid' => 1, 'vid' => 2, 'status' => 1, 'timestamp' => 20],
            ['nid' => 1, 'vid' => 3, 'status' => 1, 'timestamp' => 30],
            ['nid' => 1, 'vid' => 4, 'status' => 1, 'timestamp' => 40],
            ['nid' => 1, 'vid' => 5, 'status' => 1, 'timestamp' => 40],
            ['nid' => 1, 'vid' => 6, 'status' => 1, 'timestamp' => 60],
            ['nid' => 1, 'vid' => 7, 'status' => 0, 'timestamp' => 70],
          ],
          'workbench_moderation_node_history' => [
            [
              'hid' => 1,
              'vid' => 2,
              'nid' => 1,
              'stamp' => 20,
              'state' => 'published',
            ],
            [
              'hid' => 2,
              'vid' => 3,
              'nid' => 1,
              'stamp' => 30,
              'state' => 'published',
            ],
            [
              'hid' => 13,
              'vid' => 6,
              'nid' => 1,
              'stamp' => 60,
              'state' => 'draft',
            ],
            [
              'hid' => 14,
              'vid' => 7,
              'nid' => 1,
              'stamp' => 70,
              'state' => 'published',
            ],
            [
              'hid' => 15,
              'vid' => 7,
              'nid' => 1,
              'stamp' => 70,
              'state' => 'unpublished',
            ],
          ],
        ],
        'Node ID' => 1,
        'Expected' => [
          [
            'hid' => NULL,
            'vid' => '1',
            'nid' => '1',
            'stamp' => NULL,
            'state' => 'computed_unpublished_state',
            'status' => '0',
            'timestamp' => '10',
            'stamp_calc' => '10',
          ],
          [
            'hid' => '1',
            'vid' => '2',
            'nid' => '1',
            'stamp' => '20',
            'state' => 'published',
            'status' => '1',
            'timestamp' => '20',
            'stamp_calc' => '20',
          ],
          [
            'hid' => '2',
            'vid' => '3',
            'nid' => '1',
            'stamp' => '30',
            'state' => 'published',
            'status' => '1',
            'timestamp' => '30',
            'stamp_calc' => '30',
          ],
          [
            'hid' => NULL,
            'vid' => '4',
            'nid' => '1',
            'stamp' => NULL,
            'state' => 'computed_unpublished_state',
            'status' => '1',
            'timestamp' => '40',
            'stamp_calc' => '40',
          ],
          [
            'hid' => NULL,
            'vid' => '5',
            'nid' => '1',
            'stamp' => NULL,
            'state' => 'published',
            'status' => '1',
            'timestamp' => '40',
            'stamp_calc' => '40',
          ],
          [
            'hid' => '13',
            'vid' => '6',
            'nid' => '1',
            'stamp' => '60',
            'state' => 'draft',
            'status' => '1',
            'timestamp' => '60',
            'stamp_calc' => '60',
          ],
          [
            'hid' => '14',
            'vid' => '7',
            'nid' => '1',
            'stamp' => '70',
            'state' => 'published',
            'status' => '0',
            'timestamp' => '70',
            'stamp_calc' => '70',
          ],
          [
            'hid' => '15',
            'vid' => '7',
            'nid' => '1',
            'stamp' => '70',
            'state' => 'unpublished',
            'status' => '0',
            'timestamp' => '70',
            'stamp_calc' => '70',
          ],
        ],
      ],
    ];
  }

}
