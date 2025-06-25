<?php

namespace Drupal\pathauto\Plugin\migrate\process;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipProcessException;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Process plugin for a pathauto pattern's selection criteria.
 *
 * @code
 * process:
 *   selection_criteria:
 *     plugin: pathauto_pattern_selection_criteria
 *     source:
 *       - entity_type_id_value
 *       - entity_bundle_value
 *       - langcode
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "pathauto_pattern_selection_criteria"
 * )
 */
class PathautoPatternSelectionCriteria extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The UUID service.
   *
   * @var \Drupal\Component\Uuid\UuidInterface
   */
  protected $uuidService;

  /**
   * Constructs a PathautoPatternSelectionCriteria instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Component\Uuid\UuidInterface $uuid_service
   *   The UUID service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, UuidInterface $uuid_service) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->uuidService = $uuid_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('uuid')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $value = (array) $value;
    if (count($value) < 3) {
      throw new MigrateSkipProcessException('The entity_type, the bundle, the langcode or more of these sources are missing.');
    }

    [
      $entity_type,
      $bundle,
      $langcode,
    ] = $value;

    if (!is_string($entity_type)) {
      throw new MigrateSkipProcessException('The entity_type must be a string.');
    }

    if (!($this->entityTypeManager->hasDefinition($entity_type))) {
      throw new MigrateSkipProcessException(sprintf("The '%s' entity type does not exist.", $entity_type));
    }

    $selection_criteria = [];

    if (is_string($bundle)) {
      $uuid = $this->uuidService->generate();
      $selection_criteria[$uuid] = [
        'uuid' => $uuid,
        'id' => ($entity_type == 'node') ? 'node_type' : 'entity_bundle:' . $entity_type,
        'bundles' => [$bundle => $bundle],
        'negate' => FALSE,
        'context_mapping' => [$entity_type => $entity_type],
      ];
    }

    if (is_string($langcode)) {
      $uuid = $this->uuidService->generate();
      // Variable copied from \Drupal\pathauto\Form\PatternEditForm...
      $language_mapping = implode(':', [
        $entity_type,
        $this->entityTypeManager->getDefinition($entity_type)->getKey('langcode'),
        'language',
      ]);
      $selection_criteria[$uuid] = [
        'uuid' => $uuid,
        'id' => 'language',
        'langcodes' => [
          $langcode => $langcode,
        ],
        'negate' => FALSE,
        'context_mapping' => [
          'language' => $language_mapping,
        ],
      ];
    }

    return $selection_criteria;
  }

}
