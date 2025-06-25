<?php

namespace Drupal\pathauto_test_uuid_generator;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Changes the UUID service to a generator with predictable results.
 */
class PathautoTestUuidGeneratorServiceProvider implements ServiceModifierInterface {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    if ($container->has('uuid')) {
      $container->getDefinition('uuid')
        ->setClass(UuidTestGenerator::class)
        ->addArgument(new Reference('state'));
    }
  }

}
