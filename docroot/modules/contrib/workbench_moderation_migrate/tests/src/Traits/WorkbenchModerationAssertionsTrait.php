<?php

namespace Drupal\Tests\workbench_moderation_migrate\Traits;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Entity\RevisionableStorageInterface;
use Drupal\node\Entity\Node;

/**
 * Custom assertions used in Workbench Moderation Migrate tests.
 */
trait WorkbenchModerationAssertionsTrait {

  /**
   * Ignored workflow properties.
   *
   * @var string[]
   */
  protected $workflowIgnoredProperties = [
    'uuid',
    'langcode',
  ];

  /**
   * The common base of the expected workflows.
   *
   * @var array
   */
  protected $workflowBase = [
    'status' => TRUE,
    'dependencies' => [
      'module' => [
        'content_moderation',
      ],
    ],
    'type' => 'content_moderation',
    'type_settings' => [
      'default_moderation_state' => 'draft',
      'states' => [
        'archive' => [
          'label' => 'Archive',
          'published' => FALSE,
          'default_revision' => TRUE,
          'weight' => 1000,
        ],
        'draft' => [
          'label' => 'Draft',
          'published' => FALSE,
          'default_revision' => FALSE,
          'weight' => -99,
        ],
        'needs_review' => [
          'label' => 'Needs Review',
          'published' => FALSE,
          'default_revision' => FALSE,
          'weight' => 0,
        ],
        'published' => [
          'label' => 'Published',
          'published' => TRUE,
          'default_revision' => TRUE,
          'weight' => 99,
        ],
      ],
      'transitions' => [
        'archive' => [
          'label' => 'Archive',
          'to' => 'archive',
          'weight' => 1000,
          'from' => [
            'published',
          ],
        ],
        'submit_for_review' => [
          'label' => 'Submit for Review',
          'to' => 'needs_review',
          'weight' => 1,
          'from' => [
            'draft',
          ],
        ],
        'publish' => [
          'label' => 'Publish',
          'to' => 'published',
          'weight' => 3,
          'from' => [
            'needs_review',
          ],
        ],
        'reject' => [
          'label' => 'Reject',
          'to' => 'draft',
          'weight' => 2,
          'from' => [
            'needs_review',
          ],
        ],
        '_keep_in_draft' => [
          'label' => 'Keep in Draft',
          'from' => ['draft'],
          'to' => 'draft',
          'weight' => -99,
        ],
        '_keep_in_published' => [
          'label' => 'Keep in Published',
          'from' => ['published'],
          'to' => 'published',
          'weight' => 99,
        ],
        '_keep_in_needs_review' => [
          'label' => 'Keep in Needs Review',
          'from' => ['needs_review'],
          'to' => 'needs_review',
          'weight' => 0,
        ],
      ],
    ],
  ];

  /**
   * Verifies that the migrated workflow's structure matches the expectation.
   */
  protected function assertEditorialWithDraftDefaultStateWorkflow() {
    $workflow_storage = $this->container->get('entity_type.manager')->getStorage('workflow');
    assert($workflow_storage instanceof ConfigEntityStorageInterface);
    $this->assertEquals(
      NestedArray::mergeDeepArray(
        [
          $this->workflowBase,
          [
            'dependencies' => [
              'config' => [
                'node.type.news',
                'node.type.page',
              ],
            ],
            'id' => 'editorial_with_draft_default_state',
            'label' => 'Editorial With Draft Default State',
            'type_settings' => [
              'default_moderation_state' => 'draft',
              'entity_types' => [
                'node' => [
                  'news',
                  'page',
                ],
              ],
            ],
          ],
        ]
      ),
      array_diff_key(
        $workflow_storage->load('editorial_with_draft_default_state')->toArray(),
        array_combine($this->workflowIgnoredProperties, $this->workflowIgnoredProperties)
      )
    );
  }

