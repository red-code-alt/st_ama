<?php

namespace Drupal\workbench_moderation_migrate\Plugin\migrate;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\Plugin\MigrationDeriverTrait;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Deriver for workbench moderation flow migrations.
 */
class WorkbenchModerationFlowDeriver extends DeriverBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $source = MigrationDeriverTrait::getSourcePlugin('workbench_moderation_flow');
    assert($source instanceof DrupalSqlBase);
    try {
      $source->checkRequirements();
    }
    catch (RequirementsException $e) {
      return $this->derivatives;
    }

    try {
      foreach ($source as $row) {
        $default_state_serialized = $row->getSourceProperty('value');
        $default_state = unserialize($default_state_serialized);
        $derivative_definition = $base_plugin_definition;
        $derivative_definition['label'] = $this->t(
          '@label with @default_state default state',
          [
            '@label' => $base_plugin_definition['label'],
            '@default_state' => $default_state,
          ]
        );
        $derivative_definition['source']['default_state'] = $default_state;
        $derivative_definition['source']['default_state_serialized'] = $default_state_serialized;
        $derivative_definition['source']['node_types_aggregated'] = $row->getSourceProperty('node_types_aggregated');

        $this->derivatives[$default_state] = $derivative_definition;
      }
    }
    catch (DatabaseExceptionWrapper $e) {
    }

    return $this->derivatives;
  }

}
