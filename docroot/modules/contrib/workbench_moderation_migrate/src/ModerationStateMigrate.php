<?php

declare(strict_types = 1);

namespace Drupal\workbench_moderation_migrate;

use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Database\Connection;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\migrate\Row;
use Drupal\workflows\StateInterface;
use Drupal\workflows\TransitionInterface;
use Drupal\workflows\WorkflowInterface;

/**
 * Manages moderation states of nodes.
 */
class ModerationStateMigrate {

  /**
   * Migration tag added to moderated node migrations.
   *
   * @const string
   */
  const MODERATED_NODE_MIGRATION_TAG = 'Workbench Moderated Content';

  /**
   * State ID of published state in Drupal 7.
   *
   * @const string
   */
  const DRUPAL7_PUBLISHED_STATE_ID = 'published';

  /**
   * State ID of published state in Drupal 9.
   *
   * @const string
   */
  const DRUPAL9_MIGRATED_PUBLISHED_STATE_ID = 'published';

  /**
   * Threshold for timestamp after a published clone was saved after a draft op.
   *
   * @const int
   */
  const CLONE_SAVE_TIME_UNCERTAINTY_THRESHOLD = 1;

  /**
   * Data column name of the calculated history timestamp.
   *
   * @const string
   */
  const STAMP_CALCULATED = 'stamp_calc';

  /**
   * Local cache for the discovered applicable workflow's ID p source node type.
   *
   * @var string[]
   */
  private static $matchingModerationFlowIds = [];

  /**
   * Local cache for the discovered computed unpublished states.
   *
   * @var string[]
   */
  private static $applicableUnpublishedStates = [];

  /**
   * Adds the appropriate moderation state property value to a node migrate row.
   *
   * @param \Drupal\migrate\Row $row
   *   A migration row representing a node revision source item.
   * @param \Drupal\Core\Database\Connection $source_connection
   *   Database connection to the source Drupal 7 instance.
   *
   * @throws \Drupal\migrate\MigrateSkipRowException
   *   When a particular node revision shouldn't be migrated.
   */
  public static function prepareModeratedNodeRow(Row $row, Connection $source_connection) {
    if (
      !($nid = $row->getSourceProperty('nid')) ||
      !($vid = $row->getSourceProperty('vid'))
    ) {
      return;
    }

    $all_migratable_revision_all_trans = static::getAllTransitionOfMigratableRevisions(
      $source_connection,
      $nid,
      $row->getSourceProperty('type')
    );
    $actual_revisions_last_transition = static::getLastTransitionRelativeToRevision($all_migratable_revision_all_trans, $vid, 'current');
    $next_revisions_last_transition = static::getLastTransitionRelativeToRevision($all_migratable_revision_all_trans, $vid, 'next');
    $previous_published_transition_exists = static::publishedTransitionExists($all_migratable_revision_all_trans, $vid);

    // Skipping clones:
    // Ignore the migration of the current revision if:
    // - the current revision is published, AND
    // - the prev transitions "[to_]state" is not published, AND
    // - the prev prev transitions "[to_]state" is published.
    if (
      $actual_revisions_last_transition['state'] === static::DRUPAL7_PUBLISHED_STATE_ID &&
      $previous_published_transition_exists &&
      static::isPublishedClone($all_migratable_revision_all_trans, $actual_revisions_last_transition)
    ) {
      throw new MigrateSkipRowException(
        "Skipping the migration of this published revision: it is the copy of the last published revision. It was saved by Workbench Moderation as a workaround for the lack of a feature in Drupal 7, because it wasn't able to handle forward (non-default) revisions. In Drupal 9 this is not needed anymore.",
        TRUE
      );
    }

    // Ignore the migration of the current non-default (not published) revision
    // if:
    // 1. The next transition (whose node revision exists) "[to_]state" is
    //    "published"
    // 2. There isn't any previous node revision which was migrated as
    //    "published", but the next transition's from_state is published.
    if (
      $next_revisions_last_transition &&
      $actual_revisions_last_transition['state'] !== static::DRUPAL7_PUBLISHED_STATE_ID &&
      ($next_revisions_last_transition['from_state'] === static::DRUPAL7_PUBLISHED_STATE_ID && !$previous_published_transition_exists) &&
      $next_revisions_last_transition['state'] === static::DRUPAL7_PUBLISHED_STATE_ID
    ) {
      throw new MigrateSkipRowException(
        "Skipping the migration of this draft revision: it lacks its previous revision. It happens because with Drupal 7 Workbench Moderation it was possible to delete older revisions, but in Drupal 9 core it is impossible to restore the original data integrity. Hopefully it isn't a problem that a draft cannot be restored.",
        TRUE
      );
    }

    // If the last active ("is_current") moderation state of the entity isn't
    // published, then we shouldn't allow the entire entity being published: we
    // have to archive the entity.
    $moderation_state = $actual_revisions_last_transition['state'];
    if (
      $next_revisions_last_transition === NULL &&
      $moderation_state !== static::DRUPAL7_PUBLISHED_STATE_ID &&
      static::publishedTransitionExists($all_migratable_revision_all_trans)
    ) {
      $moderation_state = 'archive';
    }

    $row->setDestinationProperty('moderation_state', $moderation_state);

    // If the actual revision lacks its history, add note about that the state
    // is calculated.
    if (!$actual_revisions_last_transition['hid']) {
      $row->setSourceProperty(
        'log',
        implode(' ', array_filter([
          $row->getSourceProperty('log'),
          'Moderation state computed by Workbench Moderation Migrate',
        ]))
      );
    }

    // Node revisions are migrated in ascending order of revision IDs.
    // Before a node is saved as published, every draft is a default revision.
    // After that the node was saved as published, only those revisions are
    // default revisions whose state is configured as being a default revision.
    // With Workbench Moderation Migrate, these states are 'published' and
    // 'archive'.
    $is_default_revision = in_array($moderation_state, ['published', 'archive']) || !$previous_published_transition_exists;
    $row->setDestinationProperty('revision_default', $is_default_revision);
  }