  /**
   * Checks 'status', 'moderation_state' and 'revision_default' of entity revs.
   *
   * @param string $entity_type_id
   *   The entity type ID, e.g. "node", "comment'.
   * @param array $expected_moderation_states
   *   An indexed array of the expected revision properties, keyed by the
   *   revision ID.
   *   The first value represents to "status", second one "moderation_state",
   *   the third one "revision_default".
   * @param string|int|null $entity_id
   *   The identifier of the entity.
   */
  protected function assertModerationStates(string $entity_type_id, array $expected_moderation_states, $entity_id = NULL) {
    $storage = $this->container->get('entity_type.manager')->getStorage($entity_type_id);
    assert($storage instanceof RevisionableStorageInterface);
    $actual_states = [];
    $expected_states = [];
    $available_revision_ids = array_keys($expected_moderation_states);
    if ($entity_id) {
      $entity_type = $storage->getEntityType();
      $available_revision_ids = array_keys(
        $storage->getQuery()
          ->accessCheck(FALSE)
          ->condition($entity_type->getKey('id'), $entity_id)
          ->sort($entity_type->getKey('revision'))
          ->allRevisions()
          ->execute()
      );
    }
    foreach ($available_revision_ids as $revision_id) {
      $revision = $storage->loadRevision($revision_id);
      $actual_states[$revision_id] = [
        'status' => $revision->status->first()->value ?? 'MISSING!',
        'moderation_state' => $revision->moderation_state->first()->value ?? 'MISSING!',
        'revision_default' => $revision->revision_default->first()->value ?? 'MISSING!',
      ];
    }

    foreach ($expected_moderation_states as $revision_id => $expectations) {
      [$expected_status, $expected_moderation_state, $expected_revision_default] = $expectations;
      $expected_states[$revision_id] = [
        'status' => $expected_status,
        'moderation_state' => $expected_moderation_state,
        'revision_default' => $expected_revision_default,
      ];
    }

    $this->assertEquals($expected_states, $actual_states);
  }

  /**
   * Checks the revisions of node 1.
   *
   * @param bool $some_revisions_were_deleted
   *   Whether some of the node revisions were deleted on the source site.
   */
  protected function assertNode1RevisionStates(bool $some_revisions_were_deleted = FALSE) {
    $node = Node::load(1);
    $this->assertTrue($node->isPublished());

    // Expected state of node 1 with "corrupted" revision history.
    //
    // Drupal 7 transition history:
    // @code
    // ========================================================================
    // h#  vid  from_state    [to_]state  pub/curr  Expectation
    // ========================================================================
    // 01  101  draft         needs_review  0/0  SKIP: REVISION IS MISSING
    // 02  102  needs_review  published     0/0  SKIP: REVISION IS MISSING
    // 03  103  published     published     0/0  SKIP: REVISION IS MISSING
    // 04  104  published     draft         0/0  SKIP: NEXT MIGRATABLE IS PUBL.
    // 05  105  published     published     0/0  SKIP: REVISION IS MISSING
    // 06  106  draft         needs_review  0/0  IGNORED: NOT LAST STATE OF REV.
    // 07  107  published     published     0/0  SKIP: REVISION IS MISSING
    // 08  106  needs_review  draft         0/0  SKIP: REVISION IS MISSING
    // 09  108  published     published     0/0  SKIP: REVISION IS MISSING
    // 10  109  draft         needs_review  0/0  SKIP: REVISION IS MISSING
    // 11  110  published     published     0/0  SKIP: REVISION IS MISSING
    // 12  111  needs_review  published     0/0  SKIP: REVISION IS MISSING
    // 13  112  published     published     1/1  default = 1
    // ========================================================================
    // @endcode
    //
    // Expected Drupal 9 revision & state data:
    // @code
    // ========================================================================
    // vid  status  mod.state     default_revision
    // ========================================================================
    // 112  1       published     1
    // ========================================================================
    // @endcode
    if ($some_revisions_were_deleted) {
      $this->assertCount(1, $this->loadEntityRevisions($node));
      $this->assertModerationStates(
        'node',
        [
          112 => [1, 'published', 1],
        ]
      );
      return;
    }

    // Expected state of node 1 with complete revision history.
    //
    // @code
    // ========================================================================
    // h#  vid  from_state    [to_]state  pub/curr  Expectation
    // ========================================================================
    // 01  101  draft         needs_review  0/0  default = 1
    // 02  102  needs_review  published     0/0  default = 1
    // 03  103  published     published     0/0  default = 1
    // 04  104  published     draft         0/0  default = 0
    // 05  105  published     published     0/0  SKIP: ITS A CLONE
    // 06  106  draft         needs_review  0/0  IGNORED: NOT LAST STATE OF REV.
    // 07  107  published     published     0/0  SKIP: ITS A CLONE
    // 08  106  needs_review  draft         0/0  default = 0
    // 09  108  published     published     0/0  SKIP: ITS A CLONE
    // 10  109  draft         needs_review  0/0  default = 0
    // 11  110  published     published     0/0  SKIP: ITS A CLONE
    // 12  111  needs_review  published     0/0  default = 1
    // 13  112  published     published     1/1  default = 1
    // ========================================================================
    // @endcode
    //
    // Expected Drupal 9 revision & state data:
    // @code
    // ========================================================================
    // vid  status  mod.state     default_revision
    // ========================================================================
    // 101  0       needs_review  1
    // 102  1       published     1
    // 103  1       published     1
    // 104  0       draft         0
    // 106  0       draft         0
    // 109  0       needs_review  0
    // 111  1       published     1
    // 112  1       published     1
    // ========================================================================
    // @endcode
    $this->assertCount(8, $this->loadEntityRevisions($node));
    $this->assertModerationStates(
      'node',
      [
        101 => [0, 'needs_review', 1],
        102 => [1, 'published', 1],
        103 => [1, 'published', 1],
        104 => [0, 'draft', 0],
        106 => [0, 'draft', 0],
        109 => [0, 'needs_review', 0],
        111 => [1, 'published', 1],
        112 => [1, 'published', 1],
      ]
    );
  }

