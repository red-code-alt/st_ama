<?php

namespace Drupal\Tests\acquia_migrate\Functional;

use Drupal\acquia_migrate\UriDefinitions;
use Drupal\Core\Database\Database;

/**
 * Tests the Acquia Migrate HTTP API with the 'standard' profile + multilingual.
 *
 * Essential observations:
 * - Number of additional initial migration plugin rows.
 * - Every overridden expectation method calls ::dependsOnLanguage().
 * - Any new override that does not need this is most likely missing
 *   dependency.
 * - The order of the migrations in the collection should match that of the
 *   parent implementation *exactly*, if not, there are either missing
 *   dependencies or there is a bug/missing heuristic in the clusterer.
 *
 * @group acquia_migrate
 * @group acquia_migrate__core
 * @group acquia_migrate__mysql
 */
class HttpApiStandardMultilingualTest extends HttpApiStandardTest {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'language',
    'config_translation',
    'content_translation',
  ];

  /**
   * {@inheritdoc}
   */
  protected function loadFixture($path) {
    parent::loadFixture($path);

    // Override "Sujet de discussion" label to be truly multilingual, to allow
    // testing Unicode migration labels.
    $default_db = Database::getConnection()->getKey();
    Database::setActiveConnection($this->sourceDatabase->getKey());
    $connection = Database::getConnection();
    $connection->update('taxonomy_vocabulary')
      ->fields(['name' => 'Sujet de discussion énorme'])
      ->condition('vid', 2)
      ->execute();
    Database::setActiveConnection($default_db);
  }

  /**
   * {@inheritdoc}
   */
  public static function expectedServerTimingMigrationsDestDbQueryCount() : int {
    return parent::expectedServerTimingMigrationsDestDbQueryCount() + 288;
  }

  /**
   * {@inheritdoc}
   */
  public static function expectedInitialMigrationPluginTotalRowCount() : int {
    return parent::expectedInitialMigrationPluginTotalRowCount() + 106;
  }

  /**
   * {@inheritdoc}
   */
  public static function expectedServerTimingCountIdMapCount() : int {
    return parent::expectedServerTimingCountIdMapCount() + 392;
  }

  /**
   * {@inheritdoc}
   */
  protected function expectedMigrationCollectionData() : array {
    $expected = array_merge(
      [$this->expectedResourceObjectForLanguageSettings()],
      parent::expectedMigrationCollectionData()
    );
    // @see \Drupal\Tests\acquia_migrate\Functional\HttpApiStandardMultilingualTest::loadFixture()
    self::recursivelyReplace(
      $expected,
      [
        '77dbe7f5d22c6bebefc3475f5b9acba9-Sujet de discussion taxonomy terms',
        '/77dbe7f5d22c6bebefc3475f5b9acba9-Sujet%20de%20discussion%20taxonomy%20terms',
        'Sujet de discussion taxonomy terms',
      ],
      [
        '15a675c66f059cc65388d2536d49740a-Sujet de discussion énorme taxonomy terms',
        '/15a675c66f059cc65388d2536d49740a-Sujet%20de%20discussion%20%C3%A9norme%20taxonomy%20terms',
        'Sujet de discussion énorme taxonomy terms',
      ]
    );
    return $expected;
  }

  /**
   * Recursively replaces certain strings in a nested array.
   *
   * @param array &$haystack
   *   The nested array to update by reference.
   * @param string[] $value_needles
   *   The strings to search recursively.
   * @param string[] $value_replacements
   *   The corresponding replacement strings.
   */
  protected function recursivelyReplace(array &$haystack, array $value_needles, array $value_replacements): void {
    foreach ($haystack as $k => $v) {
      if (is_string($v)) {
        $haystack[$k] = str_replace($value_needles, $value_replacements, $v);
      }
      elseif (is_array($v)) {
        self::recursivelyReplace($haystack[$k], $value_needles, $value_replacements);
      }
    }
  }

  /**
   * Inserts value into indexed array at specified index.
   *
   * @param array $indexed_array
   *   The indexed array to manipulate, by reference.
   * @param int $index
   *   The index at which to insert the value.
   * @param mixed $value
   *   The value to insert.
   *
   * @throws \OutOfBoundsException
   *   When $index is not one of the existing indices or the next unused one.
   */
  private function insertAt(array &$indexed_array, int $index, $value) {
    if ($index < 0 || $index > count($indexed_array)) {
      throw new \OutOfBoundsException(sprintf("Index %d does not exist in the given array.", $index));
    }

    if ($index === count($indexed_array)) {
      $indexed_array[] = $value;
    }
    else {
      $indexed_array = array_merge(
        array_slice($indexed_array, 0, $index),
        [$value],
        array_slice($indexed_array, $index)
      );
    }
  }

  /**
   * Updates expected resource object to depend on "language" migration plugin.
   *
   * Inserted at beginning by default, since the "Language Settings" migration
   * gets sorted towards the very beginning.
   *
   * @param array $expected_resource_object
   *   The expected resource object to manipulate by reference.
   * @param int $index
   *   (optional) The relationship index.
   */
  private function dependsOnLanguage(array &$expected_resource_object, int $index = 0) {
    $migration_id = $this->expectedResourceObjectForLanguageSettings()['id'];
    $relationship = [
      'type' => 'migration',
      'id' => $migration_id,
      'meta' => [
        'dependencyReasons' => [
          'language',
        ],
      ],
    ];

    if ($index === -1) {
      $expected_resource_object['relationships']['dependencies']['data'][] = $relationship;
    }
    else {
      $this->insertAt($expected_resource_object['relationships']['dependencies']['data'], $index, $relationship);
    }
  }

  protected function expectedResourceObjectForLanguageSettings() {
    return [
      'type' => 'migration',
      'id' => 'b2e96197b823e728ee5a6be88da8f74b-Language settings',
      'attributes' => [
        'label' => 'Language settings',
        'importedCount' => 0,
        'processedCount' => 0,
        'totalCount' => 9,
        'completed' => FALSE,
        'stale' => FALSE,
        'skipped' => FALSE,
        'lastImported' => NULL,
        'activity' => 'idle',
      ],
      'relationships' => [
        'dependencies' => [
          'data' => [],
        ],
        'consistsOf' => [
          'data' => [
            ['type' => 'migrationPlugin', 'id' => 'language (0 of 3)'],
            ['type' => 'migrationPlugin', 'id' => 'default_language (0 of 1)'],
            ['type' => 'migrationPlugin', 'id' => 'd7_language_negotiation_settings (0 of 1)'],
            ['type' => 'migrationPlugin', 'id' => 'd7_language_types (0 of 1)'],
            ['type' => 'migrationPlugin', 'id' => 'language_prefixes_and_domains (0 of 3)'],
          ],
        ],
      ],
      'links' => [
        'self' => [
          'href' => $this->apiUrl('migrationIndividual', ['b2e96197b823e728ee5a6be88da8f74b-Language settings'])
            ->toString(),
        ],
        'import' => [
          'href' => $this->getMigrationStartHref('b2e96197b823e728ee5a6be88da8f74b-Language settings'),
          'title' => 'Import',
          'rel' => UriDefinitions::LINK_REL_START_BATCH_PROCESS,
        ],
        'skip' => [
          'href' => $this->apiUrl('migrationIndividual', ['b2e96197b823e728ee5a6be88da8f74b-Language settings'])
            ->toString(),
          'title' => 'Skip',
          'rel' => UriDefinitions::LINK_REL_UPDATE_RESOURCE,
          'params' => [
            'confirm' => "I'm sure, this does not need to be migrated, at least not for now.",
            'data' => [
              'type' => 'migration',
              'id' => 'b2e96197b823e728ee5a6be88da8f74b-Language settings',
              'attributes' => [
                'skipped' => TRUE,
              ],
            ],
          ],
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   *
   * Depends on "Language settings", 1 extra migration plugin, 2 extra rows.
   */
  protected function expectedResourceObjectForCustomBlocks() {
    $expected = parent::expectedResourceObjectForCustomBlocks();
    $expected['attributes']['totalCount'] += 2;
    $this->dependsOnLanguage($expected, 1);
    $expected['relationships']['consistsOf']['data'][] = [
      'type' => 'migrationPlugin',
      'id' => 'd7_custom_block_translation (0 of 2)',
    ];
    $expected['links']['preview-unmet-requirement:0']['title'] = 'Not all supporting configuration has been processed yet: Custom blocks (specifically: block_content_type, block_content_body_field, block_content_entity_display, block_content_entity_form_display), Filter format configuration (specifically: d7_filter_format), Language settings (specifically: language).';
    return $expected;
  }

  /**
   * {@inheritdoc}
   *
   * Depends on "Language settings", 2 extra migration plugin, 2 extra rows.
   */
  protected function expectedResourceObjectForUserAccounts() {
    $expected = parent::expectedResourceObjectForUserAccounts();
    $expected['attributes']['totalCount'] += 2;
    $this->dependsOnLanguage($expected);
    $this->insertAt($expected['relationships']['consistsOf']['data'], 7, [
      'type' => 'migrationPlugin',
      'id' => 'd7_entity_translation_settings:user:user',
    ]);
    $expected['relationships']['consistsOf']['data'][] = [
      'type' => 'migrationPlugin',
      'id' => 'd7_user_entity_translation (0 of 2)',
    ];
    unset($expected['links']['import']);
    $expected['links']['preview-unmet-requirement:0']['title'] = 'Not all supporting configuration has been processed yet: User accounts (specifically: d7_user_role, d7_field:user, d7_field_instance:user:user, user_picture_field, user_picture_field_instance, user_picture_entity_display, user_picture_entity_form_display, d7_entity_translation_settings:user:user, d7_view_modes:user, d7_field_formatter_settings:user:user, d7_field_instance_widget_settings:user:user), Language settings (specifically: language).';
    return $expected;
  }

  /**
   * {@inheritdoc}
   *
   * Depends on "Language settings", 4 extra migration plugins, no extra rows.
   */
  protected function expectedResourceObjectForSujetDeDiscussionTaxonomyTerms() {
    $expected = parent::expectedResourceObjectForSujetDeDiscussionTaxonomyTerms();
    $this->dependsOnLanguage($expected);
    $this->insertAt($expected['relationships']['consistsOf']['data'], 1, [
      'type' => 'migrationPlugin',
      'id' => 'd7_language_content_taxonomy_vocabulary_settings:sujet_de_discussion',
    ]);
    $expected['relationships']['consistsOf']['data'][] = [
      'type' => 'migrationPlugin',
      'id' => 'd7_taxonomy_term_entity_translation:sujet_de_discussion (0 of 0)',
    ];
    $expected['relationships']['consistsOf']['data'][] = [
      'type' => 'migrationPlugin',
      'id' => 'd7_taxonomy_term_localized_translation:sujet_de_discussion (0 of 0)',
    ];
    $expected['relationships']['consistsOf']['data'][] = [
      'type' => 'migrationPlugin',
      'id' => 'd7_taxonomy_term_translation:sujet_de_discussion (0 of 0)',
    ];
    unset($expected['links']['import']);
    $expected['links']['preview-unmet-requirement:0']['title'] = 'Not all supporting configuration has been processed yet: Sujet de discussion taxonomy terms (specifically: d7_taxonomy_vocabulary:sujet_de_discussion, d7_language_content_taxonomy_vocabulary_settings:sujet_de_discussion), Language settings (specifically: language).';
    return $expected;
  }

  /**
   * {@inheritdoc}
   *
   * Depends on "Language settings", 4 extra migration plugins, no extra rows.
   */
  protected function expectedResourceObjectForTagsTaxonomyTerms() {
    $expected = parent::expectedResourceObjectForTagsTaxonomyTerms();
    $this->dependsOnLanguage($expected);
    $this->insertAt($expected['relationships']['consistsOf']['data'], 1, [
      'type' => 'migrationPlugin',
      'id' => 'd7_language_content_taxonomy_vocabulary_settings:tags',
    ]);
    $expected['relationships']['consistsOf']['data'][] = [
      'type' => 'migrationPlugin',
      'id' => 'd7_taxonomy_term_entity_translation:tags (0 of 0)',
    ];
    $expected['relationships']['consistsOf']['data'][] = [
      'type' => 'migrationPlugin',
      'id' => 'd7_taxonomy_term_localized_translation:tags (0 of 0)',
    ];
    $expected['relationships']['consistsOf']['data'][] = [
      'type' => 'migrationPlugin',
      'id' => 'd7_taxonomy_term_translation:tags (0 of 0)',
    ];
    unset($expected['links']['import']);
    $expected['links']['preview-unmet-requirement:0']['title'] = 'Not all supporting configuration has been processed yet: Tags taxonomy terms (specifically: d7_taxonomy_vocabulary:tags, d7_language_content_taxonomy_vocabulary_settings:tags), Language settings (specifically: language).';
    return $expected;
  }

  /**
   * {@inheritdoc}
   *
   * Depends on "Language settings", 6 extra migration plugins, 2 extra rows.
   */
  protected function expectedResourceObjectForTestVocabularyTaxonomyTerms() {
    $expected = parent::expectedResourceObjectForTestVocabularyTaxonomyTerms();
    $expected['attributes']['totalCount'] += 2;
    $this->dependsOnLanguage($expected);
    $this->insertAt($expected['relationships']['consistsOf']['data'], 1, [
      'type' => 'migrationPlugin',
      'id' => 'd7_language_content_taxonomy_vocabulary_settings:test_vocabulary',
    ]);
    $this->insertAt($expected['relationships']['consistsOf']['data'], 3, [
      'type' => 'migrationPlugin',
      'id' => 'd7_entity_translation_settings:taxonomy_term:test_vocabulary',
    ]);
    $this->insertAt($expected['relationships']['consistsOf']['data'], 5, [
      'type' => 'migrationPlugin',
      'id' => 'd7_field_instance_label_description_translation:taxonomy_term:test_vocabulary',
    ]);
    $expected['relationships']['consistsOf']['data'][] = [
      'type' => 'migrationPlugin',
      'id' => 'd7_taxonomy_term_entity_translation:test_vocabulary (0 of 2)',
    ];
    $expected['relationships']['consistsOf']['data'][] = [
      'type' => 'migrationPlugin',
      'id' => 'd7_taxonomy_term_localized_translation:test_vocabulary (0 of 0)',
    ];
    $expected['relationships']['consistsOf']['data'][] = [
      'type' => 'migrationPlugin',
      'id' => 'd7_taxonomy_term_translation:test_vocabulary (0 of 0)',
    ];
    $expected['links']['preview-unmet-requirement:0']['title'] = 'Not all supporting configuration has been processed yet: Test Vocabulary taxonomy terms (specifically: d7_taxonomy_vocabulary:test_vocabulary, d7_language_content_taxonomy_vocabulary_settings:test_vocabulary, d7_field_instance:taxonomy_term:test_vocabulary, d7_entity_translation_settings:taxonomy_term:test_vocabulary, d7_field_formatter_settings:taxonomy_term:test_vocabulary, d7_field_instance_label_description_translation:taxonomy_term:test_vocabulary, d7_field_instance_widget_settings:taxonomy_term:test_vocabulary), Language settings (specifically: language), Shared structure for taxonomy terms (specifically: d7_field:taxonomy_term, d7_view_modes:taxonomy_term).';
    return $expected;
  }

  /**
   * {@inheritdoc}
   *
   * Depends on "Language settings", 5 extra migration plugins, 1 extra row.
   */
  protected function expectedResourceObjectForVocabFixedTaxonomyTerms() {
    $expected = parent::expectedResourceObjectForVocabFixedTaxonomyTerms();
    $expected['attributes']['totalCount'] += 1;
    $this->dependsOnLanguage($expected);
    $this->insertAt($expected['relationships']['consistsOf']['data'], 1, [
      'type' => 'migrationPlugin',
      'id' => 'd7_language_content_taxonomy_vocabulary_settings:vocabfixed',
    ]);
    $this->insertAt($expected['relationships']['consistsOf']['data'], 2, [
      'type' => 'migrationPlugin',
      'id' => 'd7_taxonomy_vocabulary_translation:vocabfixed',
    ]);
    $expected['relationships']['consistsOf']['data'][] = [
      'type' => 'migrationPlugin',
      'id' => 'd7_taxonomy_term_entity_translation:vocabfixed (0 of 0)',
    ];
    $expected['relationships']['consistsOf']['data'][] = [
      'type' => 'migrationPlugin',
      'id' => 'd7_taxonomy_term_localized_translation:vocabfixed (0 of 0)',
    ];
    $expected['relationships']['consistsOf']['data'][] = [
      'type' => 'migrationPlugin',
      'id' => 'd7_taxonomy_term_translation:vocabfixed (0 of 1)',
    ];
    $expected['links']['preview-unmet-requirement:0']['title'] = 'Not all supporting configuration has been processed yet: VocabFixed taxonomy terms (specifically: d7_taxonomy_vocabulary:vocabfixed, d7_language_content_taxonomy_vocabulary_settings:vocabfixed, d7_taxonomy_vocabulary_translation:vocabfixed, d7_field_instance:taxonomy_term:vocabfixed, d7_field_formatter_settings:taxonomy_term:vocabfixed, d7_field_instance_widget_settings:taxonomy_term:vocabfixed), Language settings (specifically: language), Shared structure for taxonomy terms (specifically: d7_field:taxonomy_term, d7_view_modes:taxonomy_term).';
    return $expected;
  }

  /**
   * {@inheritdoc}
   *
   * Depends on "Language settings", 4 extra migration plugins, 1 extra row.
   */
  protected function expectedResourceObjectForVocabLocalized2TaxonomyTerms() {
    $expected = parent::expectedResourceObjectForVocabLocalized2TaxonomyTerms();
    $expected['attributes']['totalCount'] += 1;
    $this->dependsOnLanguage($expected);
    $this->insertAt($expected['relationships']['consistsOf']['data'], 1, [
      'type' => 'migrationPlugin',
      'id' => 'd7_language_content_taxonomy_vocabulary_settings:vocablocalized2',
    ]);
    $expected['relationships']['consistsOf']['data'][] = [
      'type' => 'migrationPlugin',
      'id' => 'd7_taxonomy_term_entity_translation:vocablocalized2 (0 of 0)',
    ];
    $expected['relationships']['consistsOf']['data'][] = [
      'type' => 'migrationPlugin',
      'id' => 'd7_taxonomy_term_localized_translation:vocablocalized2 (0 of 1)',
    ];
    $expected['relationships']['consistsOf']['data'][] = [
      'type' => 'migrationPlugin',
      'id' => 'd7_taxonomy_term_translation:vocablocalized2 (0 of 0)',
    ];
    unset($expected['links']['import']);
    $expected['links']['preview-unmet-requirement:0']['title'] = 'Not all supporting configuration has been processed yet: VocabLocalized2 taxonomy terms (specifically: d7_taxonomy_vocabulary:vocablocalized2, d7_language_content_taxonomy_vocabulary_settings:vocablocalized2), Language settings (specifically: language).';
    return $expected;
  }

  /**
   * {@inheritdoc}
   *
   * Depends on "Language settings", 4 extra migration plugins, 2 extra rows.
   */
  protected function expectedResourceObjectForVocabLocalizedTaxonomyTerms() {
    $expected = parent::expectedResourceObjectForVocabLocalizedTaxonomyTerms();
    $expected['attributes']['totalCount'] += 2;
    $this->dependsOnLanguage($expected);
    $this->insertAt($expected['relationships']['consistsOf']['data'], 1, [
      'type' => 'migrationPlugin',
      'id' => 'd7_language_content_taxonomy_vocabulary_settings:vocablocalized',
    ]);
    $this->insertAt($expected['relationships']['consistsOf']['data'], 2, [
      'type' => 'migrationPlugin',
      'id' => 'd7_taxonomy_vocabulary_translation:vocablocalized',
    ]);
    $expected['relationships']['consistsOf']['data'][] = [
      'type' => 'migrationPlugin',
      'id' => 'd7_taxonomy_term_entity_translation:vocablocalized (0 of 0)',
    ];
    $expected['relationships']['consistsOf']['data'][] = [
      'type' => 'migrationPlugin',
      'id' => 'd7_taxonomy_term_localized_translation:vocablocalized (0 of 2)',
    ];
    $expected['relationships']['consistsOf']['data'][] = [
      'type' => 'migrationPlugin',
      'id' => 'd7_taxonomy_term_translation:vocablocalized (0 of 0)',
    ];
    $expected['links']['preview-unmet-requirement:0']['title'] = 'Not all supporting configuration has been processed yet: VocabLocalized taxonomy terms (specifically: d7_taxonomy_vocabulary:vocablocalized, d7_language_content_taxonomy_vocabulary_settings:vocablocalized, d7_taxonomy_vocabulary_translation:vocablocalized, d7_field_instance:taxonomy_term:vocablocalized, d7_field_formatter_settings:taxonomy_term:vocablocalized, d7_field_instance_widget_settings:taxonomy_term:vocablocalized), Language settings (specifically: language), Shared structure for taxonomy terms (specifically: d7_field:taxonomy_term, d7_view_modes:taxonomy_term).';
    return $expected;
  }

  /**
   * {@inheritdoc}
   *
   * Depends on "Language settings", 5 extra migration plugins, 3 extra rows.
   */
  protected function expectedResourceObjectForVocabTranslateTaxonomyTerms() {
    $expected = parent::expectedResourceObjectForVocabTranslateTaxonomyTerms();
    $expected['attributes']['totalCount'] += 3;
    $this->dependsOnLanguage($expected);
    $this->insertAt($expected['relationships']['consistsOf']['data'], 1, [
      'type' => 'migrationPlugin',
      'id' => 'd7_language_content_taxonomy_vocabulary_settings:vocabtranslate',
    ]);
    $this->insertAt($expected['relationships']['consistsOf']['data'], 2, [
      'type' => 'migrationPlugin',
      'id' => 'd7_taxonomy_vocabulary_translation:vocabtranslate',
    ]);
    $expected['relationships']['consistsOf']['data'][] = [
      'type' => 'migrationPlugin',
      'id' => 'd7_taxonomy_term_entity_translation:vocabtranslate (0 of 0)',
    ];
    $expected['relationships']['consistsOf']['data'][] = [
      'type' => 'migrationPlugin',
      'id' => 'd7_taxonomy_term_localized_translation:vocabtranslate (0 of 0)',
    ];
    $expected['relationships']['consistsOf']['data'][] = [
      'type' => 'migrationPlugin',
      'id' => 'd7_taxonomy_term_translation:vocabtranslate (0 of 3)',
    ];
    $expected['links']['preview-unmet-requirement:0']['title'] = 'Not all supporting configuration has been processed yet: VocabTranslate taxonomy terms (specifically: d7_taxonomy_vocabulary:vocabtranslate, d7_language_content_taxonomy_vocabulary_settings:vocabtranslate, d7_taxonomy_vocabulary_translation:vocabtranslate, d7_field_instance:taxonomy_term:vocabtranslate, d7_field_formatter_settings:taxonomy_term:vocabtranslate, d7_field_instance_widget_settings:taxonomy_term:vocabtranslate), Language settings (specifically: language), Shared structure for taxonomy terms (specifically: d7_field:taxonomy_term, d7_view_modes:taxonomy_term).';
    return $expected;
  }

  /**
   * {@inheritdoc}
   *
   * Depends on "Language settings", 2 extra migration plugins, 1 replaced
   * migration plugin, no extra rows, last two data migration plugins flipped
   * order.
   */
  protected function expectedResourceObjectForArticle() {
    $expected = parent::expectedResourceObjectForArticle();
    $this->dependsOnLanguage($expected);
    $this->insertAt($expected['relationships']['consistsOf']['data'], 1, [
      'type' => 'migrationPlugin',
      'id' => 'd7_language_content_settings:article',
    ]);
    $this->insertAt($expected['relationships']['consistsOf']['data'], 4, [
      'type' => 'migrationPlugin',
      'id' => 'd7_field_instance_label_description_translation:node:article',
    ]);
    $this->insertAt($expected['relationships']['consistsOf']['data'], 5, [
      'type' => 'migrationPlugin',
      'id' => 'd7_field_instance_option_translation:node:article',
    ]);
    $this->insertAt($expected['relationships']['consistsOf']['data'], 7, [
      'type' => 'migrationPlugin',
      'id' => 'd7_field_option_translation:node:article',
    ]);
    $last = array_pop($expected['relationships']['consistsOf']['data']);
    $second_last = array_pop($expected['relationships']['consistsOf']['data']);
    $expected['relationships']['consistsOf']['data'][] = $last;
    $expected['relationships']['consistsOf']['data'][] = $second_last;
    $expected['relationships']['consistsOf']['data'][10]['id'] = str_replace('d7_menu_links:node:article', 'node_translation_menu_links:node:article', $expected['relationships']['consistsOf']['data'][10]['id']);
    $expected['links']['preview-unmet-requirement:0']['title'] = 'Not all supporting configuration has been processed yet: Article (specifically: d7_node_type:article, d7_language_content_settings:article, d7_field_instance:node:article, d7_field_formatter_settings:node:article, d7_field_instance_label_description_translation:node:article, d7_field_instance_option_translation:node:article, d7_field_instance_widget_settings:node:article, d7_field_option_translation:node:article), Language settings (specifically: language), Shared structure for content items (specifically: d7_field:node, d7_view_modes:node), Filter format configuration (specifically: d7_filter_format), Shared structure for menus (specifically: d7_menu).';
    return $expected;
  }

  /**
   * {@inheritdoc}
   *
   * Depends on "Language settings", 1 extra migration plugin, no extra rows.
   */
  protected function expectedResourceObjectForBasicPage() {
    $expected = parent::expectedResourceObjectForBasicPage();
    $this->dependsOnLanguage($expected);
    $this->insertAt($expected['relationships']['consistsOf']['data'], 1, [
      'type' => 'migrationPlugin',
      'id' => 'd7_language_content_settings:page',
    ]);
    $expected['links']['preview-unmet-requirement:0']['title'] = 'Not all supporting configuration has been processed yet: Basic page (specifically: d7_node_type:page, d7_language_content_settings:page, d7_field_instance:node:page, d7_field_formatter_settings:node:page, d7_field_instance_widget_settings:node:page), Language settings (specifically: language), Shared structure for content items (specifically: d7_field:node, d7_view_modes:node), Filter format configuration (specifically: d7_filter_format).';
    return $expected;
  }

  /**
   * {@inheritdoc}
   *
   * Depends on "Language settings", 4 extra migration plugins, no extra rows.
   */
  protected function expectedResourceObjectForBlogEntry() {
    $expected = parent::expectedResourceObjectForBlogEntry();
    $this->dependsOnLanguage($expected);
    $this->insertAt($expected['relationships']['consistsOf']['data'], 1, [
      'type' => 'migrationPlugin',
      'id' => 'd7_language_content_settings:blog',
    ]);
    $this->insertAt($expected['relationships']['consistsOf']['data'], 4, [
      'type' => 'migrationPlugin',
      'id' => 'd7_field_instance_label_description_translation:node:blog',
    ]);
    $this->insertAt($expected['relationships']['consistsOf']['data'], 5, [
      'type' => 'migrationPlugin',
      'id' => 'd7_field_instance_option_translation:node:blog',
    ]);
    $this->insertAt($expected['relationships']['consistsOf']['data'], 7, [
      'type' => 'migrationPlugin',
      'id' => 'd7_field_option_translation:node:blog',
    ]);
    $expected['links']['preview-unmet-requirement:0']['title'] = 'Not all supporting configuration has been processed yet: Blog entry (specifically: d7_node_type:blog, d7_language_content_settings:blog, d7_field_instance:node:blog, d7_field_formatter_settings:node:blog, d7_field_instance_label_description_translation:node:blog, d7_field_instance_option_translation:node:blog, d7_field_instance_widget_settings:node:blog, d7_field_option_translation:node:blog), Language settings (specifically: language), Shared structure for content items (specifically: d7_field:node, d7_view_modes:node), Filter format configuration (specifically: d7_filter_format).';
    return $expected;
  }

  /**
   * {@inheritdoc}
   *
   * Depends on "Language settings", 1 extra migration plugin, no extra rows.
   */
  protected function expectedResourceObjectForBookPage() {
    $expected = parent::expectedResourceObjectForBookPage();
    $this->dependsOnLanguage($expected);
    $this->insertAt($expected['relationships']['consistsOf']['data'], 1, [
      'type' => 'migrationPlugin',
      'id' => 'd7_language_content_settings:book',
    ]);
    return $expected;
  }

  /**
   * {@inheritdoc}
   *
   * Depends on "Language settings", 2 extra migration plugins, no extra rows.
   */
  protected function expectedResourceObjectForEntityTranslationTest() {
    $expected = parent::expectedResourceObjectForEntityTranslationTest();
    $this->dependsOnLanguage($expected);
    $this->insertAt($expected['relationships']['consistsOf']['data'], 1, [
      'type' => 'migrationPlugin',
      'id' => 'd7_language_content_settings:et',
    ]);
    $this->insertAt($expected['relationships']['consistsOf']['data'], 3, [
      'type' => 'migrationPlugin',
      'id' => 'd7_entity_translation_settings:node:et',
    ]);
    $expected['links']['preview-unmet-requirement:0']['title'] = 'Not all supporting configuration has been processed yet: Entity translation test (specifically: d7_node_type:et, d7_language_content_settings:et, d7_field_instance:node:et, d7_entity_translation_settings:node:et, d7_field_formatter_settings:node:et, d7_field_instance_widget_settings:node:et), Language settings (specifically: language), Shared structure for content items (specifically: d7_field:node, d7_view_modes:node).';
    return $expected;
  }

  /**
   * {@inheritdoc}
   *
   * Depends on "Language settings", 1 extra migration plugin, no extra rows.
   */
  protected function expectedResourceObjectForForumTopic() {
    $expected = parent::expectedResourceObjectForForumTopic();
    $this->dependsOnLanguage($expected);
    $this->insertAt($expected['relationships']['consistsOf']['data'], 1, [
      'type' => 'migrationPlugin',
      'id' => 'd7_language_content_settings:forum',
    ]);
    $expected['links']['preview-unmet-requirement:0']['title'] = 'Not all supporting configuration has been processed yet: Forum topic (specifically: d7_node_type:forum, d7_language_content_settings:forum, d7_node_title_label:forum, d7_field_instance:node:forum, d7_field_formatter_settings:node:forum, d7_field_instance_widget_settings:node:forum), Language settings (specifically: language), Shared structure for content items (specifically: d7_field:node, d7_view_modes:node), Filter format configuration (specifically: d7_filter_format).';
    return $expected;
  }

  /**
   * {@inheritdoc}
   *
   * Depends on "Language settings", 5 extra migration plugins, no extra rows.
   */
  protected function expectedResourceObjectForTestContentType() {
    $expected = parent::expectedResourceObjectForTestContentType();
    $this->dependsOnLanguage($expected);
    $this->insertAt($expected['relationships']['consistsOf']['data'], 1, [
      'type' => 'migrationPlugin',
      'id' => 'd7_language_content_settings:test_content_type',
    ]);
    $this->insertAt($expected['relationships']['consistsOf']['data'], 3, [
      'type' => 'migrationPlugin',
      'id' => 'd7_entity_translation_settings:node:test_content_type',
    ]);
    $this->insertAt($expected['relationships']['consistsOf']['data'], 5, [
      'type' => 'migrationPlugin',
      'id' => 'd7_field_instance_label_description_translation:node:test_content_type',
    ]);
    $this->insertAt($expected['relationships']['consistsOf']['data'], 6, [
      'type' => 'migrationPlugin',
      'id' => 'd7_field_instance_option_translation:node:test_content_type',
    ]);
    $this->insertAt($expected['relationships']['consistsOf']['data'], 8, [
      'type' => 'migrationPlugin',
      'id' => 'd7_field_option_translation:node:test_content_type',
    ]);
    $expected['links']['preview-unmet-requirement:0']['title'] = 'Not all supporting configuration has been processed yet: Test content type (specifically: d7_node_type:test_content_type, d7_language_content_settings:test_content_type, d7_field_instance:node:test_content_type, d7_entity_translation_settings:node:test_content_type, d7_field_formatter_settings:node:test_content_type, d7_field_instance_label_description_translation:node:test_content_type, d7_field_instance_option_translation:node:test_content_type, d7_field_instance_widget_settings:node:test_content_type, d7_field_option_translation:node:test_content_type), Language settings (specifically: language), Shared structure for content items (specifically: d7_field:node, d7_view_modes:node), Filter format configuration (specifically: d7_filter_format).';
    return $expected;
  }

  /**
   * {@inheritdoc}
   *
   * Depends on "Language settings", 4 extra migration plugin, no extra rows.
   */
  protected function expectedResourceObjectForLongVocabularyNameTaxonomyTerms() {
    $expected = parent::expectedResourceObjectForLongVocabularyNameTaxonomyTerms();
    $this->dependsOnLanguage($expected);
    $this->insertAt($expected['relationships']['consistsOf']['data'], 1, [
      'type' => 'migrationPlugin',
      'id' => 'd7_language_content_taxonomy_vocabulary_settings:vocabulary_name_much_longer_than_thirty_two_characters',
    ]);
    $this->insertAt($expected['relationships']['consistsOf']['data'], 3, [
      'type' => 'migrationPlugin',
      'id' => 'd7_taxonomy_term_entity_translation:vocabulary_name_much_longer_than_thirty_two_characters (0 of 0)',
    ]);
    $this->insertAt($expected['relationships']['consistsOf']['data'], 4, [
      'type' => 'migrationPlugin',
      'id' => 'd7_taxonomy_term_localized_translation:vocabulary_name_much_longer_than_thirty_two_characters (0 of 0)',
    ]);
    $this->insertAt($expected['relationships']['consistsOf']['data'], 5, [
      'type' => 'migrationPlugin',
      'id' => 'd7_taxonomy_term_translation:vocabulary_name_much_longer_than_thirty_two_characters (0 of 0)',
    ]);
    return $expected;
  }

  /**
   * {@inheritdoc}
   *
   * Depends on "Language settings", 2 extra migration plugins, no extra rows.
   */
  protected function expectedResourceObjectForArticleComments() {
    $expected = parent::expectedResourceObjectForArticleComments();
    $this->dependsOnLanguage($expected, 1);
    $this->insertAt($expected['relationships']['consistsOf']['data'], 6, [
      'type' => 'migrationPlugin',
      'id' => 'd7_language_content_comment_settings:article',
    ]);
    $this->insertAt($expected['relationships']['consistsOf']['data'], 11, [
      'type' => 'migrationPlugin',
      'id' => 'd7_comment_entity_translation:article (0 of 0)',
    ]);
    $expected['links']['preview-unmet-requirement:0']['title'] = 'Not all supporting configuration has been processed yet: Article comments (specifically: d7_comment_type:article, d7_comment_field:article, d7_comment_field_instance:article, d7_comment_entity_display:article, d7_comment_entity_form_display:article, d7_comment_entity_form_display_subject:article, d7_language_content_comment_settings:article, d7_field_instance:comment:article, d7_field_formatter_settings:comment:article, d7_field_instance_widget_settings:comment:article), Article (specifically: d7_node_type:article), Language settings (specifically: language), Shared structure for comments (specifically: d7_field:comment, d7_view_modes:comment), Filter format configuration (specifically: d7_filter_format).';
    return $expected;
  }

  /**
   * {@inheritdoc}
   *
   * Depends on "Language settings", 3 extra migration plugins, no extra rows.
   */
  protected function expectedResourceObjectForBasicPageComments() {
    $expected = parent::expectedResourceObjectForBasicPageComments();
    $this->dependsOnLanguage($expected, 1);
    $this->insertAt($expected['relationships']['consistsOf']['data'], 6, [
      'type' => 'migrationPlugin',
      'id' => 'd7_language_content_comment_settings:page',
    ]);
    $this->insertAt($expected['relationships']['consistsOf']['data'], 9, [
      'type' => 'migrationPlugin',
      'id' => 'd7_field_instance_label_description_translation:comment:page',
    ]);
    $this->insertAt($expected['relationships']['consistsOf']['data'], 12, [
      'type' => 'migrationPlugin',
      'id' => 'd7_comment_entity_translation:page (0 of 0)',
    ]);
    return $expected;
  }

  /**
   * {@inheritdoc}
   *
   * Depends on "Language settings", 2 extra migration plugin, no extra rows.
   */
  protected function expectedResourceObjectForBlogEntryComments() {
    $expected = parent::expectedResourceObjectForBlogEntryComments();
    $this->dependsOnLanguage($expected, 1);
    $this->insertAt($expected['relationships']['consistsOf']['data'], 6, [
      'type' => 'migrationPlugin',
      'id' => 'd7_language_content_comment_settings:blog',
    ]);
    $this->insertAt($expected['relationships']['consistsOf']['data'], 11, [
      'type' => 'migrationPlugin',
      'id' => 'd7_comment_entity_translation:blog (0 of 0)',
    ]);
    return $expected;
  }

  /**
   * {@inheritdoc}
   *
   * Depends on "Language settings", 2 extra migration plugin, no extra rows.
   */
  protected function expectedResourceObjectForBookPageComments() {
    $expected = parent::expectedResourceObjectForBookPageComments();
    $this->dependsOnLanguage($expected, 1);
    $this->insertAt($expected['relationships']['consistsOf']['data'], 6, [
      'type' => 'migrationPlugin',
      'id' => 'd7_language_content_comment_settings:book',
    ]);
    $this->insertAt($expected['relationships']['consistsOf']['data'], 11, [
      'type' => 'migrationPlugin',
      'id' => 'd7_comment_entity_translation:book (0 of 0)',
    ]);
    return $expected;
  }

  /**
   * {@inheritdoc}
   *
   * Depends on "Language settings", 3 extra migration plugin, no extra rows.
   */
  protected function expectedResourceObjectForEntityTranslationTestComments() {
    $expected = parent::expectedResourceObjectForEntityTranslationTestComments();
    $this->dependsOnLanguage($expected, 1);
    $this->insertAt($expected['relationships']['consistsOf']['data'], 6, [
      'type' => 'migrationPlugin',
      'id' => 'd7_language_content_comment_settings:et',
    ]);
    $this->insertAt($expected['relationships']['consistsOf']['data'], 8, [
      'type' => 'migrationPlugin',
      'id' => 'd7_entity_translation_settings:comment:comment_node_et',
    ]);
    $expected['relationships']['consistsOf']['data'][] = [
      'type' => 'migrationPlugin',
      'id' => 'd7_comment_entity_translation:et (0 of 0)',
    ];
    return $expected;
  }

  /**
   * {@inheritdoc}
   *
   * Depends on "Language settings", 2 extra migration plugin, no extra rows.
   */
  protected function expectedResourceObjectForForumTopicComments() {
    $expected = parent::expectedResourceObjectForForumTopicComments();
    $this->dependsOnLanguage($expected, 1);
    $this->insertAt($expected['relationships']['consistsOf']['data'], 6, [
      'type' => 'migrationPlugin',
      'id' => 'd7_language_content_comment_settings:forum',
    ]);
    $this->insertAt($expected['relationships']['consistsOf']['data'], 11, [
      'type' => 'migrationPlugin',
      'id' => 'd7_comment_entity_translation:forum (0 of 0)',
    ]);
    return $expected;
  }

  /**
   * {@inheritdoc}
   *
   * Depends on "Language settings", 3 extra migration plugin, 2 extra rows.
   */
  protected function expectedResourceObjectForTestContentTypeComments() {
    $expected = parent::expectedResourceObjectForTestContentTypeComments();
    $expected['attributes']['totalCount'] += 2;
    $this->dependsOnLanguage($expected, 1);
    $this->insertAt($expected['relationships']['consistsOf']['data'], 6, [
      'type' => 'migrationPlugin',
      'id' => 'd7_language_content_comment_settings:test_content_type',
    ]);
    $this->insertAt($expected['relationships']['consistsOf']['data'], 8, [
      'type' => 'migrationPlugin',
      'id' => 'd7_entity_translation_settings:comment:comment_node_test_content_type',
    ]);
    $expected['relationships']['consistsOf']['data'][] = [
      'type' => 'migrationPlugin',
      'id' => 'd7_comment_entity_translation:test_content_type (0 of 2)',
    ];
    $expected['links']['preview-unmet-requirement:0']['title'] = 'Not all supporting configuration has been processed yet: Test content type comments (specifically: d7_comment_type:test_content_type, d7_comment_field:test_content_type, d7_comment_field_instance:test_content_type, d7_comment_entity_display:test_content_type, d7_comment_entity_form_display:test_content_type, d7_comment_entity_form_display_subject:test_content_type, d7_language_content_comment_settings:test_content_type, d7_field_instance:comment:test_content_type, d7_entity_translation_settings:comment:comment_node_test_content_type, d7_field_formatter_settings:comment:test_content_type, d7_field_instance_widget_settings:comment:test_content_type), Test content type (specifically: d7_node_type:test_content_type), Language settings (specifically: language), Shared structure for comments (specifically: d7_field:comment, d7_view_modes:comment), Filter format configuration (specifically: d7_filter_format).';
    return $expected;
  }

  /**
   * {@inheritdoc}
   *
   * Depends on "Language settings", 1 extra migration plugin, 6 extra rows.
   */
  protected function expectedResourceObjectForSharedStructureForMenus() {
    $expected = parent::expectedResourceObjectForSharedStructureForMenus();
    $expected['attributes']['totalCount'] += 6;
    $this->dependsOnLanguage($expected);
    $this->insertAt($expected['relationships']['consistsOf']['data'], 0, [
      'type' => 'migrationPlugin',
      'id' => 'd7_language_content_menu_settings (0 of 1)',
    ]);
    $expected['relationships']['consistsOf']['data'][] = [
      'type' => 'migrationPlugin',
      'id' => 'd7_menu_translation (0 of 5)',
    ];
    unset($expected['links']['import']);
    return $expected;
  }

  /**
   * {@inheritdoc}
   *
   * Depends on "Language settings", 5 extra migration plugins, 6 extra rows.
   */
  protected function expectedResourceObjectForSiteConfiguration() {
    $expected = parent::expectedResourceObjectForSiteConfiguration();

    $expected['attributes']['totalCount'] += 6;
    $this->dependsOnLanguage($expected);

    // Remove d7_user_mail, keep it for reinsertion later.
    $d7_user_mail = $expected['relationships']['consistsOf']['data'][19];
    $this->assertSame('d7_user_mail (0 of 1)', $d7_user_mail['id']);
    unset($expected['relationships']['consistsOf']['data'][19]);
    // Remove d7_user_settings, keep it for reinsertion later.
    $d7_user_settings = $expected['relationships']['consistsOf']['data'][20];
    $this->assertSame('d7_user_settings (0 of 1)', $d7_user_settings['id']);
    unset($expected['relationships']['consistsOf']['data'][20]);
    // Remove system_maintenance, keep it for reinsertion later.
    $system_maintenance = $expected['relationships']['consistsOf']['data'][26];
    $this->assertSame('system_maintenance (0 of 1)', $system_maintenance['id']);
    unset($expected['relationships']['consistsOf']['data'][26]);
    // Remove system_site, keep it for reinsertion later.
    $system_site = $expected['relationships']['consistsOf']['data'][28];
    $this->assertSame('system_site (0 of 1)', $system_site['id']);
    unset($expected['relationships']['consistsOf']['data'][28]);

    // Reindex the array.
    $expected['relationships']['consistsOf']['data'] = array_values($expected['relationships']['consistsOf']['data']);

    // Reinsert the 5 new migration plugins, move the 4 that we removed earlier.
    $this->insertAt($expected['relationships']['consistsOf']['data'], 20, [
      'type' => 'migrationPlugin',
      'id' => 'locale_settings (0 of 1)',
    ]);
    $expected['relationships']['consistsOf']['data'][] = $system_maintenance;
    $expected['relationships']['consistsOf']['data'][] = [
      'type' => 'migrationPlugin',
      'id' => 'd7_system_maintenance_translation (0 of 1)',
    ];
    $expected['relationships']['consistsOf']['data'][] = $system_site;
    $expected['relationships']['consistsOf']['data'][] = [
      'type' => 'migrationPlugin',
      'id' => 'd7_system_site_translation (0 of 2)',
    ];
    $expected['relationships']['consistsOf']['data'][] = $d7_user_mail;
    $expected['relationships']['consistsOf']['data'][] = [
      'type' => 'migrationPlugin',
      'id' => 'd7_user_mail_translation (0 of 1)',
    ];
    $expected['relationships']['consistsOf']['data'][] = $d7_user_settings;
    $expected['relationships']['consistsOf']['data'][] = [
      'type' => 'migrationPlugin',
      'id' => 'd7_user_settings_translation (0 of 1)',
    ];

    unset($expected['links']['import']);

    return $expected;
  }

  /**
   * {@inheritdoc}
   *
   * Depends on "Language settings", 3 extra migration plugins, 3 extra rows.
   */
  protected function expectedResourceObjectForBlockPlacements() {
    $expected = parent::expectedResourceObjectForBlockPlacements();
    $expected['attributes']['totalCount'] += 3;
    $this->dependsOnLanguage($expected, 2);
    $expected['relationships']['consistsOf']['data'][] = [
      'type' => 'migrationPlugin',
      'id' => 'd7_block_translation:bartik:simple (0 of 1)',
    ];
    $expected['relationships']['consistsOf']['data'][] = [
      'type' => 'migrationPlugin',
      'id' => 'd7_block_translation:seven:simple (0 of 1)',
    ];
    $expected['relationships']['consistsOf']['data'][] = [
      'type' => 'migrationPlugin',
      'id' => 'd7_block_translation:stark:simple (0 of 1)',
    ];
    return $expected;
  }

  /**
   * {@inheritdoc}
   *
   * Depends on "Language settings", 2 extra migration plugins, 4 extra rows.
   */
  protected function expectedResourceObjectForOtherMenuLinks() {
    $expected = parent::expectedResourceObjectForOtherMenuLinks();
    $expected['attributes']['totalCount'] += 4;
    $this->dependsOnLanguage($expected, 1);
    $expected['relationships']['dependencies']['data'][0]['meta']['dependencyReasons'][] = 'd7_language_content_menu_settings';
    $expected['relationships']['consistsOf']['data'][] = [
      'type' => 'migrationPlugin',
      'id' => 'd7_menu_links_localized:other (0 of 3)',
    ];
    $expected['relationships']['consistsOf']['data'][] = [
      'type' => 'migrationPlugin',
      'id' => 'd7_menu_links_translation:other (0 of 1)',
    ];
    $expected['links']['preview-unmet-requirement:0']['title'] = 'Not all supporting configuration has been processed yet: Shared structure for menus (specifically: d7_menu, d7_language_content_menu_settings), Language settings (specifically: language).';
    return $expected;
  }

  /**
   * {@inheritdoc}
   *
   * Depends on "Language settings", 1 extra migration plugin, no extra rows.
   */
  protected function expectedResourceObjectForTestLongName() {
    $expected = parent::expectedResourceObjectForTestLongName();
    $this->dependsOnLanguage($expected);
    $this->insertAt($expected['relationships']['consistsOf']['data'], 1, [
      'type' => 'migrationPlugin',
      'id' => 'd7_language_content_settings:a_thirty_two_character_type_name',
    ]);
    return $expected;
  }

  /**
   * {@inheritdoc}
   *
   * Depends on "Language settings", 2 extra migration plugins, no extra rows.
   */
  protected function expectedResourceObjectForTestLongNameComments() {
    $expected = parent::expectedResourceObjectForTestLongNameComments();
    $this->dependsOnLanguage($expected, 1);
    $this->insertAt($expected['relationships']['consistsOf']['data'], 6, [
      'type' => 'migrationPlugin',
      'id' => 'd7_language_content_comment_settings:a_thirty_two_character_type_name',
    ]);
    $this->insertAt($expected['relationships']['consistsOf']['data'], 11, [
      'type' => 'migrationPlugin',
      'id' => 'd7_comment_entity_translation:a_thirty_two_character_type_name (0 of 0)',
    ]);
    return $expected;
  }

}
