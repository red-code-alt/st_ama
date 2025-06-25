<?php

namespace Drupal\Tests\workbench_moderation_migrate\Traits;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Helpers for Workbench Moderation Migrate tests.
 */
trait WorkbenchModerationTestToolsTrait {

  /**
   * Data provider for testing moderated node migration.
   *
   * @return array[]
   *   The test cases.
   */
  public function providerTest() {
    return [
      'Complete node revision history' => [
        'Missing node revisions' => FALSE,
      ],
      'Corrupted node revision history' => [
        'Missing node revisions' => TRUE,
      ],
    ];
  }

  /**
   * Deletes specific node revision from the source.
   */
  public function deleteSourceNodeRevisions() {
    // Shouldn't delete active or "current" revisions!
    $this->sourceDatabase->delete('node_revision')
      ->condition(
        'vid',
        [
          // Completely remove every previous revisions of node 1.
          101,
          102,
          103,
          105,
          106,
          107,
          108,
          109,
          110,
          111,
          // Node 2.
          201,
          202,
          // Node 43.
          301,
          // Node 4.
          401,
          402,
          // Node 5.
          501,
        ],
        'IN'
      )
      ->execute();
  }

  /**
   * Loads revisions of a content entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   A content entity instance.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface[]
   *   The revisions of the given entity, keyed by the entity ID and revision ID
   *   joined with ':'.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function loadEntityRevisions(ContentEntityInterface $entity): array {
    $revisions = [];
    $entity_type = $entity->getEntityType();
    $entity_type_id = $entity_type->id();
    $revision_key = $entity_type->getKey('revision');
    $storage = \Drupal::entityTypeManager()->getStorage($entity_type_id);
    $revision_ids = $storage->getQuery()
      ->allRevisions()
      ->condition($entity_type->getKey('id'), $entity->id())
      ->sort($revision_key, 'ASC')
      ->execute();
    if (empty($revision_ids)) {
      return $revisions;
    }

    foreach (array_keys($revision_ids) as $revision_id) {
      $entity_revision = $storage->loadRevision($revision_id);
      $key = implode(':', [$entity_revision->id(), $revision_id]);
      $revisions[$key] = $entity_revision;
    }

    return $revisions;
  }

}