  /**
   * Checks the revisions of node 2.
   *
   * @param bool $some_revisions_were_deleted
   *   Whether some of the node revisions were deleted on the source site.
   */
  protected function assertNode2RevisionStates(bool $some_revisions_were_deleted = FALSE) {
    $node = Node::load(2);
    $this->assertFalse($node->isPublished());

    // Expected state of node 2 with "corrupted" revision history.
    //
    // Drupal 7 transition history:
    // @code
    // ========================================================================
    // h#  vid  from_state    [to_]state  pub/curr  Expectation
    // ========================================================================
    // 14  201  draft         draft         0/0  SKIP: REVISION IS MISSING
    // 15  202  draft         draft         0/0  SKIP: REVISION IS MISSING
    // 16  203  draft         needs_review  0/0  IGNORED: NOT LAST STATE OF REV.
    // 17  203  needs_review  draft         0/0  default = 1
    // 18  204  draft         needs_review  0/1  default = 1
    // ========================================================================
    // @endcode
    //
    // Expected Drupal 9 revision & state data:
    // @code
    // ========================================================================
    // vid  status  mod.state     default_revision
    // ========================================================================
    // 203  0       draft         1
    // 204  0       needs_review  1
    // ========================================================================
    // @endcode
    if ($some_revisions_were_deleted) {
      $this->assertCount(2, $this->loadEntityRevisions($node));
      $this->assertModerationStates(
        'node',
        [
          203 => [0, 'draft', 1],
          204 => [0, 'needs_review', 1],
        ]
      );
      return;
    }

    // Expected state of node 2 with complete revision history.
    //
    // Drupal 7 transition history:
    // @code
    // ========================================================================
    // h#  vid  from_state    [to_]state  pub/curr  Expectation
    // ========================================================================
    // 14  201  draft         draft         0/0  default = 1
    // 15  202  draft         draft         0/0  default = 1
    // 16  203  draft         needs_review  0/0  IGNORED: NOT LAST STATE OF REV.
    // 17  203  needs_review  draft         0/0  default = 1
    // 18  204  draft         needs_review  0/1  default = 1
    // ========================================================================
    // @endcode
    //
    // Expected Drupal 9 revision & state data:
    // @code
    // ========================================================================
    // vid  status  mod.state     default_revision
    // ========================================================================
    // 201  0       draft         1
    // 202  0       draft         1
    // 203  0       draft         1
    // 204  0       needs_review  1
    // ========================================================================
    // @endcode
    $this->assertCount(4, $this->loadEntityRevisions($node));
    $this->assertModerationStates(
      'node',
      [
        201 => [0, 'draft', 1],
        202 => [0, 'draft', 1],
        203 => [0, 'draft', 1],
        204 => [0, 'needs_review', 1],
      ]
    );
  }

