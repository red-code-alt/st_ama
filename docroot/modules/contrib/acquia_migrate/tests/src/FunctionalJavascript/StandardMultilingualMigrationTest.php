<?php

namespace Drupal\Tests\acquia_migrate\FunctionalJavascript;

/**
 * Tests multilingual migration from the core Drupal 7 database fixture.
 *
 * @group acquia_migrate
 * @group acquia_migrate__core
 */
class StandardMultilingualMigrationTest extends StandardMigrationTest {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'config_translation',
    'content_translation',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->expectedInitialImports += [
      'd7_entity_translation_settings:comment:comment_node_et' => [1, 1, 1, 0, 0],
      'd7_entity_translation_settings:comment:comment_node_test_content_type' => [1, 1, 1, 0, 0],
      'd7_entity_translation_settings:node:et' => [1, 1, 1, 0, 0],
      'd7_entity_translation_settings:node:test_content_type' => [1, 1, 1, 0, 0],
      'd7_entity_translation_settings:taxonomy_term:test_vocabulary' => [1, 1, 1, 0, 0],
      'd7_entity_translation_settings:user:user' => [1, 1, 1, 0, 0],
      'd7_field_instance_label_description_translation:comment:page' => [1, 1, 1, 0, 0],
      'd7_field_instance_label_description_translation:node:article' => [4, 4, 4, 0, 0],
      'd7_field_instance_label_description_translation:node:blog' => [4, 4, 4, 0, 0],
      'd7_field_instance_label_description_translation:node:test_content_type' => [4, 4, 4, 0, 0],
      'd7_field_instance_label_description_translation:taxonomy_term:test_vocabulary' => [1, 1, 1, 0, 0],
      'd7_field_instance_option_translation:node:article' => [4, 4, 4, 0, 0],
      'd7_field_instance_option_translation:node:blog' => [14, 14, 2, 0, 0],
      'd7_field_instance_option_translation:node:test_content_type' => [2, 2, 2, 0, 0],
      'd7_field_option_translation:node:article' => [4, 4, 4, 0, 0],
      'd7_field_option_translation:node:blog' => [14, 14, 14, 0, 0],
      'd7_field_option_translation:node:test_content_type' => [2, 2, 2, 0, 0],
      'd7_language_content_comment_settings:article' => [1, 1, 1, 0, 0],
      'd7_language_content_comment_settings:a_thirty_two_character_type_name' => [1, 1, 1, 0, 0],
      'd7_language_content_comment_settings:blog' => [1, 1, 1, 0, 0],
      'd7_language_content_comment_settings:book' => [1, 1, 0, 0, 0],
      'd7_language_content_comment_settings:et' => [1, 1, 1, 0, 0],
      'd7_language_content_comment_settings:forum' => [1, 1, 1, 0, 0],
      'd7_language_content_comment_settings:page' => [1, 1, 1, 0, 0],
      'd7_language_content_comment_settings:test_content_type' => [1, 1, 1, 0, 0],
      'd7_language_content_menu_settings' => [1, 1, 1, 0, 0],
      'd7_language_content_settings:article' => [1, 1, 1, 0, 0],
      'd7_language_content_settings:a_thirty_two_character_type_name' => [1, 1, 1, 0, 0],
      'd7_language_content_settings:blog' => [1, 1, 1, 0, 0],
      'd7_language_content_settings:book' => [1, 1, 0, 0, 0],
      'd7_language_content_settings:et' => [1, 1, 1, 0, 0],
      'd7_language_content_settings:forum' => [1, 1, 0, 0, 0],
      'd7_language_content_settings:page' => [1, 1, 1, 0, 0],
      'd7_language_content_settings:test_content_type' => [1, 1, 1, 0, 0],
      'd7_language_content_taxonomy_vocabulary_settings:sujet_de_discussion' => [1, 1, 1, 0, 0],
      'd7_language_content_taxonomy_vocabulary_settings:tags' => [1, 1, 1, 0, 0],
      'd7_language_content_taxonomy_vocabulary_settings:test_vocabulary' => [1, 1, 1, 0, 0],
      'd7_language_content_taxonomy_vocabulary_settings:vocabfixed' => [1, 1, 1, 0, 0],
      'd7_language_content_taxonomy_vocabulary_settings:vocablocalized' => [1, 1, 1, 0, 0],
      'd7_language_content_taxonomy_vocabulary_settings:vocablocalized2' => [1, 1, 1, 0, 0],
      'd7_language_content_taxonomy_vocabulary_settings:vocabtranslate' => [1, 1, 1, 0, 0],
      'd7_language_content_taxonomy_vocabulary_settings:vocabulary_name_much_longer_than_thirty_two_characters' => [1, 1, 1, 0, 0],
      'd7_language_negotiation_settings' => [1, 1, 1, 0, 0],
      'd7_language_types' => [1, 1, 1, 0, 0],
      'd7_menu_translation' => [5, 5, 5, 0, 0],
      'd7_taxonomy_vocabulary_translation:vocabfixed' => [1, 1, 1, 0, 0],
      'd7_taxonomy_vocabulary_translation:vocablocalized' => [4, 4, 4, 0, 0],
      'd7_taxonomy_vocabulary_translation:vocabtranslate' => [2, 2, 2, 0, 0],
      'default_language' => [1, 1, 1, 0, 0],
      'language' => [3, 3, 3, 0, 0],
      'language_prefixes_and_domains' => [3, 3, 3, 0, 0],
    ];
  }

}
