<?php

namespace Drupal\paragraphs_migration\Plugin\migrate;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\Plugin\MigrationDeriverTrait;
use Drupal\migrate\Row;
use Drupal\migrate_drupal\FieldDiscoveryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configuration migration deriver for multifield → paragraph migrations.
 */
class MultifieldConfigDeriver extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;
  use MigrationDeriverTrait;
  /**
   * Migration base IDs of migrations derived per field name.
   *
   * @const string[]
   */
  const MIGRATIONS_DERIVED_PER_FIELD_NAME = [
    'pm_multifield_translation_settings',
    'pm_multifield_type',
  ];

  /**
   * Migration base IDs of migrations derived per entity type, bundle and field.
   *
   * @const string[]
   */
  const MIGRATIONS_DERIVED_PER_TYPE_BUNDLE_FIELD = [
    'pm_multifield',
  ];

  /**
   * The base plugin ID this derivative is for.
   *
   * @var string
   */
  protected $basePluginId;

  /**
   * The migration field discovery service.
   *
   * @var \Drupal\migrate_drupal\FieldDiscoveryInterface
   */
  protected $fieldDiscovery;

  /**
   * Constructs a new MultifieldItemDeriver instance.
   *
   * @param string $base_plugin_id
   *   The base plugin ID for the plugin ID.
   * @param \Drupal\migrate_drupal\FieldDiscoveryInterface $field_discovery
   *   The migration field discovery service.
   */
  public function __construct($base_plugin_id, FieldDiscoveryInterface $field_discovery) {
    $this->basePluginId = $base_plugin_id;
    $this->fieldDiscovery = $field_discovery;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $base_plugin_id,
      $container->get('migrate_drupal.field_discovery')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $types = static::getSourcePlugin('pm_multifield_type');
    $source_plugin = static::getSourcePlugin($base_plugin_definition['source']['plugin']);

    try {
      $types->checkRequirements();
      if ($types !== $source_plugin) {
        $source_plugin->checkRequirements();
      }
    }
    catch (RequirementsException $e) {
      return $this->derivatives;
    }

    try {
      foreach ($types as $row) {
        assert($row instanceof Row);
        $field_name = $row->getSourceProperty('field_name');
        $derivative_definition = $base_plugin_definition;
        $derivative_id = $field_name;
        $derivative_definition['label'] = $this->t('@label (@type)', [
          '@label' => $derivative_definition['label'],
          '@type' => $row->getSourceProperty('label'),
        ]);
        $derivative_definition['source']['field_name'] = $field_name;

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

  /**
   * Hardens dependencies and lookup migration IDs of multifield migrations.
   *
   * @param array $plugin_definition
   *   A derivative's migration plugin definition.
   */
  protected function hardenDefinition(array &$plugin_definition) {
    $field_name = $plugin_definition['source']['field_name'] ?? NULL;
    if (!$field_name) {
      return;
    }

    // Harden migrations derived per field name.
    $this->hardenDependencies($plugin_definition, static::MIGRATIONS_DERIVED_PER_FIELD_NAME, $field_name);
    $this->hardenMigrationLookups($plugin_definition, static::MIGRATIONS_DERIVED_PER_FIELD_NAME, $field_name);

    // Harden migrations derived per source entity type ID, source entity bundle
    // and host field name.
    $entity_type = $plugin_definition['source']['entity_type'] ?? NULL;
    $bundle = $plugin_definition['source']['bundle'] ?? NULL;
    if ($entity_type && $bundle) {
      $derivative_id = implode(PluginBase::DERIVATIVE_SEPARATOR, [
        $entity_type,
        $bundle,
        $field_name,
      ]);
      $this->hardenDependencies($plugin_definition, static::MIGRATIONS_DERIVED_PER_TYPE_BUNDLE_FIELD, $derivative_id);
      $this->hardenMigrationLookups($plugin_definition, static::MIGRATIONS_DERIVED_PER_TYPE_BUNDLE_FIELD, $derivative_id);
    }
  }

  /**
   * Hardens dependencies of a multifield migration.
   *
   * @param array $plugin_definition
   *   A derivative's migration plugin definition.
   * @param string[] $migration_ids_to_harden
   *   An array of the migration plugin IDs which should be hardened.
   * @param string $derivative_id
   *   The derivative ID to add to the corresponding migration dependencies.
   */
  protected function hardenDependencies(array &$plugin_definition, array $migration_ids_to_harden, string $derivative_id) {
    foreach (['required', 'optional'] as $dependency_type) {
      if (empty($plugin_definition['migration_dependencies'][$dependency_type])) {
        continue;
      }

      foreach ($plugin_definition['migration_dependencies'][$dependency_type] as $key => $dependency) {
        if (in_array($dependency, $migration_ids_to_harden, TRUE)) {
          $plugin_definition['migration_dependencies'][$dependency_type][$key] = implode(PluginBase::DERIVATIVE_SEPARATOR, [
            $dependency,
            $derivative_id,
          ]);
        }
      }
    }
  }

  /**
   * Adds derivative IDs to the multifield migrations used in migrate lookups.
   *
   * @param array $plugin_definition
   *   A migration plugin definition.
   * @param string[] $migration_ids_to_harden
   *   An array of the migration plugin IDs which should be hardened.
   * @param string $derivative_id
   *   The derivative ID.
   */
  protected function hardenMigrationLookups(array &$plugin_definition, array $migration_ids_to_harden, string $derivative_id) {
    foreach ($plugin_definition['process'] as &$property_process) {
      if (!is_array($property_process)) {
        continue;
      }

      if (isset($property_process['plugin']) && $property_process['plugin'] === 'migration_lookup') {
        $this->addDerivativeId($property_process, $migration_ids_to_harden, $derivative_id);
      }
      else {
        foreach ($property_process as &$subprocess) {
          if (isset($subprocess['plugin']) && $subprocess['plugin'] === 'migration_lookup') {
            $this->addDerivativeId($subprocess, $migration_ids_to_harden, $derivative_id);
          }
        }
      }
    }
  }

  /**
   * Identifies migrations used by migration_lookup process plugins.
   *
   * @param array $process
   *   A process configuration array.
   * @param string[] $migration_ids_to_harden
   *   An array of the migration plugin IDs which should be hardened.
   * @param string $derivative_id
   *   The derivative ID.
   */
  protected function addDerivativeId(array &$process, array $migration_ids_to_harden, string $derivative_id) {
    $process['migration'] = (array) $process['migration'];
    foreach ($process['migration'] as $key => $lookup_migration_id) {
      if (in_array($lookup_migration_id, $migration_ids_to_harden, TRUE)) {
        $process['migration'][$key] = implode(PluginBase::DERIVATIVE_SEPARATOR, [
          $lookup_migration_id,
          $derivative_id,
        ]);
      }
    }
  }

}
