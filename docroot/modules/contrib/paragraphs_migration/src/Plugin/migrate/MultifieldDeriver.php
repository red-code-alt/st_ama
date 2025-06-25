<?php

namespace Drupal\paragraphs_migration\Plugin\migrate;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\Plugin\Migration;
use Drupal\migrate\Plugin\MigrationDeriverTrait;
use Drupal\migrate_drupal\Plugin\migrate\source\d7\FieldableEntity;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Deriver for multifield → paragraph migrations.
 */
class MultifieldDeriver extends MultifieldConfigDeriver {
  use MigrationDeriverTrait;

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $source_plugin = static::getSourcePlugin('pm_multifield');
    $field_instance_source = static::getSourcePlugin('d7_field_instance');

    try {
      $source_plugin->checkRequirements();
      $field_instance_source->checkRequirements();
    }
    catch (RequirementsException $e) {
      return $this->derivatives;
    }

    if (!$field_instance_source instanceof DrupalSqlBase) {
      return $this->derivatives;
    }

    try {
      $fields_types_bundles = $field_instance_source->query()
        ->condition('fc.type', 'multifield')
        ->execute()
        ->fetchAll(\PDO::FETCH_ASSOC);
    }
    catch (DatabaseExceptionWrapper $e) {
      $fields_types_bundles = [];
    }

    try {
      foreach ($fields_types_bundles as $field_instance_data) {
        [
          'field_name' => $field_name,
          'entity_type' => $source_entity_type,
          'bundle' => $source_bundle,
          'data' => $data_serialized,
        ] = $field_instance_data;
        $derivative_definition = $base_plugin_definition;
        $derivative_id = implode(PluginBase::DERIVATIVE_SEPARATOR, [
          $source_entity_type,
          $source_bundle,
          $field_name,
        ]);
        $derivative_definition['label'] = $this->t('@label (@type)', [
          '@label' => $derivative_definition['label'],
          '@type' => unserialize($data_serialized)['label'] ?? $derivative_id,
        ]);
        $derivative_definition['source']['field_name'] = $field_name;
        $derivative_definition['source']['entity_type'] = $source_entity_type;
        $derivative_definition['source']['bundle'] = $source_bundle;

        if ($source_plugin instanceof FieldableEntity) {
          $migration = \Drupal::service('plugin.manager.migration')
            ->createStubMigration($derivative_definition);
          assert($migration instanceof Migration);
          $this->fieldDiscovery->addBundleFieldProcesses($migration, 'multifield', $field_name);
          $derivative_definition = $migration->getPluginDefinition();
        }

        $this->hardenDefinition($derivative_definition);

        $this->derivatives[$derivative_id] = $derivative_definition;
      }
    }
    catch (DatabaseExceptionWrapper $e) {
      // Once we begin iterating the source plugin it is possible that the
      // source tables will not exist. This can happen when
      // MigrationPluginManager gathers up the migration definitions but we do
      // not actually have a Drupal 7 source database.
    }
    return $this->derivatives;
  }

}
