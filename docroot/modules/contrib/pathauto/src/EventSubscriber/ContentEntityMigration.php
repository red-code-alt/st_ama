<?php

namespace Drupal\pathauto\EventSubscriber;

use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigratePreRowSaveEvent;
use Drupal\migrate\Plugin\migrate\destination\EntityContentBase;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;
use Drupal\pathauto\PathautoState;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * A subscriber to content entity migrations.
 *
 * This event subscriber prevents generating path aliases for content entities
 * being migrated.
 *
 * It also saves path alias states (pathauto states) for nodes and taxonomy term
 * entities (only these entities might have path alias state in Drupal 7
 * Pathauto). For an up-to-date Pathauto module, the states are fetched from
 * Pathauto's "pathauto_state" table. In case of an older release, the event
 * subscriber tries to fetch the state from the "pathauto_persist" table of
 * Pathauto Persistent State (pathauto_persist) module.
 */
class ContentEntityMigration implements EventSubscriberInterface {

  /**
   * Constant to flag a new content entity.
   *
   * @const string
   */
  const ENTITY_BEING_MIGRATED = '_pathauto_content_entity_migration';

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The key value factory.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueFactoryInterface
   */
  protected $keyValue;

  /**
   * Constructs a ContentEntityMigration instance.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, KeyValueFactoryInterface $key_value) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->keyValue = $key_value;
  }

  /**
   * Checks whether a migration is a content entity migration with "path" field.
   *
   * @param \Drupal\migrate\Event\MigratePreRowSaveEvent $event
   *   The migrate post row save event.
   *
   * @return bool
   *   TRUE if the migration's source is Drupal, the migration's destination is
   *   a content entity, and the destination entity has a "path" field; FALSE
   *   otherwise.
   */
  protected function isApplicable(MigratePreRowSaveEvent $event): bool {
    $migration = $event->getMigration();
    if (!($migration->getSourcePlugin() instanceof DrupalSqlBase)) {
      return FALSE;
    }
    if (!(($destination_plugin = $migration->getDestinationPlugin()) instanceof EntityContentBase)) {
      return FALSE;
    }
    if (!($destination_entity_type = $destination_plugin->getDerivativeId())) {
      return FALSE;
    }
    $path_field_definition = $this->entityFieldManager->getBaseFieldDefinitions($destination_entity_type)['path'] ?? NULL;
    return $path_field_definition instanceof FieldDefinitionInterface;
  }

  /**
   * Saves pathauto states and flags new content entities as 'migrated'.
   *
   * @param \Drupal\migrate\Event\MigratePreRowSaveEvent $event
   *   The migrate post row save event.
   */
  public function onPreRowSave(MigratePreRowSaveEvent $event): void {
    if (!$this->isApplicable($event)) {
      return;
    }

    // "Flag" the entity to make PathautoGenerator skip creating an alias during
    // the migration process.
    // @see \Drupal\pathauto\PathautoGenerator::createEntityAlias()
    $row = $event->getRow();
    $row->setDestinationProperty(static::ENTITY_BEING_MIGRATED, NULL);

    // Only nodes and terms may have pathauto alias state.
    $migration = $event->getMigration();
    $entity_type = $migration->getDestinationPlugin()->getDerivativeId();
    if (!in_array($entity_type, ['node', 'taxonomy_term'], TRUE)) {
      return;
    }

    // Determine the right state table.
    $source = $migration->getSourcePlugin();
    $pathauto_schema = $source->getSystemData()['module']['pathauto']['schema_version'] ?? 0;
    $path_alias_state_table = $pathauto_schema >= 7006
      ? 'pathauto_state'
      : 'pathauto_persist';
    // Get the source ID of the current content entity the row represents.
    $source_id_values = $row->getSourceIdValues();
    $source_entity_id = reset($source_id_values);

    // Check the source database for a matching path alias state record.
    try {
      $path_alias_state_from_source_result = $source->getDatabase()->select($path_alias_state_table, 'pas')
        ->fields('pas', ['pathauto'])
        ->condition('pas.entity_type', $entity_type)
        ->condition('pas.entity_id', $source_entity_id)
        ->execute()->fetchField();
      $path_alias_state_from_source = $path_alias_state_from_source_result !== FALSE ? (int) $path_alias_state_from_source_result : NULL;
    }
    catch (DatabaseExceptionWrapper $e) {
      // No table found or the table does not have the expected schema.
      $path_alias_state_from_source = NULL;
    }

    // Try to load the destination entity and check its alias current state.
    // This is required for e.g. node translation: multilingual node sources
    // have different IDs on the source site, but they will have the same node
    // ID on the destination site. If any of the translations (even the default
    // one) had a custom path alias, then we will set the state to
    // PathautoState::SKIP to prevent accidental data loss.
    // @see https://www.w3.org/Provider/Style/URI
    $storage = $this->entityTypeManager->getStorage($entity_type);
    assert($storage instanceof ContentEntityStorageInterface);
    $destination_entity_key = $storage->getEntityType()->getKey('id');
    // The migration's row should be already processed at this point, this is a
    // MigrateEvents::PRE_ROW_SAVE subscriber.
    $destination_entity_id = $row->getDestinationProperty($destination_entity_key);
    $path_alias_state_at_dest = $this->keyValue->get("pathauto_state.$entity_type")
      ->get(PathautoState::getPathautoStateKey($destination_entity_id));

    // Determine the right path alias state.
    $path_alias_state = NULL;
    if ($path_alias_state_from_source === NULL && $path_alias_state_at_dest === NULL) {
      // No path alias status was found.
      return;
    }
    elseif ($path_alias_state_from_source === NULL && $path_alias_state_at_dest !== NULL) {
      // Source does not have path alias state for this entity row, but
      // destination does. This might happen with entity translations.
      $path_alias_state = $path_alias_state_at_dest;
    }
    elseif ($path_alias_state_from_source !== NULL && $path_alias_state_at_dest === NULL) {
      // Source does have state for this entity row, but destination does not.
      // This might be the row that contains the default translation.
      $path_alias_state = $path_alias_state_from_source;
    }
    else {
      // Both source and destination have state for this entity row. If these
      // are equal, then use that state value; if they aren't, set it to
      // PathautoState::SKIP to prevent changing the custom path alias.
      $path_alias_state = $path_alias_state_at_dest === $path_alias_state_from_source
        ? $path_alias_state_from_source
        : PathautoState::SKIP;
    }

    // Actually, I (huzooka) was not able to push the state value to
    // PathautoFieldItemList::computeValue() when a content entity's language
    // is not equal with the destination site's default language: that's why the
    // right keyvalue record is set here.
    // @see Drupal\pathauto\PathautoFieldItemList::computeValue()
    $this->keyValue->get("pathauto_state.$entity_type")
      ->set(PathautoState::getPathautoStateKey($destination_entity_id), $path_alias_state);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      MigrateEvents::PRE_ROW_SAVE => ['onPreRowSave'],
    ];
  }

}
