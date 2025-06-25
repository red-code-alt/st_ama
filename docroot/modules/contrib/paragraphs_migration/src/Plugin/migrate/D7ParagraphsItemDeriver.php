<?php

namespace Drupal\paragraphs_migration\Plugin\migrate;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Deriver class for paragraph migrations.
 */
class D7ParagraphsItemDeriver extends FieldableEntityDeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $field_instances = static::getSourcePlugin('d7_field_instance');
    $types = static::getSourcePlugin('d7_pm_paragraphs_type');
    $paragraph_types = [];

    try {
      $field_instances->checkRequirements();
      $types->checkRequirements();
    }
    catch (RequirementsException $e) {
      return $this->derivatives;
    }

    try {
      assert($field_instances instanceof DrupalSqlBase);
      $query = $field_instances->query()
        ->condition('fc.type', 'paragraphs')
        ->condition('fc.module', 'paragraphs');
      $derivatives = array_reduce($query->execute()->fetchAll(), function (array $carry, array $record) {
        $fci_data = unserialize($record['data']);
        $carry[$record['entity_type']][$record['bundle']][$record['field_name']] = $fci_data['label'];
        return $carry;
      }, []);

      foreach ($types as $type) {
        $paragraph_types[$type->getSourceProperty('bundle')] = $type->getSourceProperty('name');
      }
    }
    catch (DatabaseExceptionWrapper $e) {
      // Once we begin iterating the source plugin it is possible that the
      // source tables will not exist. This can happen when the
      // MigrationPluginManager gathers up the migration definitions but we do
      // not actually have a Drupal 7 source database.
      return $this->derivatives;
    }

    // Parent entity type: 'node', 'paragraph', etc.
    foreach ($derivatives as $parent_entity_type => $parent_bundle_data) {
      // Parent entity bundle: 'article', 'tags', 'paragraph_bundle_one', etc.
      foreach ($parent_bundle_data as $parent_bundle => $host_field_names) {
        // Field names, e.g. 'paragraph_one_only'.
        foreach ($host_field_names as $host_field_name => $host_field_label) {
          // Get those paragraph bundles that are actually used in the given
          // field for the given parent entity type and bundle.
          $used_types_query = $field_instances->getDatabase()->select("field_data_$host_field_name", $host_field_name)
            ->distinct()
            ->fields('p', ['bundle'])
            ->condition("{$host_field_name}.entity_type", $parent_entity_type)
            ->condition("{$host_field_name}.bundle", $parent_bundle)
            ->condition('p.archived', 0);
          $used_types_query->join('paragraphs_item', 'p', "{$host_field_name}.{$host_field_name}_value = p.item_id AND {$host_field_name}.{$host_field_name}_revision_id = p.revision_id AND p.field_name = :field_name", [':field_name' => $host_field_name]);

          try {
            $used_types = $used_types_query->execute()->fetchCol(0);
          }
          catch (\Exception $e) {
            $used_types = array_keys($paragraph_types);
          }

          foreach ($used_types as $paragraph_bundle) {
            $derivative_definition = $base_plugin_definition;
            $derivative_id = implode(PluginBase::DERIVATIVE_SEPARATOR, [
              $parent_entity_type,
              $parent_bundle,
              $host_field_name,
              $paragraph_bundle,
            ]);
            $derivative_definition['label'] = $this->t(
              '@label of type @type (in @parent_entity_bundle @parent_entity_type, referenced from @field_label field)',
              [
                '@label' => $derivative_definition['label'],
                '@type' => $paragraph_types[$paragraph_bundle],
                '@parent_entity_bundle' => $parent_bundle,
                '@parent_entity_type' => $parent_entity_type,
                '@field_label' => $host_field_label,
              ]
            );
            $derivative_definition['source']['bundle'] = $paragraph_bundle;
            $derivative_definition['source']['parent_type'] = $parent_entity_type;
            $derivative_definition['source']['parent_bundle'] = $parent_bundle;
            $derivative_definition['source']['field_name'] = $host_field_name;
            $derivative_definition['source']['legacy_entity_type_id'] = 'paragraphs_item';
            $derivative_definition['source']['legacy_entity_bundle_id'] = $paragraph_bundle;

            $migration = $this->migrationManager->createStubMigration($derivative_definition);
            $this->fieldDiscovery->addBundleFieldProcesses($migration, 'paragraphs_item', $paragraph_bundle);
            $this->derivatives[$derivative_id] = $migration->getPluginDefinition();

            // Revision migration has to depend on the corresponding default
            // revision migration derivative.
            if (in_array('Paragraphs Revisions Content', $migration->getMigrationTags(), TRUE)) {
              // Add more specific dependency.
              $dependencies = $migration->getMigrationDependencies();
              $dependencies += ['required' => []];

              foreach ($dependencies['required'] as $index => $dependency) {
                if ($dependency === 'd7_pm_paragraphs') {
                  $dependencies['required'][$index] .= ":$derivative_id";
                }
              }

              $this->derivatives[$derivative_id]['migration_dependencies'] = $dependencies;
            }
          }
        }
      }
    }
    return $this->derivatives;
  }

}