  /**
   * Returns the revision history of the given node.
   *
   * If some revisions lack their history then most column values will be NULL
   * except nid, vid (which is the node version ID – aka revision ID) or
   * timestamp.
   *
   * The returned history is sorted by a calculated timestamp, history ID and
   * state, and contains discoverable (but sometimes inaccurate) [to_]state
   * metadata.
   *
   * @param \Drupal\Core\Database\Connection $source_db
   *   Database connection to the source Drupal 7 instance.
   * @param string|int $nid
   *   The ID of the node which history should be returned.
   * @param string $source_node_type
   *   The type ID of the actual node revision on the source Drupal 7 instance.
   *
   * @return array[]
   *   The revision history of the given node, ordered by the moderation
   *   action's timestamp (so: in the order of the moderation actions, and NOT
   *   in the node revisions' migration!).
   */
  private static function getAllTransitionOfMigratableRevisions(Connection $source_db, $nid, $source_node_type): array {
    $all_history_query = $source_db->select('node_revision', 'nr');
    $all_history_query->leftJoin('workbench_moderation_node_history', 'history', 'history.vid = nr.vid');
    $all_history_query
      ->fields('history')
      ->fields('nr', ['nid', 'vid', 'status', 'timestamp'])
      ->condition('nr.nid', $nid);
    $all_history_query->addExpression('COALESCE(history.stamp, nr.timestamp)', self::STAMP_CALCULATED);

    $all_history = $all_history_query->execute()->fetchAll(\PDO::FETCH_ASSOC);

    $non_published_state = static::getNonPublishedStateAllowingTransitionToPublished($source_node_type);

    $all_history = array_reduce(
      $all_history,
      function (array $carry, array $last_transition) use ($non_published_state) {
        if ($last_transition['state'] === NULL) {
          // Unfortunately, revision status based state is only accurate for the
          // first unpublished revisions. If the node has an already published
          // revisions, then all forward revision's status is 1.
          $last_transition['state'] = $last_transition['status']
            ? self::DRUPAL7_PUBLISHED_STATE_ID
            : $non_published_state;
        }
        $carry[] = $last_transition;
        return $carry;
      },
      []
    );

    // Order:
    // 1. by stamp/timestamp ASC
    // 2. by hid if the above are equal and hids are available, OR by state
    //    (published comes last) OR version ID (ASC).
    usort(
      $all_history,
      function ($transition_a, $transition_b) {
        $time_a = $transition_a[self::STAMP_CALCULATED];
        $time_b = $transition_b[self::STAMP_CALCULATED];

        if ($time_a === $time_b) {
          if (isset($transition_a['hid']) && isset($transition_b['hid'])) {
            return ($transition_a['hid'] < $transition_b['hid']) ? -1 : 1;
          }

          if (
            $transition_a['state'] !== self::DRUPAL7_PUBLISHED_STATE_ID &&
            $transition_b['state'] == self::DRUPAL7_PUBLISHED_STATE_ID
          ) {
            return -1;
          }

          if (
            $transition_a['state'] === self::DRUPAL7_PUBLISHED_STATE_ID &&
            $transition_b['state'] !== self::DRUPAL7_PUBLISHED_STATE_ID
          ) {
            return 1;
          }

          return ($transition_a['vid'] < $transition_b['vid']) ? -1 : 1;
        }
        return ($time_a < $time_b) ? -1 : 1;
      }
    );

    // After we have an ordered history, we can get more info:
    // If two transition have the same (or same-ish) timestamp, then the one
    // with lower revision ID was always saved for a non-published edit.
    foreach ($all_history as $delta => $history_item) {
      if ($history_item['hid']) {
        continue;
      }
      $next_history_item = $all_history[$delta + 1] ?? NULL;

      if (
        $next_history_item  &&
        $next_history_item['state'] === self::DRUPAL7_PUBLISHED_STATE_ID &&
        $next_history_item['vid'] > $history_item['vid'] &&
        ($next_history_item[self::STAMP_CALCULATED] - $history_item[self::STAMP_CALCULATED]) >= 0 &&
        ($next_history_item[self::STAMP_CALCULATED] - $history_item[self::STAMP_CALCULATED]) <= self::CLONE_SAVE_TIME_UNCERTAINTY_THRESHOLD
      ) {
        $all_history[$delta]['state'] = $non_published_state;
      }
    }

    return $all_history;
  }

