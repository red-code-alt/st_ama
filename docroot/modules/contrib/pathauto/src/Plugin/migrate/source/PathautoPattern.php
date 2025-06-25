<?php

namespace Drupal\pathauto\Plugin\migrate\source;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\State\StateInterface;
use Drupal\migrate\Plugin\MigrationDeriverTrait;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Fetches pathauto patterns from the source database.
 *
 * @MigrateSource(
 *   id = "pathauto_pattern",
 *   source_module = "pathauto",
 * )
 */
class PathautoPattern extends DrupalSqlBase implements ContainerFactoryPluginInterface {

  use MigrationDeriverTrait;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, StateInterface $state, EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler) {
    $configuration += [
      'entity_type' => NULL,
      'bundle' => NULL,
    ];
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $state, $entity_type_manager);

    $this->moduleHandler = $module_handler;
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
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    [
      'entity_type' => $entity_type,
      'bundle' => $bundle,
    ] = $this->configuration;
    // Fetch all pattern variables whose value is not a serialized empty string.
    $query = $this->select('variable', 'v')
      ->fields('v', ['name', 'value'])
      ->condition('value', serialize(''), '<>');

    // Exclude forum pattern if forum wasn't enabled on the source site.
    if (!$this->moduleExists('forum')) {
      $query->condition('name', 'pathauto_forum_pattern', '<>');
    }

    if (!$entity_type) {
      if ($bundle) {
        throw new \LogicException(sprintf('If "bundle" configuration is set for %s migration source plugin, "entity_type" configuration must also be defined.', get_class($this)));
      }
      // Fetch every pattern variable.
      $query->condition('name', 'pathauto_%_pattern', 'LIKE');
    }
    else {
      $forum_vocabulary = $this->getForumTaxonomyVocabularyMachineName();
      $pattern_id = $entity_type === 'taxonomy_term' && !empty($bundle) && $forum_vocabulary === $bundle
        ? 'forum'
        : implode('_', array_filter([
          $entity_type,
          $bundle,
        ]));

      // Entity types might have multilingual patterns.
      if ($bundle) {
        // For "node" entity type, the following conditions should match
        // "pathauto_node_foo_pattern", "pathauto_node_foo_en_pattern" and
        // "pathauto_node_foo_fr_pattern" where "foo" is the node type (bundle).
        $query->condition('name', "pathauto_{$pattern_id}%_pattern", 'LIKE');
        $query->condition('name', "pathauto_{$pattern_id}_%pattern", 'LIKE');
      }
      else {
        $or_group = $query->orConditionGroup()
          ->condition('name', "pathauto_{$entity_type}_%_pattern", 'LIKE')
          ->condition('name', "pathauto_{$entity_type}_pattern");
        $query->condition($or_group);
      }
    }

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  protected function initializeIterator() {
    $results = $this->prepareQuery()->execute()->fetchAll();
    $forum_vocabulary = $this->getForumTaxonomyVocabularyMachineName();
    // See Drupal 7 locale_language_list() and language_list().
    $languages = [];
    if ($this->moduleExists('locale')) {
      try {
        $language_source = static::getSourcePlugin('language');
        $language_source->checkRequirements();
        foreach ($language_source as $language_source_row) {
          assert($language_source_row instanceof Row);
          $langcode = $language_source_row->getSourceProperty('language');
          $languages[] = $langcode;
        }
        $languages = array_unique(
          array_merge(
            $languages,
            ['und']
          )
        );
      }
      catch (\Exception $e) {
      }
    }

    $rows = [];
    foreach ($results as $result) {
      preg_match('/^pathauto_(.+)_pattern$/', $result['name'], $matches);
      $row = $result + [
        'id' => $matches[1],
        'forum_vocabulary' => $matches[1] === 'forum' && $forum_vocabulary,
      ];

      if ($forum_vocabulary && $row['id'] === 'forum') {
        $row += [
          'entity_type' => 'taxonomy_term',
          'bundle' => $forum_vocabulary,
        ];
      }
      elseif ($forum_vocabulary && $row['id'] === "taxonomy_term_$forum_vocabulary") {
        // This pattern wasn't used by Drupal 7 pathauto.
        // @see https://git.drupalcode.org/project/pathauto/-/blob/7.x-1.x/pathauto.module#L825
        continue;
      }
      else {
        // Try to determine the destination entity_type.
        $variable_id_parts = explode('_', $row['id']);
        $provisioned_entity_type_id_parts = [];
        // This loop tries to find the destination entity type. If the ID is
        // "taxonomy_term_tags", then for first, it tries to find a content
        // entity definition with ID "taxonomy", then "taxonomy_term", and
        // finally "taxonomy_term_tags".
        foreach ($variable_id_parts as $variable_id_part) {
          $provisioned_entity_type_id_parts[] = $variable_id_part;
          $provisioned_entity_type_id = implode('_', $provisioned_entity_type_id_parts);

          // File entity may have pattern if "pathauto_entity" was enabled on
          // the source. If "media" is installed, the pathauto patterm migration
          // will map these patterns to media entity types.
          if ($provisioned_entity_type_id === 'file' && $this->moduleHandler->moduleExists('media_migration')) {
            $row['entity_type'] = 'file';
            break;
          }
          elseif ($this->entityTypeManager->getDefinition($provisioned_entity_type_id, FALSE) instanceof ContentEntityTypeInterface) {
            $row['entity_type'] = $provisioned_entity_type_id;
            break;
          }
        }

        // If entity type was indeterminable, skip this row. This will happen
        // with the "blog" pattern: in Drupal 8 or Drupal 9, no equivalent
        // "thing" exists for this where the "pathauto_blog_pattern" could be
        // used.
        if (empty($row['entity_type'])) {
          continue;
        }

        // If entity type was determined, try to get the bundle and the language
        // code as well.
        if (
          $row['entity_type'] !== $row['id'] &&
          strpos($row['id'], $row['entity_type'] . '_') === 0
        ) {
          $bundle_and_language = substr($row['id'], strlen($row['entity_type']) + 1);

          $langcode = NULL;
          foreach ($languages as $available_langcode) {
            if (preg_match("/^(.+)_{$available_langcode}$/", $bundle_and_language, $langcode_matches)) {
              $langcode = $available_langcode;
              break 1;
            }
          }

          if (is_string($langcode)) {
            $substr_length = (strlen($langcode) + 1) * -1;
            $row['bundle'] = substr($bundle_and_language, 0, $substr_length);
            $row['langcode'] = $langcode;
          }
          else {
            $row['bundle'] = $bundle_and_language;
          }
        }
      }

      // If the current 'bundle' config is FALSE, then we want to skip results
      // which have bundle value, because this derivative should only migrate
      // the entity type's default (fallback) pattern.
      if ($this->configuration['bundle'] === FALSE && !empty($row['bundle'])) {
        continue;
      }

      // "Default" patterns (which do not have entity bundle restriction) should
      // get higher weight to act as a fallback. Patterns with language (and
      // bundle) restriction should prioritized over every other pattern.
      $weight = 1;
      if (!empty($row['bundle'])) {
        $weight--;
        // With Drupal 7 Pathauto, language specific patterns only work for
        // bundle-restricted patterns.
        if (!empty($row['langcode'])) {
          $weight--;
        }
      }
      $rows[] = $row + [
        'weight' => $weight,
      ];
    }

    return new \ArrayIterator($rows);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['name']['type'] = 'string';
    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'name' => $this->t("The name of the pattern's variable."),
      'value' => $this->t("The value of the pattern's variable."),
      'id' => $this->t('The ID of the destination pathauto pattern. This is the name without the "pathauto_" prefix and "_pattern" suffix'),
      'entity_type' => $this->t('The provisioned destination entity type ID of the pattern.'),
      'bundle' => $this->t('The provisioned destination entity bundle of the pattern, if any.'),
      'forum_vocabulary' => $this->t('Whether the current pattern belongs to the forum taxonomy vocabulary.'),
      'weight' => $this->t('The weight of the pattern'),
      'langcode' => $this->t('The language code (language ID) of the pattern'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function doCount() {
    // The number of variables fetched with query() does not match the number of
    // rows to migrate.
    return (int) $this->initializeIterator()->count();
  }

  /**
   * Returns the machine name of the forum navigation taxonomy on the source.
   *
   * @return string|null
   *   The machine name of the forum navigation taxonomy on the source.
   */
  protected function getForumTaxonomyVocabularyMachineName() {
    $forum_vocabulary = FALSE;
    if ($this->moduleExists('taxonomy') && $this->moduleExists('forum')) {
      $forum_vocabulary_id = $this->variableGet('forum_nav_vocabulary', NULL);
      try {
        if ($forum_vocabulary_id !== NULL) {
          $forum_vocabulary = $this->select('taxonomy_vocabulary', 'tv')
            ->fields('tv', ['machine_name'])
            ->condition('tv.vid', $forum_vocabulary_id)
            ->execute()
            ->fetchField();
        }
      }
      catch (\Exception $e) {
      }
    }

    return $forum_vocabulary ? $forum_vocabulary : NULL;
  }

}
