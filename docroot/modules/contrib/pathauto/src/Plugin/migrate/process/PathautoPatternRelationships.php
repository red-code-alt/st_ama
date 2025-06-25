<?php

namespace Drupal\pathauto\Plugin\migrate\process;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\Context\ContextHandlerInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipProcessException;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Process plugin for a pathauto pattern relationships.
 *
 * @code
 * process:
 *   relationships:
 *     plugin: pathauto_pattern_relationships
 *     source:
 *       - entity_type_id_value
 *       - langcode
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "pathauto_pattern_relationships"
 * )
 */
class PathautoPatternRelationships extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a PathautoPatternRelationships process plugin instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }



  /**
   * {@inheritdoc}
   *
   * @see \Drupal\pathauto\Entity\PathautoPattern::addRelationship()
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $value = (array) $value;
    if (count($value) < 2) {
      return [];
    }

    [
      $entity_type,
      $langcode,
    ] = $value;

    if (empty($langcode)) {
      return [];
    }

    if (!is_string($entity_type)) {
      throw new MigrateSkipProcessException('The entity_type must be a string.');
    }
    if (!($this->entityTypeManager->hasDefinition($entity_type))) {
      throw new MigrateSkipProcessException(sprintf("The '%s' entity type does not exist.", $entity_type));
    }
    if (!is_string($langcode)) {
      throw new MigrateSkipProcessException('The language code must be a string.');
    }

    // Variable copied from \Drupal\pathauto\Form\PatternEditForm...
    $language_mapping = implode(':', [
      $entity_type,
      $this->entityTypeManager->getDefinition($entity_type)->getKey('langcode'),
      'language',
    ]);

    return [
      $language_mapping => [
        'label' => 'Language',
      ],
    ];
  }

}