  /**
   * Returns the last transitions of the migratable node revisions.
   *
   * @param array $all_migratable_transitions
   *   The revision history of a node (returned by
   *   ::getAllTransitionOfMigratableRevisions).
   *
   * @return array[]
   *   The last transitions of the migratable revisions of the given node,
   *   ordered by the node revision (so in the order of the migration).
   */
  private static function getLastTransitions(array $all_migratable_transitions): array {
    return array_reduce(
      $all_migratable_transitions,
      function (array $carry, array $item) {
        if (($carry[$item['vid']][self::STAMP_CALCULATED] ?? 0) < $item[self::STAMP_CALCULATED]) {
          $carry[$item['vid']] = $item;
        }
        return $carry;
      },
      []
    );
  }

  /**
   * Checks whether a published migratable transition exists.
   *
   * @param array $all_migratable_transitions
   *   The revision history of a node (returned by
   *   ::getAllTransitionOfMigratableRevisions).
   * @param string|int|null $vid
   *   A node revision ID. If specified, then only the previous revisions are
   *   checked. If $vid is NULL, then every migratable revisions are checked.
   *
   * @return bool
   *   TRUE if a published migratable transition exists, FALSE if not.
   */
  private static function publishedTransitionExists(array $all_migratable_transitions, $vid = NULL): bool {
    $last_transitions = self::getLastTransitions($all_migratable_transitions);

    $published_found = FALSE;
    $vid = $vid ?? end($last_transitions)['vid'] + 1;
    reset($last_transitions);
    while (((int) (key($last_transitions) < (int) $vid)) && !$published_found) {
      $published_found = current($last_transitions)['state'] === self::DRUPAL7_PUBLISHED_STATE_ID;
      if (!next($last_transitions)) {
        break;
      }
    }

    return $published_found;
  }

  /**
   * Returns the last transition relative to the given revision ID.
   *
   * @param array $all_migratable_transitions
   *   The revision history of a node (returned by
   *   ::getAllTransitionOfMigratableRevisions).
   * @param string|int $vid
   *   The node revision ID whose previous, actual or next transition we need.
   * @param string $which
   *   Which transition should be returned. Might be "prev", "current" or
   *   "next".
   *
   * @return array|null
   *   The last transition relative to the given revision ID. If there is no
   *   matching transition, NULL is returned.
   */
  private static function getLastTransitionRelativeToRevision(array $all_migratable_transitions, $vid, string $which): ?array {
    $last_transitions = self::getLastTransitions($all_migratable_transitions);

    if (!isset($last_transitions[$vid])) {
      return NULL;
    }

    switch ($which) {
      case 'current':
      case 'next':
      case 'prev':
        break;

      default:
        throw new \LogicException("Third argument must be one of 'current', 'next' or 'prev'.");
    }

    reset($last_transitions);
    while (key($last_transitions) < $vid) {
      if (!next($last_transitions)) {
        return NULL;
      }
    }

    $transition = $which($last_transitions);
    if (!$transition) {
      return NULL;
    }

    return $transition;
  }

