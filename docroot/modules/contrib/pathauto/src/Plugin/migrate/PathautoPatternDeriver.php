<?php

namespace Drupal\pathauto\Plugin\migrate;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\Plugin\MigrationDeriverTrait;
use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Deriver class for Pathauto pattern migrations.
 */
class PathautoPatternDeriver extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;
  use MigrationDeriverTrait;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs PathautoPatternDeriver.
   *
   * @param string $base_plugin_id
   *   The base plugin ID this derivative is for.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct($base_plugin_id, ModuleHandlerInterface $module_handler) {
    $this->basePluginId = $base_plugin_id;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $base_plugin_id,
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $pathauto_source = static::getSourcePlugin('pathauto_pattern');
    assert($pathauto_source instanceof DrupalSqlBase);

    try {
      $pathauto_source->checkRequirements();
    }
    catch (RequirementsException $e) {
      // If the source plugin requirements failed, that means we do not have a
      // Drupal source database configured - there is nothing to generate.
      return $this->derivatives;
    }

    $source_system_data = $pathauto_source->getSystemData();
    $pathauto_entity_installed_on_source = !empty($source_system_data['module']['pathauto_entity']['status']);
    $file_entity_installed_on_source = !empty($source_system_data['module']['file_entity']['status']);
    $media_migration_available = $this->moduleHandler->moduleExists('media_migration');
    if ($file_entity_installed_on_source && $media_migration_available) {
      // If media_migration is installed, then file entities will be migrated
      // to media entities.
      $base_plugin_definition['process']['entity_type'][1]['map']['file'] = 'media';
    }

    try {
      foreach ($pathauto_source as $pathauto_row) {
        assert($pathauto_row instanceof Row);
        $source = $pathauto_row->getSource();
        $entity_type = $source['entity_type'];
        $bundle = $source['bundle'] ?? NULL;
        $derivative_id = $bundle
          ? implode(PluginBase::DERIVATIVE_SEPARATOR, [
            $entity_type,
            $bundle,
          ])
          : "{$entity_type}_default";

        if (
          // Pathauto Entity adds pattern support for every (content) entity.
          (
            !$pathauto_entity_installed_on_source &&
            !in_array($entity_type, ['node', 'taxonomy_term', 'user'], TRUE)
          ) ||
          // "file_entity" provides basic support for file pathauto patterns –
          // this means that a default (bundle agnostic) pattern can be
          // configured for files.
          (
            $entity_type === 'file' &&
            !$file_entity_installed_on_source
          )
        ) {
          continue;
        }

        $derivative_definition = $base_plugin_definition;
        $derivative_definition['source']['entity_type'] = $entity_type;
        $derivative_definition['source']['bundle'] = $bundle ?? FALSE;
        $derivative_definition['label'] = $this->t('@label (@type - @bundle)', [
          '@label' => $derivative_definition['label'],
          '@type' => $entity_type,
          '@bundle' => $bundle ?? 'default',
        ]);

        $migration_requirements = [];
        if ($bundle) {
          switch ($entity_type) {
            case 'node':
              $migration_requirements = ['d7_node_type'];
              break;

            case 'taxonomy_term':
              $migration_requirements = ['d7_taxonomy_vocabulary'];
              break;

            case 'file':
              // If media_migration is installed, then media bundles will be
              // migrated by "d7_file_entity_type". But per-bundle patterns of
              // "file" entities are functional on the source only if
              // "pathauto_entity" was installed.
              if ($pathauto_entity_installed_on_source && $media_migration_available) {
                $migration_requirements = ['d7_file_entity_type'];
              }
              else {
                continue 2;
              }
              break;

            case 'comment':
              $migration_requirements = ['d7_comment_type'];
              break;
          }
        }
        $derivative_definition['migration_dependencies']['optional'] = array_merge(
          $derivative_definition['migration_dependencies']['optional'] ?? [],
          $migration_requirements
        );

        $this->derivatives[$derivative_id] = $derivative_definition;
      }
    }
    catch (DatabaseExceptionWrapper $e) {
    }

    return $this->derivatives;
  }

}
