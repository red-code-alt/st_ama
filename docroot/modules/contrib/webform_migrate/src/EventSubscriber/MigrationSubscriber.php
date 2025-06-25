<?php

namespace Drupal\webform_migrate\EventSubscriber;

use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigratePostRowSaveEvent;
use Drupal\migrate\Plugin\MigrationInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Sets Message on a row save.
 */
class MigrationSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      MigrateEvents::POST_ROW_SAVE => ['onPostRowSave'],
    ];
  }

  /**
   * Sets Messages for rules that can't be migrated.
   */
  public function onPostRowSave(MigratePostRowSaveEvent $event) {
    if ($event->getMigration()->id() !== "d7_webform" || !$event->getDestinationIdValues()) {
      return;
    }
    $unmigratable_rules = $event->getRow()->getSourceProperty('unmigratable_rules');
    if ($unmigratable_rules) {
      $event->getMigration()->getIdMap()->saveMessage($event->getRow()->getSourceIdValues(), "The rules " . implode(', ', $unmigratable_rules) . " couldn't be migrated for webform " . $event->getRow()->get('title') . ".", MigrationInterface::MESSAGE_INFORMATIONAL);
    }
  }

}
