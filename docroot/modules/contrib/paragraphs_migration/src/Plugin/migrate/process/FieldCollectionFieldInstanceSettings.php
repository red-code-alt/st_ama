<?php

namespace Drupal\paragraphs_migration\Plugin\migrate\process;

use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateLookupInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure field instance settings for field collections.
 *
 * @MigrateProcessPlugin(
 *   id = "pm_field_collection_field_instance_settings"
 * )
 */
class FieldCollectionFieldInstanceSettings extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The migrate lookup service.
   *
   * @var \Drupal\migrate\MigrateLookupInterface
   */
  protected $migrateLookup;

  /**
   * Constructs a new FieldCollectionFieldInstanceSettings instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity bundle info service.
   * @param \Drupal\migrate\MigrateLookupInterface $migrate_lookup
   *   The migrate lookup service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeBundleInfoInterface $entity_type_bundle_info, MigrateLookupInterface $migrate_lookup) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_bundle_info);

    $this->migrateLookup = $migrate_lookup;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.bundle.info'),
      $container->get('migrate.lookup')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if ($row->getSourceProperty('type') === 'field_collection') {
      $bundles = $this->entityTypeBundleInfo->getBundleInfo('paragraph');
      // Lookup for the target bundle.
      $target_bundle_lookup_results = $this->migrateLookup->lookup('d7_pm_field_collection_type', (array) $row->getSourceProperty('field_name'));
      $target_bundle = !empty($target_bundle_lookup_results[0])
        ? reset($target_bundle_lookup_results[0])
        : NULL;

      if (!$target_bundle || !isset($bundles[$target_bundle])) {
        throw new MigrateSkipRowException('No target paragraph bundle found for field_collection');
      }

      // Enable only this paragraph type for this field.
      $weight = 0;
      $value['handler_settings']['negate'] = 0;
      $value['handler_settings']['target_bundles'] = [$target_bundle => $target_bundle];
      $value['handler_settings']['target_bundles_drag_drop'][$target_bundle] = [
        'enabled' => TRUE,
        'weight' => ++$weight,
      ];
      unset($bundles[$target_bundle]);

      foreach ($bundles as $bundle_name => $bundle) {
        $value['handler_settings']['target_bundles_drag_drop'][$bundle_name] = [
          'enabled' => FALSE,
          'weight' => ++$weight,
        ];
        unset($bundles[$bundle_name]);
      }
    }

    return $value;
  }

}
