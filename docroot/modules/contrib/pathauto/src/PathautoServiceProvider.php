<?php

namespace Drupal\pathauto;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\pathauto\EventSubscriber\ContentEntityMigration;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Remove the drush commands until path_alias module is enabled.
 */
class PathautoServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    $definitions = array_keys($container->getDefinitions());
    if (!in_array('path_alias.repository', $definitions)) {
      $container->removeDefinition('pathauto.commands');
    }

    $modules = $container->getParameter('container.modules');
    if (isset($modules['migrate'])) {
      $container->register('pathauto.content_entity_migration', ContentEntityMigration::class)
        ->addTag('event_subscriber')
        ->addArgument(new Reference('entity_type.manager'))
        ->addArgument(new Reference('entity_field.manager'))
        ->addArgument(new Reference('keyvalue'));
    }
  }

}
