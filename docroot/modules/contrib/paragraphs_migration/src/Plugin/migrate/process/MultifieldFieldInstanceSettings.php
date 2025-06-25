<?php

namespace Drupal\paragraphs_migration\Plugin\migrate\process;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateLookupInterface;
use Drupal\migrate\ProcessPluginBase as CoreProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Process plugin for field instance settings of multifield fields.
 *
 * @MigrateProcessPlugin(
 *   id = "pm_multifield_field_instance_settings"
 * )
 */
class MultifieldFieldInstanceSettings extends CoreProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The migrate lookup service.
   *
   * @var \Drupal\migrate\MigrateLookupInterface
   */
  protected $migrateLookup;

  /**
   * Constructs a new MultifieldFieldInstanceSettings process plugin instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\migrate\MigrateLookupInterface $migrate_lookup
   *   The migrate lookup service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrateLookupInterface $migrate_lookup) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->migrateLookup = $migrate_lookup;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('migrate.lookup')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $source_type_is_multifield = $row->getSourceProperty('type') === 'multifield';
    $field_name = $row->getSourceProperty('field_name');
    if (!$source_type_is_multifield || !$field_name) {
      return $value;
    }

    try {
      $lookup_result = $this->migrateLookup->lookup('pm_multifield_type', [$field_name]);
    }
    catch (\Exception $e) {
      $lookup_result = NULL;
    }
    $destination_bundle = is_array($lookup_result) && reset($lookup_result[0])
      ? reset($lookup_result[0])
      : $field_name;

    return [
      'handler_settings' => [
        'negate' => FALSE,
        'target_bundles' => [
          $destination_bundle => $destination_bundle,
        ],
      ],
    ];
  }

}
