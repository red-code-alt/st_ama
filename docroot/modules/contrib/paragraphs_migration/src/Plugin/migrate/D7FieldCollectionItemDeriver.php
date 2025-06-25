<?php

namespace Drupal\paragraphs_migration\Plugin\migrate;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Deriver class for field collections to paragraphs migrations.
 */
class D7FieldCollectionItemDeriver extends FieldableEntityDeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $field_instances = static::getSourcePlugin('d7_field_instance');

    try {
      $field_instances->checkRequirements();
    }
    catch (RequirementsException $e) {
      return $this->derivatives;
    }

    try {
      assert($field_instances instanceof DrupalSqlBase);
      $query = $field_instances->query()
        ->condition('fc.type', 'field_collection')
        ->condition('fc.module', 'field_collection')
        // @see fieldable_panels_pane looks like it will never be portable https://www.drupal.org/node/3153099
        // @todo consider generalizing this and disallowing derivatives for any parent entity type that does not exist?
        ->condition('fci.entity_type', 'fieldable_panels_pane', '<>');

      $derivatives = array_reduce($query->execute()->fetchAll(), function (array $carry, array $record) {
        $fci_data = unserialize($record['data']);
        $carry[$record['entity_type']][$record['bundle']][$record['field_name']] = $fci_data['label'];
        return $carry;
      }, []);
    }
    catch (DatabaseExceptionWrapper $e) {
      // Once we begin iterating the source plugin it is possible that the
      // source tables will not exist. This can happen when the
      // MigrationPluginManager gathers up the migration definitions but we do
      // not actually have a Drupal 7 source database.
      return $this->derivatives;
    }

    // Parent entity type: 'node', 'field_collection', etc.
    foreach ($derivatives as $parent_entity_type => $parent_bundle_data) {
      // Parent entity bundle: 'article', 'field_nested_fc_outer', etc.
      foreach ($parent_bundle_data as $parent_bundle => $host_field_names) {
        // Field names: 'field_nested_fc_inner'.
        foreach ($host_field_names as $host_field_name => $host_field_label) {
          $derivative_id = implode(PluginBase::DERIVATIVE_SEPARATOR, [
            $parent_entity_type,
            $parent_bundle,
            $host_field_name,
          ]);
          $derivative_definition = $base_plugin_definition;
          $derivative_definition['label'] = $this->t(
            '@label (in @parent_entity_bundle @parent_entity_type, referenced from @field_label field)',
            [
              '@label' => $derivative_definition['label'],
              '@parent_entity_bundle' => $parent_bundle,
              '@parent_entity_type' => $parent_entity_type,
              '@field_label' => $host_field_label,
            ]
          );

          $derivative_definition['source']['field_name'] = $host_field_name;
          $derivative_definition['source']['parent_type'] = $parent_entity_type;
          $derivative_definition['source']['parent_bundle'] = $parent_bundle;
          $derivative_definition['source']['legacy_entity_type_id'] = 'field_collection_item';
          $derivative_definition['source']['legacy_entity_bundle_id'] = $host_field_name;

          $migration = $this->migrationManager->createStubMigration($derivative_definition);
          $this->fieldDiscovery->addBundleFieldProcesses($migration, 'field_collection_item', $host_field_name);
          $this->derivatives[$derivative_id] = $migration->getPluginDefinition();

          // Revision migration has to depend on the corresponding default
          // revision migration derivative.
          if (in_array('Field Collection Revisions Content', $migration->getMigrationTags(), TRUE)) {
            // Add more specific dependency.
            $dependencies = $migration->getMigrationDependencies();

            foreach ($dependencies['required'] as $index => $dependency) {
              if ($dependency === 'd7_pm_field_collection') {
                $dependencies['required'][$index] .= ":$derivative_id";
              }
            }

            $this->derivatives[$derivative_id]['migration_dependencies'] = $dependencies;
          }
        }
      }
    }

    return $this->derivatives;
  }

}
