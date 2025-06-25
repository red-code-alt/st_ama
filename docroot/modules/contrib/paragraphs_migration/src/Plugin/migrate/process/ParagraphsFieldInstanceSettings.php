<?php

namespace Drupal\paragraphs_migration\Plugin\migrate\process;

use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateLookupInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure field instance settings for paragraphs.
 *
 * @MigrateProcessPlugin(
 *   id = "pm_paragraphs_field_instance_settings"
 * )
 */
class ParagraphsFieldInstanceSettings extends ProcessPluginBase implements ContainerFactoryPluginInterface {

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
    if ($row->getSourceProperty('type') === 'paragraphs') {
      $bundles = $this->entityTypeBundleInfo->getBundleInfo('paragraph');
      $target_bundles = [];

      if (!empty($value['allowed_bundles'])) {
        $source_allowed_bundles = array_filter($value['allowed_bundles'], function ($a) {
          return $a != -1;
        });
        foreach ($source_allowed_bundles as $source_bundle) {
          $target_bundle_lookup_results = $this->migrateLookup->lookup('d7_pm_paragraphs_type', (array) $source_bundle);
          if (!empty($target_bundle_lookup_results[0])) {
            $target_bundle = reset($target_bundle_lookup_results[0]);
            $target_bundles[$target_bundle] = $target_bundle;
          }
        }

        $value['handler_settings']['negate'] = 0;
        $value['handler_settings']['target_bundles'] = empty($target_bundles)
          ? NULL
          : $target_bundles;
      }
      unset($value['allowed_bundles']);

      if (!empty($value['bundle_weights'])) {

        // Copy the existing weights, and add any new bundles (either from
        // a field collection migration happening now, or pre-existing on the
        // site at the bottom.
        foreach ($value['bundle_weights'] as $bundle_name => $weight) {
          $value['handler_settings']['target_bundles_drag_drop'][$bundle_name] = [
            'enabled' => array_key_exists($bundle_name, $target_bundles),
            'weight' => $weight,
          ];
        }
        $other_bundles = array_keys(array_diff_key($bundles, $value['bundle_weights']));
        $weight = max($value['bundle_weights']);
        foreach ($other_bundles as $bundle_name) {
          $value['handler_settings']['target_bundles_drag_drop'][$bundle_name] = [
            'enabled' => array_key_exists($bundle_name, $target_bundles),
            'weight' => ++$weight,
          ];
        }
      }
      unset($value['bundle_weights']);
    }
    return $value;
  }

}