  /**
   * Checks whether the given transition belongs to a published version's clone.
   *
   * @param array $all_revision_history
   *   The revision history of the node.
   * @param array $transition
   *   The transition to check.
   *
   * @return bool
   *   Whether the given transition belongs to a clone of a published node's
   *   revision.
   */
  private static function isPublishedClone(array $all_revision_history, array $transition): bool {
    reset($all_revision_history);
    while (current($all_revision_history) !== $transition) {
      if (!next($all_revision_history)) {
        break;
      }
    }
    $previous_transition = prev($all_revision_history);

    if (!$previous_transition) {
      return FALSE;
    }

    // If the previous transition's last history entry is the current revision,
    // this was a clone.
    if (
      ($transition[self::STAMP_CALCULATED] - $previous_transition[self::STAMP_CALCULATED]) >= 0 &&
      ($transition[self::STAMP_CALCULATED] - $previous_transition[self::STAMP_CALCULATED]) <= self::CLONE_SAVE_TIME_UNCERTAINTY_THRESHOLD
    ) {
      return TRUE;
    }

    $previous_previous_transition = $previous_transition ? prev($all_revision_history) : FALSE;

    if (!$previous_previous_transition) {
      return FALSE;
    }

    return $previous_transition['state'] !== self::DRUPAL7_PUBLISHED_STATE_ID && $previous_previous_transition['state'] === self::DRUPAL7_PUBLISHED_STATE_ID;
  }

  /**
   * Returns the moderation flow for the corresponding entity.
   *
   * @param string $source_node_type
   *   The node type ID in the source Drupal 7 instance.
   *
   * @return \Drupal\workflows\WorkflowInterface
   *   The moderation flow of the given node type.
   */
  private static function getMatchingModerationFlow(string $source_node_type) {
    if (empty(static::$matchingModerationFlowIds[$source_node_type])) {
      $migration_manager = \Drupal::service('plugin.manager.migration');
      assert($migration_manager instanceof MigrationPluginManagerInterface);
      $flow_migrations = $migration_manager->createInstances(['workbench_moderation_flow']);
      $corresponding_flow_migration = array_reduce(
        $flow_migrations,
        function ($carry, MigrationInterface $flow_migration) use ($source_node_type) {
          $node_types_aggregated = $flow_migration->getSourceConfiguration()['node_types_aggregated'] ?? '';
          if (in_array($source_node_type, explode(',', $node_types_aggregated), TRUE)) {
            return $flow_migration;
          }
          return $carry;
        }
      );
      // Every single flow is migrated by a dedicated migration, so we can
      // easily identify the destination config entity's ID.
      $destination_ids = $corresponding_flow_migration->getIdMap()->lookupDestinationIds([
        $corresponding_flow_migration->getSourceConfiguration()['default_state_serialized'],
      ]);
      if (empty($destination_ids)) {
        throw new \LogicException(
          sprintf(
            "Cannot find the corresponding moderation flow applicable for node type %s",
            $source_node_type
          )
        );
      }
      $destination_id = implode('.', reset($destination_ids));
      static::$matchingModerationFlowIds[$source_node_type] = $destination_id;
    }

    $storage = \Drupal::entityTypeManager()->getStorage('workflow');
    assert($storage instanceof ConfigEntityStorageInterface);
    $corresponding_flow = $storage->load(static::$matchingModerationFlowIds[$source_node_type]);
    if ($corresponding_flow) {
      assert($corresponding_flow instanceof WorkflowInterface);
      assert($corresponding_flow->status());
    }

    return $corresponding_flow;
  }

  /**
   * Returns the ID of an unpublished state which can transition to published.
   *
   * @param string $source_node_type
   *   The node type ID in the source Drupal 7 instance.
   *
   * @return string
   *   The ID of an unpublished state which can transition to the given state.
   */
  private static function getNonPublishedStateAllowingTransitionToPublished($source_node_type): string {
    if (empty(static::$applicableUnpublishedStates[$source_node_type])) {
      $moderation_flow = self::getMatchingModerationFlow($source_node_type);
      $transitions_to_published = array_filter(
        $moderation_flow->getTypePlugin()->getTransitions(),
        function (TransitionInterface $transition) {
          // Return TRUE if:
          // - transition's to state equals to $to_state_id.
          // - one of the from state is unpublished.
          $to_state_is_published = $transition->to()->id() === static::DRUPAL9_MIGRATED_PUBLISHED_STATE_ID;
          $one_from_state_is_unpublished = array_reduce(
            $transition->from(),
            function (bool $carry, StateInterface $from_state) {
              if (!$from_state->isPublishedState()) {
                return TRUE;
              }
              return $carry;
            },
            FALSE
          );
          return $to_state_is_published && $one_from_state_is_unpublished;
        }
      );

      $non_default_state = array_reduce(
        reset($transitions_to_published)->from(),
        function ($carry, StateInterface $from_state) {
          if (!$carry && !$from_state->isPublishedState()) {
            return $from_state->id();
          }
          return $carry;
        },
        NULL
      );
      static::$applicableUnpublishedStates[$source_node_type] = $non_default_state;
    }

    return static::$applicableUnpublishedStates[$source_node_type];
  }

}
