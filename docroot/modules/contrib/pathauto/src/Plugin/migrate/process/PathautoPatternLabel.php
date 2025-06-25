<?php

namespace Drupal\pathauto\Plugin\migrate\process;

use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Process plugin for pathauto pattern's label.
 *
 * @code
 * process:
 *   label:
 *     plugin: pathauto_pattern_label
 *     source:
 *       - entity_type_id_value
 *       - entity_bundle_value
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "pathauto_pattern_label"
 * )
 */
class PathautoPatternLabel extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The migration to be executed.
   *
   * @var \Drupal\migrate\Plugin\MigrationInterface
   */
  protected $migration;

  /**
   * The available entity type definitions.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface[]
   */
  protected $entityTypeDefinitions;

  /**
   * The bundle info of all available entity types.
   *
   * @var array[]
   */
  protected $entityTypeBundleInfo;

  /**
   * Constructs a PathautoPatternLabel instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The Migration the plugin is being used in.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The migrate lookup service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_bundle_info
   *   The migrate stub service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $entity_bundle_info) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->migration = $migration;
    $this->entityTypeDefinitions = $entity_type_manager->getDefinitions();
    $this->entityTypeBundleInfo = $entity_bundle_info->getAllBundleInfo();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $entity_type = ((array) $value)[0];
    $bundle = ((array) $value)[1] ?? NULL;
    $langcode = ((array) $value)[2] ?? NULL;

    $entity_type_label = isset($this->entityTypeDefinitions[$entity_type])
      ? (string) $this->entityTypeDefinitions[$entity_type]->getLabel()
      : $entity_type;
    $bundle_suffix = $bundle
      ? $this->entityTypeBundleInfo[$entity_type][$bundle]['label'] ?? $bundle
      : 'default';

    $label = implode(' - ', [
      $entity_type_label,
      $bundle_suffix,
    ]);

    if (is_string($langcode)) {
      $label .= " ($langcode)";
    }

    return $label;
  }

}