  /**
   * Checks the revisions of node 3.
   *
   * @param bool $some_revisions_were_deleted
   *   Whether some of the node revisions were deleted on the source site.
   */
  protected function assertNode3RevisionStates(bool $some_revisions_were_deleted = FALSE) {
    $node = Node::load(3);
    $this->assertTrue($node->isPublished());

    // Expected state of node 3 with "corrupted" revision history.
    //
    // Drupal 7 transition history:
    // @code
    // ========================================================================
    // h#  vid  from_state    [to_]state  pub/curr  Expectation
    // ========================================================================
    // 19  301  draft         published     0/0  SKIP: REVISION IS MISSING
    // 20  302  published     draft         0/0  SKIP: NEXT MIGRATABLE IS PUBL.
    // 21  303  published     published     0/0  default = 1
    // 22  304  draft         draft         0/1  default = 0
    // 23  305  published     published     1/0  SKIP: ITS A CLONE
    // ========================================================================
    // @endcode
    //
    // Expected Drupal 9 revision & state data:
    // @code
    // ========================================================================
    // vid  status  mod.state     default_revision
    // ========================================================================
    // 303  1       published     1
    // 304  0       draft         0
    // ========================================================================
    // @endcode
    if ($some_revisions_were_deleted) {
      $this->assertCount(2, $this->loadEntityRevisions($node));
      $this->assertModerationStates(
        'node',
        [
          303 => [1, 'published', 1],
          304 => [0, 'draft', 0],
        ]
      );
      return;
    }

    // Expected state of node 3 with complete revision history.
    //
    // Drupal 7 transition history:
    // @code
    // ========================================================================
    // h#  vid  from_state    [to_]state  pub/curr  Expectation
    // ========================================================================
    // 19  301  draft         published     0/0  default = 1
    // 20  302  published     draft         0/0  default = 0
    // 21  303  published     published     0/0  SKIP: ITS A CLONE
    // 22  304  draft         draft         0/1  default = 0
    // 23  305  published     published     1/0  SKIP: ITS A CLONE
    // ========================================================================
    // @endcode
    //
    // Expected Drupal 9 revision & state data:
    // @code
    // ========================================================================
    // vid  status  mod.state     default_revision
    // ========================================================================
    // 301  1       published     1
    // 302  0       draft         0
    // 304  0       draft         0
    // ========================================================================
    // @endcode
    $this->assertCount(3, $this->loadEntityRevisions($node));
    $this->assertModerationStates(
      'node',
      [
        301 => [1, 'published', 1],
        302 => [0, 'draft', 0],
        304 => [0, 'draft', 0],
      ]
    );
  }

  /**
   * Checks the revisions of node 4.
   *
   * @param bool $some_revisions_were_deleted
   *   Whether some of the node revisions were deleted on the source site.
   */
  protected function assertNode4RevisionStates(bool $some_revisions_were_deleted = FALSE) {
    $node = Node::load(4);
    $this->assertFalse($node->isPublished());

    // Expected state of node 4 with "corrupted" revision history.
    //
    // Drupal 7 transition history:
    // @code
    // ========================================================================
    // h#  vid  from_state    [to_]state  pub/curr  Expectation
    // ========================================================================
    // 24  401  draft         needs_review  0/0  SKIP: REVISION IS MISSING
    // 25  402  needs_review  published     0/0  SKIP: REVISION IS MISSING
    // 26  403  published     draft         0/0  default = 0
    // 27  404  published     published     0/0  IGNORED: NOT LAST STATE OF REV.
    // 28  404  published     draft         0/1  default = 1
    // ========================================================================
    // @endcode
    //
    // Expected Drupal 9 revision & state data:
    // @code
    // ========================================================================
    // vid  status  mod.state     default_revision
    // ========================================================================
    // 403  0       draft         1
    // 404  0       draft         1
    // ========================================================================
    // @endcode
    if ($some_revisions_were_deleted) {
      $this->assertCount(2, $this->loadEntityRevisions($node));
      $this->assertModerationStates(
        'node',
        [
          403 => [0, 'draft', 1],
          404 => [0, 'draft', 1],
        ]
      );
      return;
    }

    // Expected state of node 4 with complete revision history.
    //
    // Drupal 7 transition history:
    // @code
    // ========================================================================
    // h#  vid  from_state    [to_]state  pub/curr  Expectation
    // ========================================================================
    // 24  401  draft         needs_review  0/0  default = 1
    // 25  402  needs_review  published     0/0  default = 1
    // 26  403  published     draft         0/0  default = 0
    // 27  404  published     published     0/0  IGNORED: NOT LAST STATE OF REV.
    // 28  404  published     draft         0/0  default = 1
    // ========================================================================
    // @endcode
    //
    // Expected Drupal 9 revision & state data:
    // @code
    // ========================================================================
    // vid  status  mod.state     default_revision
    // ========================================================================
    // 401  0       needs_review  1
    // 402  1       published     1
    // 403  0       draft         0
    // 404  0       archive       1
    // ========================================================================
    // @endcode
    $this->assertCount(4, $this->loadEntityRevisions($node));
    $this->assertModerationStates(
      'node',
      [
        401 => [0, 'needs_review', 1],
        402 => [1, 'published', 1],
        403 => [0, 'draft', 0],
        404 => [0, 'archive', 1],
      ]
    );
  }

