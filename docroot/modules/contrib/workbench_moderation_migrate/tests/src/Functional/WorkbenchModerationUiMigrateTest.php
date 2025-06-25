<?php

namespace Drupal\Tests\workbench_moderation_migrate\Functional;

use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Tests\workbench_moderation_migrate\Traits\WorkbenchModerationAssertionsTrait;
use Drupal\Tests\workbench_moderation_migrate\Traits\WorkbenchModerationTestToolsTrait;

/**
 * Tests moderation flow and the changed node complete migrations with core UI.
 *
 * @group workbench_moderation_migrate
 */
class WorkbenchModerationUiMigrateTest extends CoreUiMigrateTestBase {

  use WorkbenchModerationTestToolsTrait;
  use WorkbenchModerationAssertionsTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'content_moderation',
    'workbench_moderation_migrate',
  ];

  /**
   * {@inheritdoc}
   */
  public function getDatabaseFixtureFilePath(): string {
    return implode(DIRECTORY_SEPARATOR, [
      drupal_get_path('module', 'workbench_moderation_migrate'),
      'tests',
      'fixtures',
      'wm-drupal7.php',
    ]);
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

    $this->executeMigrationsWithUi();
    $this->resetAll();

    $workflow_storage = $this->container->get('entity_type.manager')->getStorage('workflow');
    assert($workflow_storage instanceof ConfigEntityStorageInterface);
    $workflow_ids = array_keys($workflow_storage->loadMultiple());
    // Ignore "editorial" flow installed by default with "standard" profile.
    $migrated_workflow_ids = array_values(
      array_diff(
        $workflow_ids,
        ['editorial']
      )
    );
    $this->assertEquals(
      [
        'editorial_with_draft_default_state',
      ],
      $migrated_workflow_ids
    );

    $this->assertEditorialWithDraftDefaultStateWorkflow();

    $this->assertNode1RevisionStates($with_missing_node_revisions);
    $this->assertNode2RevisionStates($with_missing_node_revisions);
    $this->assertNode3RevisionStates($with_missing_node_revisions);
    $this->assertNode4RevisionStates($with_missing_node_revisions);
    $this->assertNode5RevisionStates($with_missing_node_revisions);
  }

}
