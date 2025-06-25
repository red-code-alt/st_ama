<?php

namespace Drupal\maxlength\Plugin\migrate;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\Plugin\MigrationDeriverTrait;
use Drupal\migrate\Row;

/**
 * Deriver for Maxlength module.
 *
 * @see \Drupal\node\Plugin\migrate\D7NodeDeriver
 */
class MaxlengthDeriver extends DeriverBase {
  use MigrationDeriverTrait;

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $source = static::getSourcePlugin('maxlength_title_settings');
    try {
      $source->checkRequirements();
    }
    catch (RequirementsException $e) {
      // Nothing to generate.
      return $this->derivatives;
    }
    try {
      foreach ($source as $row) {
        assert($row instanceof Row);
        $node_type = $row->getSourceProperty('mx_bundle');
        $derivative_definition = $base_plugin_definition;
        $derivative_definition['source']['node_type'] = $node_type;
        $this->derivatives[$node_type] = $derivative_definition;
      }
    }
    catch (DatabaseExceptionWrapper $e) {
      // Once we begin iterating the source plugin it is possible that the
      // source values will not exist.
    }
    return $this->derivatives;
  }

}