  /**
   * Checks the revisions of node 5.
   *
   * @param bool $some_revisions_were_deleted
   *   Whether some of the node revisions were deleted on the source site.
   */
  protected function assertNode5RevisionStates(bool $some_revisions_were_deleted = FALSE) {
    $node = Node::load(5);
    $this->assertTrue($node->isPublished());

    // Expected state of node 5 with "corrupted" revision history.
    //
    // Drupal 7 transition history:
    // @endcode
    // ========================================================================
    // h#  vid  from_state    [to_]state  pub/curr  Expectation
    // ========================================================================
    // 29  501  draft         published     0/0  SKIP: REVISION IS MISSING
    // 30  502  published     draft         0/0  default = 0
    // 31  503  published     published     0/0  SKIP: ITS A CLONE
    // 32  504  draft         draft         0/0  default = 1
    // 33  505  published     published     0/0  SKIP: ITS A CLONE
    // 34  506  draft         published     0/0  IGNORED: NOT LAST STATE OF REV.
    // 35  506  published     draft         0/0  default = 0
    // 36  507  published     published     0/0  SKIP: ITS A CLONE
    // 37  508  published     draft         0/0  default = 0
    // 38  509  published     published     0/0  SKIP: ITS A CLONE
    // 39  510  draft         needs_review  0/0  default = 0
    // 40  511  published     published     0/0  SKIP: ITS A CLONE
    // 41  512  needs_review  published     0/0  default = 1
    // 42  513  published     published     0/0  default = 1
    // 43  514  published     draft         0/1  default = 0
    // 44  515  published     published     1/0  SKIP: ITS A CLONE
    // ========================================================================
    // @endcode
    //
    // Expected Drupal 9 revision & state data:
    // @endcode
    // ========================================================================
    // vid  status  mod.state     default_revision
    // ========================================================================
    // 503  1       published     1
    // 504  0       draft         0
    // 506  0       draft         0
    // 508  0       draft         0
    // 510  0       needs_review  0
    // 512  1       published     1
    // 513  1       published     1
    // 514  0       draft         0
    // ========================================================================
    // @endcode
    if ($some_revisions_were_deleted) {
      $this->assertCount(8, $this->loadEntityRevisions($node));
      $this->assertModerationStates(
        'node',
        [
          503 => [1, 'published', 1],
          504 => [0, 'draft', 0],
          506 => [0, 'draft', 0],
          508 => [0, 'draft', 0],
          510 => [0, 'needs_review', 0],
          512 => [1, 'published', 1],
          513 => [1, 'published', 1],
          514 => [0, 'draft', 0],
        ]
      );
      return;
    }

    // Expected state of node 5 with complete revision history.
    //
    // Drupal 7 transition history:
    // @code
    // ========================================================================
    // h#  vid  from_state    [to_]state  pub/curr  Expectation
    // ========================================================================
    // 29  501  draft         published     0/0  default = 1
    // 30  502  published     draft         0/0  default = 0
    // 31  503  published     published     0/0  SKIP: ITS A CLONE
    // 32  504  draft         draft         0/0  default = 1
    // 33  505  published     published     0/0  SKIP: ITS A CLONE
    // 34  506  draft         published     0/0  IGNORED: NOT LAST STATE OF REV.
    // 35  506  published     draft         0/0  default = 0
    // 36  507  published     published     0/0  SKIP: ITS A CLONE
    // 37  508  published     draft         0/0  default = 0
    // 38  509  published     published     0/0  SKIP: ITS A CLONE
    // 39  510  draft         needs_review  0/0  default = 0
    // 40  511  published     published     0/0  SKIP: ITS A CLONE
    // 41  512  needs_review  published     0/0  default = 1
    // 42  513  published     published     0/0  default = 1
    // 43  514  published     draft         0/1  default = 0
    // 44  515  published     published     1/0  SKIP: ITS A CLONE
    // ========================================================================
    // @endcode
    //
    // Expected Drupal 9 revision & state data:
    // @code
    // ========================================================================
    // vid  status  mod.state     default_revision
    // ========================================================================
    // 501  1       published     1
    // 502  0       draft         0
    // 504  0       draft         0
    // 506  0       draft         0
    // 508  0       draft         0
    // 510  0       needs_review  0
    // 512  1       published     1
    // 513  1       published     1
    // 514  0       draft         0
    // ========================================================================
    // @endcode
    $this->assertCount(9, $this->loadEntityRevisions($node));
    $this->assertModerationStates(
      'node',
      [
        501 => [1, 'published', 1],
        502 => [0, 'draft', 0],
        504 => [0, 'draft', 0],
        506 => [0, 'draft', 0],
        508 => [0, 'draft', 0],
        510 => [0, 'needs_review', 0],
        512 => [1, 'published', 1],
        513 => [1, 'published', 1],
        514 => [0, 'draft', 0],
      ]
    );
  }

}
