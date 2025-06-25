<?php

namespace Drupal\paragraphs_migration\Plugin\migrate\source\d7;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\Plugin\MigrationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Migration source plugin for Multifield translation settings.
 *
 * @MigrateSource(
 *   id = "pm_multifield_translation_settings",
 *   source_module = "multifield"
 * )
 */
class MultifieldTranslationSettings extends MultifieldType {

  /**
   * Whether content translation is installed on the destination site.
   *
   * @var bool
   */
  protected $contentTranslationInstalled;

  /**
   * Constructs a BeanTranslationSettings instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The current migration.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param bool $content_translation_is_installed
   *   Whether Content Translation is installed on the destination site.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, StateInterface $state, EntityTypeManagerInterface $entity_type_manager, bool $content_translation_is_installed) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $state, $entity_type_manager);

    $this->contentTranslationInstalled = $content_translation_is_installed;
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
      $container->get('state'),
      $container->get('entity_type.manager'),
      $container->get('module_handler')->moduleExists('content_translation')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function initializeIterator() {
    $source_records = [];
    foreach (parent::initializeIterator() as $item) {
      $item += [
        'untranslatable_fields_hide' => 1,
      ];

      // If Content Translation isn't installed on the destination, then the
      // content_translation related third party settings would cause schema
      // errors.
      if (!$this->contentTranslationInstalled) {
        unset($item['translatable']);
        unset($item['untranslatable_fields_hide']);
      }

      $source_records[] = $item;
    }
    return new \ArrayIterator($source_records);
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return parent::fields() + [
      'untranslatable_fields_hide' => $this->t('Whether the untranslatable fields are hidden on the translation edit form.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function checkRequirements() {
    parent::checkRequirements();

    if (!$this->moduleExists('entity_translation')) {
      throw new RequirementsException('The Entity Translation module is not enabled in the source site.', [
        'source_module' => 'entity_translation',
      ]);
    }
  }

}
