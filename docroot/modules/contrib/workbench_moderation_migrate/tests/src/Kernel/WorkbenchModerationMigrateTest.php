<?php

namespace Drupal\Tests\workbench_moderation_migrate\Kernel;

use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\migrate_drupal\NodeMigrateType;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\Tests\migmag\Traits\MigMagKernelTestDxTrait;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;
use Drupal\Tests\workbench_moderation_migrate\Kernel\Plugin\migrate\source\WorkbenchModerationFlowTest;
use Drupal\Tests\workbench_moderation_migrate\Traits\WorkbenchModerationAssertionsTrait;
use Drupal\Tests\workbench_moderation_migrate\Traits\WorkbenchModerationTestToolsTrait;

/**
 * Tests Workbench Moderation flow and the changed node complete migrations.
 *
 * @group workbench_moderation_migrate
 */
class WorkbenchModerationMigrateTest extends MigrateDrupal7TestBase {

  use MigMagKernelTestDxTrait;
  use WorkbenchModerationTestToolsTrait;
  use WorkbenchModerationAssertionsTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'content_moderation',
    'filter',
    'menu_ui',
    'node',
    'text',
    'workbench_moderation_migrate',
    'workflows',
    'migmag_callback_upgrade',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getFixtureFilePath() {
    return implode(DIRECTORY_SEPARATOR, [
      drupal_get_path('module', 'workbench_moderation_migrate'),
      'tests',
      'fixtures',
      'wm-drupal7.php',
    ]);
  }

  /**
   * Prepares tests: remove node migrate table and install configs and schema.
   */
  protected function prepareTest(): void {
    $this->removeNodeMigrateMapTable(NodeMigrateType::NODE_MIGRATE_TYPE_CLASSIC, '7');

    $this->installConfig(['node']);
    $this->installEntitySchema('node');
    $this->installSchema('node', ['node_access']);
    $this->installEntitySchema('content_moderation_state');
  }

  /**
   * Executes all workbench moderation related migrations.
   */
  protected function executeWorkbenchRelatedMigrations(): void {
    $this->startCollectingMessages();
    $this->executeMigrations([
      'd7_node_type',
      'd7_user_role',
      'd7_user',
    ]);
    $this->assertNoMigrationMessages();

    $this->startCollectingMessages();
    $this->executeMigrations(['workbench_moderation_flow']);
    $this->assertNoMigrationMessages();

    $this->startCollectingMessages();
    $this->executeMigrations(['d7_node_complete']);
    $this->assertNoMigrationMessages();
  }

  /**
   * Tests the "workbench_moderation_flow" migration.
   */
  public function testWorkbenchModerationFlowMigration() {
    $this->prepareTest();

    $this->startCollectingMessages();
    $this->executeMigrations(['d7_node_type']);
    $this->executeMigrations(['workbench_moderation_flow']);
    $this->assertNoMigrationMessages();

    $workflow_storage = $this->container->get('entity_type.manager')->getStorage('workflow');
    assert($workflow_storage instanceof ConfigEntityStorageInterface);
    $workflow_ids = array_keys($workflow_storage->loadMultiple());
    $this->assertEquals(
      [
        'editorial_with_draft_default_state',
      ],
      $workflow_ids
    );

    $this->assertEditorialWithDraftDefaultStateWorkflow();
  }

  /**
   * Test moderated content migration with and without missing source node revs.
   *
   * @param bool $with_missing_node_revisions
   *   Whether the test should be performed with complete or corrupted node
   *   revision data.
   *
   * @dataProvider providerTest
   */
  public function testWorkbenchModerationMigrations(bool $with_missing_node_revisions) {
    if ($with_missing_node_revisions) {
      $this->deleteSourceNodeRevisions();
    }

    $this->prepareTest();
    $this->executeWorkbenchRelatedMigrations();

    $this->assertNode1RevisionStates($with_missing_node_revisions);
    $this->assertNode2RevisionStates($with_missing_node_revisions);
    $this->assertNode3RevisionStates($with_missing_node_revisions);
    $this->assertNode4RevisionStates($with_missing_node_revisions);
    $this->assertNode5RevisionStates($with_missing_node_revisions);
  }

