<?php

namespace Drupal\paragraphs_migration\Plugin\migrate\process;

use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\ProcessPluginBase as CoreProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Process plugin for multifield subfield translatablility.
 *
 * If a multifield field was marked as translatable, then all of its subfields
 * should be translatable.
 *
 * @MigrateProcessPlugin(
 *   id = "pm_multifield_field_translatable"
 * )
 */
class MultifieldFieldTranslatable extends CoreProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The current migration plugin instance.
   *
   * @var \Drupal\migrate\Plugin\MigrationInterface
   */
  protected $migration;

  /**
   * Constructs a new MultifieldFieldTranslatable instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The current migration plugin instance.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->migration = $migration;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $entity_type_is_multifield = $row->getSourceProperty('entity_type') === 'multifield';
    if (!$entity_type_is_multifield || !(($source = $this->migration->getSourcePlugin()) instanceof DrupalSqlBase)) {
      return $value;
    }

    try {
      return $source->getDatabase()->select('field_config', 'fc')
        ->fields('fc', ['translatable'])
        ->condition('fc.field_name', $row->getSourceProperty('bundle'))
        ->execute()->fetchCol()[0] ?? $value;
    }
    catch (DatabaseExceptionWrapper $e) {
    }
    return $value;
  }

}