  /**
   * Tests the weirdest cases found at customers.
   *
   * @param array $source_data
   *   The data to set in the source DB (array of SQL records keyed by Drupal 7
   *   table name).
   * @param array $expected_states
   *   The expected state of the specified nodes after the migration.
   *
   * @dataProvider awkwardCases
   */
  public function testWithAwkwardCases(array $source_data, array $expected_states) {
    foreach ($source_data as $table_name => $records) {
      $this->sourceDatabase->truncate($table_name)->execute();
      if (empty($records)) {
        continue;
      }
      $insert = $this->sourceDatabase
        ->insert($table_name)
        ->fields(array_keys(reset($records)));
      foreach ($records as $record) {
        $insert->values($record);
      }
      $insert->execute();
    }

    $this->prepareTest();
    $this->executeWorkbenchRelatedMigrations();

    foreach ($expected_states as $node_id => $expectations) {
      $node = Node::load($node_id);
      assert($node instanceof NodeInterface);

      $this->assertEquals($expectations['published'], $node->isPublished());
      $this->assertModerationStates(
        'node',
        $expectations['states'],
        $node->id()
      );
    }
  }

  /**
   * Data provider for ::testWithAwkwardCases.
   *
   * @return array[]
   *   The test cases.
   */
  public function awkwardCases(): array {
    return [
      'Node saved before flow was applied (1086)' => [
        'DB' => [
          'node' => [
            [
              'nid' => 1086,
              'vid' => 40046,
              'type' => 'page',
              'language' => 'en',
              'title' => 'Node #1086 title',
              'uid' => 1,
              'status' => 1,
              'created' => 1466710020,
              'changed' => 1475066038,
              'comment' => 1,
              'promote' => 0,
              'sticky' => 0,
              'tnid' => 0,
              'translate' => 0,
            ],
          ],
          'node_revision' => [
            [
              'nid' => 1086,
              'vid' => 1111,
              'uid' => 1,
              'title' => 'Node #1086 title',
              'log' => '',
              'timestamp' => 1468425064,
              'status' => 1,
              'comment' => 1,
              'promote' => 0,
              'sticky' => 0,
            ],
            [
              'nid' => 1086,
              'vid' => 28951,
              'uid' => 0,
              'title' => 'Node #1086 title',
              'log' => 'rcasarez replaced <em class=\"placeholder\">&lt;/a&gt;</em> with <em class=\"placeholder\"></em> via Scanner Search and Replace module.',
              'timestamp' => 1470860905,
              'status' => 1,
              'comment' => 1,
              'promote' => 0,
              'sticky' => 0,
            ],
            [
              'nid' => 1086,
              'vid' => 40046,
              'uid' => 1,
              'title' => 'Node #1086 title',
              'log' => 'rcasarez replaced <em class=\"placeholder\">&lt;/a&gt;</em> with <em class=\"placeholder\"> </em> via Scanner Search and Replace module.',
              'timestamp' => 1475066038,
              'status' => 1,
              'comment' => 1,
              'promote' => 0,
              'sticky' => 0,
            ],
          ],
          'workbench_moderation_node_history' => [],
          'workbench_moderation_states' => WorkbenchModerationFlowTest::WORKBENCH_MODERATION_STATES,
          'workbench_moderation_transitions' => WorkbenchModerationFlowTest::WORKBENCH_MODERATION_TRANSITIONS,
        ],
        'Expected' => [
          1086 => [
            'published' => TRUE,
            'states' => [
              1111 => [1, 'published', 1],
              28951 => [1, 'published', 1],
              40046 => [1, 'published', 1],
            ],
          ],
        ],
      ],

      'Only the last revision has moderation info (1571)' => [
        'DB' => [
          'node' => [
            [
              'nid' => 1571,
              'vid' => 40141,
              'type' => 'page',
              'language' => 'en',
              'title' => 'Node #1571 title',
              'uid' => 1,
              'status' => 1,
              'created' => 1468352824,
              'changed' => 1590902101,
              'comment' => 1,
              'promote' => 0,
              'sticky' => 0,
              'tnid' => 0,
              'translate' => 0,
            ],
          ],
          'node_revision' => [
            [
              'nid' => 1571,
              'vid' => 1636,
              'uid' => 1,
              'title' => 'Node #1571 title',
              'log' => '',
              'timestamp' => 1468352824,
              'status' => 1,
              'comment' => 1,
              'promote' => 0,
              'sticky' => 0,
            ],
            [
              'nid' => 1571,
              'vid' => 29046,
              'uid' => 0,
              'title' => 'Node #1571 title',
              'log' => 'rcasarez replaced <em class=\"placeholder\">&lt;/a&gt;</em> with <em class=\"placeholder\"></em> via Scanner Search and Replace module.',
              'timestamp' => 1470860905,
              'status' => 1,
              'comment' => 1,
              'promote' => 0,
              'sticky' => 0,
            ],
            [
              'nid' => 1571,
              'vid' => 40141,
              'uid' => 0,
              'title' => 'Node #1571 title',
              'log' => 'rcasarez replaced <em class=\"placeholder\">&lt;/a&gt;</em> with <em class=\"placeholder\"> </em> via Scanner Search and Replace module.',
              'timestamp' => 1590902101,
              'status' => 1,
              'comment' => 1,
              'promote' => 0,
              'sticky' => 0,
            ],
          ],
          'workbench_moderation_node_history' => [
            [
              'hid' => 603506,
              'vid' => 40141,
              'nid' => 1571,
              'from_state' => 'published',
              'state' => 'published',
              'uid' => 0,
              'stamp' => 1590902101,
              'published' => 1,
              'is_current' => 1,
            ],
          ],
          'workbench_moderation_states' => WorkbenchModerationFlowTest::WORKBENCH_MODERATION_STATES,
          'workbench_moderation_transitions' => WorkbenchModerationFlowTest::WORKBENCH_MODERATION_TRANSITIONS,
        ],
        'Expected' => [
          1571 => [
            'published' => TRUE,
            'states' => [
              1636 => [1, 'published', 1],
              29046 => [1, 'published', 1],
              40141 => [1, 'published', 1],
            ],
          ],
        ],
      ],

      'Only the first rev lacks its history (176951)' => [
        'DB' => [
          'node' => [
            [
              'nid' => 176951,
              'vid' => 597401,
              'type' => 'page',
              'language' => 'en',
              'title' => 'Node #176951 title',
              'uid' => 3,
              'status' => 1,
              'created' => 1532526840,
              'changed' => 1535483990,
              'comment' => 1,
              'promote' => 0,
              'sticky' => 0,
              'tnid' => 0,
              'translate' => 0,
            ],
          ],
          'node_revision' => [
            [
              'nid' => 176951,
              'vid' => 584591,
              'uid' => 1,
              'title' => 'Node #176951 title',
              'log' => '',
              'timestamp' => 1532719866,
              'status' => 1,
              'comment' => 1,
              'promote' => 0,
              'sticky' => 0,
            ],
            [
              'nid' => 176951,
              'vid' => 597296,
              'uid' => 2,
              'title' => 'Node #176951 title',
              'log' => 'Edited by arturo.\r\n  HHSC-6312',
              'timestamp' => 1535473727,
              'status' => 1,
              'comment' => 1,
              'promote' => 0,
              'sticky' => 0,
            ],
            [
              'nid' => 176951,
              'vid' => 597301,
              'uid' => 2,
              'title' => 'Node #176951 title',
              'log' => '',
              'timestamp' => 1535473727,
              'status' => 1,
              'comment' => 1,
              'promote' => 0,
              'sticky' => 0,
            ],
            [
              'nid' => 176951,
              'vid' => 597401,
              'uid' => 2,
              'title' => 'Node #176951 title',
              'log' => 'Edited by arturo.',
              'timestamp' => 1535483990,
              'status' => 1,
              'comment' => 1,
              'promote' => 0,
              'sticky' => 0,
            ],
          ],
          'workbench_moderation_node_history' => [
            [
              'hid' => 382481,
              'vid' => 597296,
              'nid' => 176951,
              'from_state' => 'published',
              'state' => 'draft',
              'uid' => 2,
              'stamp' => 1535473727,
              'published' => 0,
              'is_current' => 0,
            ],
            [
              'hid' => 382486,
              'vid' => 597301,
              'nid' => 176951,
              'from_state' => 'draft',
              'state' => 'published',
              'uid' => 2,
              'stamp' => 1535473727,
              'published' => 0,
              'is_current' => 0,
            ],
            [
              'hid' => 382596,
              'vid' => 597401,
              'nid' => 176951,
              'from_state' => 'draft',
              'state' => 'published',
              'uid' => 2,
              'stamp' => 1535483990,
              'published' => 1,
              'is_current' => 1,
            ],
          ],
          'workbench_moderation_states' => WorkbenchModerationFlowTest::WORKBENCH_MODERATION_STATES,
          'workbench_moderation_transitions' => WorkbenchModerationFlowTest::WORKBENCH_MODERATION_TRANSITIONS,
        ],
        'Expected' => [
          176951 => [
            'published' => TRUE,
            'states' => [
              584591 => [1, 'published', 1],
              597296 => [0, 'draft', 0],
              597401 => [1, 'published', 1],
            ],
          ],
        ],
      ],

      'First 7 revs deleted, first migratable rev lacks its history (272566)' => [
        'DB' => [
          'node' => [
            [
              'nid' => 272566,
              'vid' => 857596,
              'type' => 'page',
              'language' => 'en',
              'title' => 'Node #272566 title',
              'uid' => 4,
              'status' => 1,
              'created' => 1593696180,
              'changed' => 1594927341,
              'comment' => 0,
              'promote' => 0,
              'sticky' => 0,
              'tnid' => 0,
              'translate' => 0,
            ],
          ],
          'node_revision' => [
            [
              'nid' => 272566,
              'vid' => 857546,
              'uid' => 2,
              'title' => 'Node #272566 title',
              'log' => 'Edited by mdeleon. HHSC-12301\r\n\r\n',
              'timestamp' => 1594922101,
              'status' => 1,
              'comment' => 0,
              'promote' => 0,
              'sticky' => 0,
            ],
            [
              'nid' => 272566,
              'vid' => 857551,
              'uid' => 2,
              'title' => 'Node #272566 title',
              'log' => 'Edited by amcelwee.',
              'timestamp' => 1594922590,
              'status' => 1,
              'comment' => 0,
              'promote' => 0,
              'sticky' => 0,
            ],
            [
              'nid' => 272566,
              'vid' => 857556,
              'uid' => 2,
              'title' => 'Node #272566 title',
              'log' => 'Edited by mdeleon. HHSC-12301\r\n\r\n',
              'timestamp' => 1594922590,
              'status' => 1,
              'comment' => 0,
              'promote' => 0,
              'sticky' => 0,
            ],
            [
              'nid' => 272566,
              'vid' => 857561,
              'uid' => 3,
              'title' => 'Node #272566 title',
              'log' => 'Edited by mbrown per QA of 12385 -- linked agenda item 11.',
              'timestamp' => 1594923786,
              'status' => 1,
              'comment' => 0,
              'promote' => 0,
              'sticky' => 0,
            ],
            [
              'nid' => 272566,
              'vid' => 857566,
              'uid' => 3,
              'title' => 'Node #272566 title',
              'log' => 'Edited by mdeleon. HHSC-12301\r\n\r\n',
              'timestamp' => 1594923786,
              'status' => 1,
              'comment' => 0,
              'promote' => 0,
              'sticky' => 0,
            ],
            [
              'nid' => 272566,
              'vid' => 857596,
              'uid' => 2,
              'title' => 'Node #272566 title',
              'log' => 'Edited by amcelwee.',
              'timestamp' => 1594927341,
              'status' => 1,
              'comment' => 0,
              'promote' => 0,
              'sticky' => 0,
            ],
          ],
          'workbench_moderation_node_history' => [
            [
              'hid' => 614656,
              'vid' => 853936,
              'nid' => 272566,
              'from_state' => 'draft',
              'state' => 'draft',
              'uid' => 4,
              'stamp' => 1593696181,
              'published' => 0,
              'is_current' => 0,
            ],
            [
              'hid' => 614661,
              'vid' => 853941,
              'nid' => 272566,
              'from_state' => 'draft',
              'state' => 'draft',
              'uid' => 4,
              'stamp' => 1593696273,
              'published' => 0,
              'is_current' => 0,
            ],
            [
              'hid' => 614716,
              'vid' => 853986,
              'nid' => 272566,
              'from_state' => 'draft',
              'state' => 'published',
              'uid' => 4,
              'stamp' => 1593702337,
              'published' => 0,
              'is_current' => 0,
            ],
            [
              'hid' => 618496,
              'vid' => 857356,
              'nid' => 272566,
              'from_state' => 'published',
              'state' => 'draft',
              'uid' => 2,
              'stamp' => 1594917973,
              'published' => 0,
              'is_current' => 0,
            ],
            [
              'hid' => 618501,
              'vid' => 857361,
              'nid' => 272566,
              'from_state' => 'published',
              'state' => 'published',
              'uid' => 2,
              'stamp' => 1594917973,
              'published' => 0,
              'is_current' => 0,
            ],
            [
              'hid' => 618571,
              'vid' => 857431,
              'nid' => 272566,
              'from_state' => 'draft',
              'state' => 'draft',
              'uid' => 2,
              'stamp' => 1594920521,
              'published' => 0,
              'is_current' => 0,
            ],
            [
              'hid' => 618576,
              'vid' => 857436,
              'nid' => 272566,
              'from_state' => 'published',
              'state' => 'published',
              'uid' => 2,
              'stamp' => 1594920521,
              'published' => 0,
              'is_current' => 0,
            ],
            [
              'hid' => 618676,
              'vid' => 857551,
              'nid' => 272566,
              'from_state' => 'draft',
              'state' => 'draft',
              'uid' => 2,
              'stamp' => 1594922590,
              'published' => 0,
              'is_current' => 0,
            ],
            [
              'hid' => 618681,
              'vid' => 857556,
              'nid' => 272566,
              'from_state' => 'draft',
              'state' => 'published',
              'uid' => 2,
              'stamp' => 1594922590,
              'published' => 0,
              'is_current' => 0,
            ],
            [
              'hid' => 618686,
              'vid' => 857561,
              'nid' => 272566,
              'from_state' => 'draft',
              'state' => 'draft',
              'uid' => 3,
              'stamp' => 1594923786,
              'published' => 0,
              'is_current' => 0,
            ],
            [
              'hid' => 618691,
              'vid' => 857566,
              'nid' => 272566,
              'from_state' => 'published',
              'state' => 'published',
              'uid' => 3,
              'stamp' => 1594923786,
              'published' => 0,
              'is_current' => 0,
            ],
            [
              'hid' => 618716,
              'vid' => 857596,
              'nid' => 272566,
              'from_state' => 'draft',
              'state' => 'published',
              'uid' => 2,
              'stamp' => 1594927341,
              'published' => 1,
              'is_current' => 1,
            ],
          ],
          'workbench_moderation_states' => WorkbenchModerationFlowTest::WORKBENCH_MODERATION_STATES,
          'workbench_moderation_transitions' => WorkbenchModerationFlowTest::WORKBENCH_MODERATION_TRANSITIONS,
        ],
        'Expected' => [
          272566 => [
            'published' => TRUE,
            'states' => [
              857546 => [1, 'published', 1],
              857551 => [0, 'draft', 0],
              857561 => [0, 'draft', 0],
              857596 => [1, 'published', 1],
            ],
          ],
        ],
      ],

      'First 5 revs deleted, node was unpublished, last transitions are funky (242276)' => [
        'DB' => [
          'node' => [
            [
              'nid' => 242276,
              'vid' => 725896,
              'type' => 'page',
              'language' => 'en',
              'title' => 'Node #242276 title',
              'uid' => 3,
              'status' => 0,
              'created' => 1559666880,
              'changed' => 1591246801,
              'comment' => 0,
              'promote' => 0,
              'sticky' => 0,
              'tnid' => 0,
              'translate' => 0,
            ],
          ],
          'node_revision' => [
            [
              'nid' => 242276,
              'vid' => 725571,
              'uid' => 2,
              'title' => 'Node #242276 title',
              'log' => 'Edited by arturo.',
              'timestamp' => '1560198337',
              'status' => 1,
              'comment' => 0,
              'promote' => 0,
              'sticky' => 0,
            ],
            [
              'nid' => 242276,
              'vid' => 725576,
              'uid' => 2,
              'title' => 'Node #242276 title',
              'log' => 'Edited by arturo.',
              'timestamp' => '1560198337',
              'status' => 1,
              'comment' => 0,
              'promote' => 0,
              'sticky' => 0,
            ],
            [
              'nid' => 242276,
              'vid' => 725841,
              'uid' => 1,
              'title' => 'Node #242276 title',
              'log' => 'Edited by arturo.',
              'timestamp' => '1560277830',
              'status' => 1,
              'comment' => 0,
              'promote' => 0,
              'sticky' => 0,
            ],
            [
              'nid' => 242276,
              'vid' => 725836,
              'uid' => 1,
              'title' => 'Node #242276 title',
              'log' => 'Edited by ericv.',
              'timestamp' => '1560277830',
              'status' => 1,
              'comment' => 0,
              'promote' => 0,
              'sticky' => 0,
            ],
            [
              'nid' => 242276,
              'vid' => 725896,
              'uid' => 0,
              'title' => 'Node #242276 title',
              'log' => 'Edited by arturo.',
              'timestamp' => '1591246801',
              'status' => 0,
              'comment' => 0,
              'promote' => 0,
              'sticky' => 0,
            ],
          ],
          'workbench_moderation_node_history' => [
            [
              'hid' => 482061,
              'vid' => 723576,
              'nid' => 242276,
              'from_state' => 'draft',
              'state' => 'draft',
              'uid' => 3,
              'stamp' => 1559666891,
              'published' => 0,
              'is_current' => 0,
            ],
            [
              'hid' => 482226,
              'vid' => 723691,
              'nid' => 242276,
              'from_state' => 'draft',
              'state' => 'published',
              'uid' => 3,
              'stamp' => 1559675137,
              'published' => 0,
              'is_current' => 0,
            ],
            [
              'hid' => 483156,
              'vid' => 724521,
              'nid' => 242276,
              'from_state' => 'published',
              'state' => 'draft',
              'uid' => 2,
              'stamp' => 1559844610,
              'published' => 0,
              'is_current' => 0,
            ],
            [
              'hid' => 483161,
              'vid' => 724526,
              'nid' => 242276,
              'from_state' => 'published',
              'state' => 'published',
              'uid' => 2,
              'stamp' => 1559844610,
              'published' => 0,
              'is_current' => 0,
            ],
            [
              'hid' => 483326,
              'vid' => 724686,
              'nid' => 242276,
              'from_state' => 'draft',
              'state' => 'published',
              'uid' => 2,
              'stamp' => 1559853292,
              'published' => 0,
              'is_current' => 0,
            ],
            [
              'hid' => 484031,
              'vid' => 725571,
              'nid' => 242276,
              'from_state' => 'published',
              'state' => 'draft',
              'uid' => 2,
              'stamp' => 1560198337,
              'published' => 0,
              'is_current' => 0,
            ],
            [
              'hid' => 484036,
              'vid' => 725576,
              'nid' => 242276,
              'from_state' => 'published',
              'state' => 'published',
              'uid' => 2,
              'stamp' => 1560198337,
              'published' => 0,
              'is_current' => 0,
            ],
            [
              'hid' => 484356,
              'vid' => 725836,
              'nid' => 242276,
              'from_state' => 'draft',
              'state' => 'draft',
              'uid' => 1,
              'stamp' => 1560277830,
              'published' => 0,
              'is_current' => 0,
            ],
            [
              'hid' => 484361,
              'vid' => 725841,
              'nid' => 242276,
              'from_state' => 'published',
              'state' => 'published',
              'uid' => 1,
              'stamp' => 1560277830,
              'published' => 0,
              'is_current' => 0,
            ],
            [
              'hid' => 484426,
              'vid' => 725896,
              'nid' => 242276,
              'from_state' => 'draft',
              'state' => 'published',
              'uid' => 2,
              'stamp' => 1560284419,
              'published' => 0,
              'is_current' => 0,
            ],
            [
              'hid' => 604981,
              'vid' => 725896,
              'nid' => 242276,
              'from_state' => 'published',
              'state' => 'unpublished',
              'uid' => 0,
              'stamp' => 1591246801,
              'published' => 0,
              'is_current' => 0,
            ],
            [
              'hid' => 604986,
              'vid' => 725896,
              'nid' => 242276,
              'from_state' => 'published',
              'state' => 'unpublished',
              'uid' => 0,
              'stamp' => 1591246801,
              'published' => 0,
              'is_current' => 1,
            ],
          ],
          'workbench_moderation_states' => WorkbenchModerationFlowTest::WORKBENCH_MODERATION_STATES,
          'workbench_moderation_transitions' => WorkbenchModerationFlowTest::WORKBENCH_MODERATION_TRANSITIONS,
        ],
        'Expected' => [
          242276 => [
            'published' => FALSE,
            'states' => [
              725576 => [1, 'published', 1],
              725836 => [0, 'draft', 0],
              725896 => [0, 'archive', 1],
            ],
          ],
        ],
      ],

      'First 3 revs deleted, 2 revs missing its history from the middle (272911)' => [
        'DB' => [
          'node' => [
            [
              'nid' => 272911,
              'vid' => 867486,
              'type' => 'page',
              'language' => 'en',
              'title' => 'Node #272911 title',
              'uid' => 4,
              'status' => 1,
              'created' => 1594667700,
              'changed' => 1595335965,
              'comment' => 0,
              'promote' => 0,
              'sticky' => 0,
              'tnid' => 0,
              'translate' => 0,
            ],
          ],
          'node_revision' => [
            [
              'nid' => 272911,
              'vid' => 856456,
              'uid' => 2,
              'title' => 'Node #272911 title',
              'log' => 'Edited by arturo.',
              'timestamp' => 1594749730,
              'status' => 1,
              'comment' => 0,
              'promote' => 0,
              'sticky' => 0,
            ],
            [
              'nid' => 272911,
              'vid' => 856346,
              'uid' => 2,
              'title' => 'Node #272911 title',
              'log' => 'Edited by mdeleon. HHSC-12433\r\n\r\n',
              'timestamp' => 1594740640,
              'status' => 1,
              'comment' => 0,
              'promote' => 0,
              'sticky' => 0,
            ],
            [
              'nid' => 272911,
              'vid' => 867476,
              'uid' => 3,
              'title' => 'Node #272911 title',
              'log' => 'HHSC-12527',
              'timestamp' => 1595276310,
              'status' => 1,
              'comment' => 0,
              'promote' => 0,
              'sticky' => 0,
            ],
            [
              'nid' => 272911,
              'vid' => 867481,
              'uid' => 3,
              'title' => 'Node #272911 title',
              'log' => 'Edited by arturo.',
              'timestamp' => 1595276310,
              'status' => 1,
              'comment' => 0,
              'promote' => 0,
              'sticky' => 0,
            ],
            [
              'nid' => 272911,
              'vid' => 867486,
              'uid' => 3,
              'title' => 'Node #272911 title',
              'log' => 'HHSC-12527',
              'timestamp' => 1595335965,
              'status' => 1,
              'comment' => 0,
              'promote' => 0,
              'sticky' => 0,
            ],
            [
              'nid' => 272911,
              'vid' => 867491,
              'uid' => 3,
              'title' => 'Node #272911 title',
              'log' => 'Edited by arturo.',
              'timestamp' => 1595276361,
              'status' => 1,
              'comment' => 0,
              'promote' => 0,
              'sticky' => 0,
            ],
          ],
          'workbench_moderation_node_history' => [
            [
              'hid' => 617236,
              'vid' => 856166,
              'nid' => 272911,
              'from_state' => 'draft',
              'state' => 'draft',
              'uid' => 4,
              'stamp' => 1594667754,
              'published' => 0,
              'is_current' => 0,
            ],
            [
              'hid' => 617261,
              'vid' => 856191,
              'nid' => 272911,
              'from_state' => 'draft',
              'state' => 'published',
              'uid' => 4,
              'stamp' => 1594670787,
              'published' => 0,
              'is_current' => 0,
            ],
            [
              'hid' => 617456,
              'vid' => 856341,
              'nid' => 272911,
              'from_state' => 'published',
              'state' => 'draft',
              'uid' => 2,
              'stamp' => 1594740640,
              'published' => 0,
              'is_current' => 0,
            ],
            [
              'hid' => 617461,
              'vid' => 856346,
              'nid' => 272911,
              'from_state' => 'published',
              'state' => 'published',
              'uid' => 2,
              'stamp' => 1594740640,
              'published' => 0,
              'is_current' => 0,
            ],
            [
              'hid' => 617576,
              'vid' => 856456,
              'nid' => 272911,
              'from_state' => 'draft',
              'state' => 'published',
              'uid' => 2,
              'stamp' => 1594749730,
              'published' => 0,
              'is_current' => 0,
            ],
            [
              'hid' => 628506,
              'vid' => 867486,
              'nid' => 272911,
              'from_state' => 'published',
              'state' => 'draft',
              'uid' => 3,
              'stamp' => 1595276361,
              'published' => 0,
              'is_current' => 0,
            ],
            [
              'hid' => 628511,
              'vid' => 867491,
              'nid' => 272911,
              'from_state' => 'draft',
              'state' => 'published',
              'uid' => 3,
              'stamp' => 1595276361,
              'published' => 0,
              'is_current' => 0,
            ],
            [
              'hid' => 629726,
              'vid' => 867486,
              'nid' => 272911,
              'from_state' => 'draft',
              'state' => 'published',
              'uid' => 3,
              'stamp' => 1595335965,
              'published' => 1,
              'is_current' => 1,
            ],
          ],
          'workbench_moderation_states' => WorkbenchModerationFlowTest::WORKBENCH_MODERATION_STATES,
          'workbench_moderation_transitions' => WorkbenchModerationFlowTest::WORKBENCH_MODERATION_TRANSITIONS,
        ],
        'Expected' => [
          272911 => [
            'published' => TRUE,
            'states' => [
              856346 => [1, 'published', 1],
              856456 => [1, 'published', 1],
              867476 => [0, 'draft', 0],
              867486 => [1, 'published', 1],
            ],
          ],
        ],
      ],

      'Highest rev ID is not the current published revision (274511)' => [
        'DB' => [
          'node' => [
            [
              'nid' => 274511,
              'vid' => 959631,
              'type' => 'page',
              'language' => 'en',
              'title' => 'Node #274511 title',
              'uid' => 1,
              'status' => 1,
              'created' => 1500000000,
              'changed' => 1619807861,
              'comment' => 0,
              'promote' => 0,
              'sticky' => 0,
              'tnid' => 0,
              'translate' => 0,
            ],
          ],
          'node_revision' => [
            [
              'nid' => 274511,
              'vid' => 959631,
              'uid' => 1,
              'title' => 'Node #274511 title',
              'log' => 'Current',
              'timestamp' => 1619807861,
              'status' => 1,
              'comment' => 0,
              'promote' => 0,
              'sticky' => 0,
            ],
            [
              'nid' => 274511,
              'vid' => 959636,
              'uid' => 1,
              'title' => 'Node #274511 title',
              'log' => 'Previous with higher rev',
              'timestamp' => 1619806567,
              'status' => 1,
              'comment' => 0,
              'promote' => 0,
              'sticky' => 0,
            ],
          ],
          'workbench_moderation_node_history' => [
            [
              'hid' => 733111,
              'vid' => 959631,
              'nid' => 274511,
              'from_state' => 'published',
              'state' => 'draft',
              'uid' => 1,
              'stamp' => 1619806567,
              'published' => 0,
              'is_current' => 0,
            ],
            [
              'hid' => 733191,
              'vid' => 959631,
              'nid' => 274511,
              'from_state' => 'draft',
              'state' => 'published',
              'uid' => 1,
              'stamp' => 1619807861,
              'published' => 1,
              'is_current' => 1,
            ],
            [
              'hid' => 733116,
              'vid' => 959636,
              'nid' => 274511,
              'from_state' => 'published',
              'state' => 'published',
              'uid' => 1,
              'stamp' => 1619806567,
              'published' => 0,
              'is_current' => 0,
            ],
          ],
          'workbench_moderation_states' => WorkbenchModerationFlowTest::WORKBENCH_MODERATION_STATES,
          'workbench_moderation_transitions' => WorkbenchModerationFlowTest::WORKBENCH_MODERATION_TRANSITIONS,
        ],
        'Expected' => [
          274511 => [
            'published' => TRUE,
            'states' => [
              959631 => [1, 'published', 1],
            ],
          ],
        ],
      ]
    ];
  }

}
