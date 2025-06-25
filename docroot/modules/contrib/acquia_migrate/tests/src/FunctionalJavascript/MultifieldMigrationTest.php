<?php

namespace Drupal\Tests\acquia_migrate\FunctionalJavascript;

use Drupal\Core\Url;

/**
 * Tests multifield to paragraphs migrations.
 *
 * @requires function Drupal\Tests\acquia_migrate\FunctionalJavascript\MultifieldMigrationTest::setupMigrationConnection
 * @group acquia_migrate
 * @group acquia_migrate__contrib
 */
class MultifieldMigrationTest extends MultifieldMigrationTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'standard';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'acquia_migrate',
    'config_translation',
    'content_translation',
    'paragraphs',
    'paragraphs_migration',
    'telephone',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getFixtureFilePath() {
    return drupal_get_path('module', 'migrate_drupal') . '/tests/fixtures/drupal7.php';
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->setupMigrationConnection();
    $this->loadFixture(drupal_get_path('module', 'paragraphs_migration') . '/tests/fixtures/drupal7_multifield_on_core_fixture.php');

    // Write public and private file location into settings.php.
    $public_base_path = realpath(DRUPAL_ROOT . '/' . drupal_get_path('module', 'migrate_drupal_ui') . '/tests/src/Functional/d7/files');
    $private_base_path = $public_base_path;
    $this->writeSettings([
      'settings' => [
        'migrate_source_base_path' => (object) [
          'value' => $private_base_path,
          'required' => TRUE,
        ],
        'migrate_source_private_file_path' => (object) [
          'value' => $private_base_path,
          'required' => TRUE,
        ],
      ],
    ]);

    // Log in as user 1. Migrations in the UI can only be performed as user 1.
    $this->drupalLogin($this->rootUser);

    $this->submitMigrationContentSelectionScreen();
    $this->visitMigrationDashboard();
  }

  /**
   * Tests multifield to paragraphs migrations.
   */
  public function testMultifieldMigrations() {
    $this->assertMultifieldInitialImports();

    // Run only those migrations that are required for paragraphs and field
    // collection migrations.
    $this->runMigrations([
      'User accounts',
      'Tags taxonomy terms',
      'Vocabulary with multifields taxonomy terms',
    ]);
    $this->runMigrations([
      'Type with multifields',
    ]);

    // Have to reset all the static caches after migration to ensure entities
    // are loadable.
    $this->resetAll();

    // Leave the dashboard.
    $this->drupalGet(Url::fromRoute('system.admin_content'));
    $this->assertNoWarningOrErrorMessages();

    $this->assertEquals(8, $this->getActualParagraphRevisionTranslationsCount());
    $this->assertEquals(7, $this->getActualParagraphRevisionsCount());
    $this->assertEquals(4, $this->getActualParagraphsCount());

    $this->assertMultifieldTextType();
    $this->assertMultifieldComplexType();

    $this->assertTermMultifieldFieldStorage();
    $this->assertTermMultifieldFieldInstance();
    $this->assertTerm126();

    $this->assertNodeMultifieldTextFieldStorage();
    $this->assertNodeMultifieldComplexFieldStorage();
    $this->assertNodeMultifieldTextFieldInstance();
    $this->assertNodeMultifieldComplexFieldInstance();
    $this->assertNode112();
  }

  /**
   * Asserts initial imports.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function assertMultifieldInitialImports() {
    $this->assertStrictInitialImport([
      'block_content_body_field' => [1, 1, 1, 0, 0],
      'block_content_entity_display' => [1, 1, 1, 0, 0],
      'block_content_entity_form_display' => [1, 1, 1, 0, 0],
      'block_content_type' => [1, 1, 1, 0, 0],
      'd7_comment_entity_display:a_thirty_two_character_type_name' => [1, 1, 1, 0, 0],
      'd7_comment_entity_display:article' => [1, 1, 1, 0, 0],
      'd7_comment_entity_display:blog' => [1, 1, 1, 0, 0],
      'd7_comment_entity_display:book' => [1, 1, 1, 0, 0],
      'd7_comment_entity_display:et' => [1, 1, 1, 0, 0],
      'd7_comment_entity_display:forum' => [1, 1, 1, 0, 0],
      'd7_comment_entity_display:page' => [1, 1, 1, 0, 0],
      'd7_comment_entity_display:test_content_type' => [1, 1, 1, 0, 0],
      'd7_comment_entity_display:type_with_multifields' => [1, 1, 1, 0, 0],
      'd7_comment_entity_form_display:a_thirty_two_character_type_name' => [1, 1, 1, 0, 0],
      'd7_comment_entity_form_display:article' => [1, 1, 1, 0, 0],
      'd7_comment_entity_form_display:blog' => [1, 1, 1, 0, 0],
      'd7_comment_entity_form_display:book' => [1, 1, 1, 0, 0],
      'd7_comment_entity_form_display:et' => [1, 1, 1, 0, 0],
      'd7_comment_entity_form_display:forum' => [1, 1, 1, 0, 0],
      'd7_comment_entity_form_display:page' => [1, 1, 1, 0, 0],
      'd7_comment_entity_form_display:test_content_type' => [1, 1, 1, 0, 0],
      'd7_comment_entity_form_display:type_with_multifields' => [1, 1, 1, 0, 0],
      'd7_comment_entity_form_display_subject:a_thirty_two_character_type_name' => [1, 1, 1, 0, 0],
      'd7_comment_entity_form_display_subject:article' => [1, 1, 1, 0, 0],
      'd7_comment_entity_form_display_subject:blog' => [1, 1, 1, 0, 0],
      'd7_comment_entity_form_display_subject:book' => [1, 1, 1, 0, 0],
      'd7_comment_entity_form_display_subject:et' => [1, 1, 1, 0, 0],
      'd7_comment_entity_form_display_subject:forum' => [1, 1, 1, 0, 0],
      'd7_comment_entity_form_display_subject:page' => [1, 1, 1, 0, 0],
      'd7_comment_entity_form_display_subject:test_content_type' => [1, 1, 1, 0, 0],
      'd7_comment_entity_form_display_subject:type_with_multifields' => [1, 1, 1, 0, 0],
      'd7_comment_field:a_thirty_two_character_type_name' => [1, 1, 1, 0, 0],
      'd7_comment_field:article' => [1, 1, 1, 0, 0],
      'd7_comment_field:blog' => [1, 1, 1, 0, 0],
      'd7_comment_field:book' => [1, 1, 1, 0, 0],
      'd7_comment_field:et' => [1, 1, 1, 0, 0],
      'd7_comment_field:forum' => [1, 1, 1, 0, 0],
      'd7_comment_field:page' => [1, 1, 1, 0, 0],
      'd7_comment_field:test_content_type' => [1, 1, 1, 0, 0],
      'd7_comment_field:type_with_multifields' => [1, 1, 1, 0, 0],
      'd7_comment_field_instance:a_thirty_two_character_type_name' => [1, 1, 1, 0, 0],
      'd7_comment_field_instance:article' => [1, 1, 1, 0, 0],
      'd7_comment_field_instance:blog' => [1, 1, 1, 0, 0],
      'd7_comment_field_instance:book' => [1, 1, 1, 0, 0],
      'd7_comment_field_instance:et' => [1, 1, 1, 0, 0],
      'd7_comment_field_instance:forum' => [1, 1, 1, 0, 0],
      'd7_comment_field_instance:page' => [1, 1, 1, 0, 0],
      'd7_comment_field_instance:test_content_type' => [1, 1, 1, 0, 0],
      'd7_comment_field_instance:type_with_multifields' => [1, 1, 1, 0, 0],
      'd7_comment_type:a_thirty_two_character_type_name' => [1, 1, 1, 0, 0],
      'd7_comment_type:article' => [1, 1, 1, 0, 0],
      'd7_comment_type:blog' => [1, 1, 1, 0, 0],
      'd7_comment_type:book' => [1, 1, 1, 0, 0],
      'd7_comment_type:et' => [1, 1, 1, 0, 0],
      'd7_comment_type:forum' => [1, 1, 1, 0, 0],
      'd7_comment_type:page' => [1, 1, 1, 0, 0],
      'd7_comment_type:test_content_type' => [1, 1, 1, 0, 0],
      'd7_comment_type:type_with_multifields' => [1, 1, 1, 0, 0],
      'd7_entity_translation_settings:comment:comment_node_et' => [1, 1, 1, 0, 0],
      'd7_entity_translation_settings:comment:comment_node_test_content_type' => [1, 1, 1, 0, 0],
      'd7_entity_translation_settings:node:et' => [1, 1, 1, 0, 0],
      'd7_entity_translation_settings:node:test_content_type' => [1, 1, 1, 0, 0],
      'd7_entity_translation_settings:taxonomy_term:test_vocabulary' => [1, 1, 1, 0, 0],
      'd7_entity_translation_settings:user:user' => [1, 1, 1, 0, 0],
      'd7_field:comment' => [2, 2, 2, 0, 0],
      'd7_field:multifield' => [5, 5, 5, 0, 0],
      'd7_field:node' => [54, 54, 53, 0, 1],
      'd7_field:taxonomy_term' => [6, 6, 6, 0, 0],
      'd7_field:user' => [3, 3, 3, 0, 0],
      'd7_field_formatter_settings:comment:a_thirty_two_character_type_name' => [1, 1, 1, 0, 0],
      'd7_field_formatter_settings:comment:article' => [1, 1, 1, 0, 0],
      'd7_field_formatter_settings:comment:blog' => [1, 1, 1, 0, 0],
      'd7_field_formatter_settings:comment:book' => [1, 1, 1, 0, 0],
      'd7_field_formatter_settings:comment:et' => [1, 1, 1, 0, 0],
      'd7_field_formatter_settings:comment:forum' => [1, 1, 1, 0, 0],
      'd7_field_formatter_settings:comment:page' => [1, 1, 1, 0, 0],
      'd7_field_formatter_settings:comment:test_content_type' => [2, 2, 2, 0, 0],
      'd7_field_formatter_settings:comment:type_with_multifields' => [1, 1, 1, 0, 0],
      'd7_field_formatter_settings:multifield:field_multifield_complex_fields' => [3, 3, 3, 0, 0],
      'd7_field_formatter_settings:multifield:field_multifield_w_text_fields' => [2, 2, 2, 0, 0],
      'd7_field_formatter_settings:node:a_thirty_two_character_type_name' => [2, 2, 2, 0, 0],
      'd7_field_formatter_settings:node:article' => [25, 25, 25, 0, 0],
      'd7_field_formatter_settings:node:blog' => [10, 10, 10, 0, 0],
      'd7_field_formatter_settings:node:book' => [2, 2, 2, 0, 0],
      'd7_field_formatter_settings:node:et' => [1, 1, 1, 0, 0],
      'd7_field_formatter_settings:node:forum' => [5, 5, 4, 0, 0],
      'd7_field_formatter_settings:node:page' => [11, 11, 11, 0, 0],
      'd7_field_formatter_settings:node:test_content_type' => [24, 24, 24, 0, 0],
      'd7_field_formatter_settings:node:type_with_multifields' => [4, 4, 4, 0, 0],
      'd7_field_formatter_settings:taxonomy_term:test_vocabulary' => [2, 2, 2, 0, 0],
      'd7_field_formatter_settings:taxonomy_term:vocabfixed' => [1, 1, 1, 0, 0],
      'd7_field_formatter_settings:taxonomy_term:vocablocalized' => [1, 1, 1, 0, 0],
      'd7_field_formatter_settings:taxonomy_term:vocabtranslate' => [1, 1, 1, 0, 0],
      'd7_field_formatter_settings:taxonomy_term:vocabulary_with_multifields' => [1, 1, 1, 0, 0],
      'd7_field_formatter_settings:user:user' => [3, 3, 3, 0, 0],
      'd7_field_instance:comment:a_thirty_two_character_type_name' => [1, 1, 1, 0, 0],
      'd7_field_instance:comment:article' => [1, 1, 1, 0, 0],
      'd7_field_instance:comment:blog' => [1, 1, 1, 0, 0],
      'd7_field_instance:comment:book' => [1, 1, 1, 0, 0],
      'd7_field_instance:comment:et' => [1, 1, 1, 0, 0],
      'd7_field_instance:comment:forum' => [1, 1, 1, 0, 0],
      'd7_field_instance:comment:page' => [1, 1, 1, 0, 0],
      'd7_field_instance:comment:test_content_type' => [2, 2, 2, 0, 0],
      'd7_field_instance:comment:type_with_multifields' => [1, 1, 1, 0, 0],
      'd7_field_instance:multifield:field_multifield_complex_fields' => [3, 3, 3, 0, 0],
      'd7_field_instance:multifield:field_multifield_w_text_fields' => [2, 2, 2, 0, 0],
      'd7_field_instance:node:a_thirty_two_character_type_name' => [1, 1, 1, 0, 0],
      'd7_field_instance:node:article' => [21, 21, 21, 0, 0],
      'd7_field_instance:node:blog' => [9, 9, 9, 0, 0],
      'd7_field_instance:node:book' => [1, 1, 1, 0, 0],
      'd7_field_instance:node:et' => [1, 1, 1, 0, 0],
      'd7_field_instance:node:forum' => [3, 3, 2, 0, 1],
      'd7_field_instance:node:page' => [10, 10, 10, 0, 0],
      'd7_field_instance:node:test_content_type' => [23, 23, 23, 0, 0],
      'd7_field_instance:node:type_with_multifields' => [3, 3, 3, 0, 0],
      'd7_field_instance:taxonomy_term:test_vocabulary' => [2, 2, 2, 0, 0],
      'd7_field_instance:taxonomy_term:vocabfixed' => [1, 1, 1, 0, 0],
      'd7_field_instance:taxonomy_term:vocablocalized' => [1, 1, 1, 0, 0],
      'd7_field_instance:taxonomy_term:vocabtranslate' => [1, 1, 1, 0, 0],
      'd7_field_instance:taxonomy_term:vocabulary_with_multifields' => [1, 1, 1, 0, 0],
      'd7_field_instance:user:user' => [3, 3, 3, 0, 0],
      'd7_field_instance_label_description_translation:comment:page' => [1, 1, 1, 0, 0],
      'd7_field_instance_label_description_translation:node:article' => [4, 4, 4, 0, 0],
      'd7_field_instance_label_description_translation:node:blog' => [4, 4, 4, 0, 0],
      'd7_field_instance_label_description_translation:node:test_content_type' => [4, 4, 4, 0, 0],
      'd7_field_instance_label_description_translation:taxonomy_term:test_vocabulary' => [1, 1, 1, 0, 0],
      'd7_field_instance_option_translation:node:article' => [4, 4, 4, 0, 0],
      'd7_field_instance_option_translation:node:blog' => [14, 14, 2, 0, 0],
      'd7_field_instance_option_translation:node:test_content_type' => [2, 2, 2, 0, 0],
      'd7_field_instance_widget_settings:comment:a_thirty_two_character_type_name' => [1, 1, 1, 0, 0],
      'd7_field_instance_widget_settings:comment:article' => [1, 1, 1, 0, 0],
      'd7_field_instance_widget_settings:comment:blog' => [1, 1, 1, 0, 0],
      'd7_field_instance_widget_settings:comment:book' => [1, 1, 1, 0, 0],
      'd7_field_instance_widget_settings:comment:et' => [1, 1, 1, 0, 0],
      'd7_field_instance_widget_settings:comment:forum' => [1, 1, 1, 0, 0],
      'd7_field_instance_widget_settings:comment:page' => [1, 1, 1, 0, 0],
      'd7_field_instance_widget_settings:comment:test_content_type' => [2, 2, 2, 0, 0],
      'd7_field_instance_widget_settings:comment:type_with_multifields' => [1, 1, 1, 0, 0],
      'd7_field_instance_widget_settings:multifield:field_multifield_complex_fields' => [3, 3, 3, 0, 0],
      'd7_field_instance_widget_settings:multifield:field_multifield_w_text_fields' => [2, 2, 2, 0, 0],
      'd7_field_instance_widget_settings:node:a_thirty_two_character_type_name' => [1, 1, 1, 0, 0],
      'd7_field_instance_widget_settings:node:article' => [21, 21, 21, 0, 0],
      'd7_field_instance_widget_settings:node:blog' => [9, 9, 9, 0, 0],
      'd7_field_instance_widget_settings:node:book' => [1, 1, 1, 0, 0],
      'd7_field_instance_widget_settings:node:et' => [1, 1, 1, 0, 0],
      'd7_field_instance_widget_settings:node:forum' => [3, 3, 2, 0, 0],
      'd7_field_instance_widget_settings:node:page' => [10, 10, 10, 0, 0],
      'd7_field_instance_widget_settings:node:test_content_type' => [23, 23, 23, 0, 0],
      'd7_field_instance_widget_settings:node:type_with_multifields' => [3, 3, 3, 0, 0],
      'd7_field_instance_widget_settings:taxonomy_term:test_vocabulary' => [2, 2, 2, 0, 0],
      'd7_field_instance_widget_settings:taxonomy_term:vocabfixed' => [1, 1, 1, 0, 0],
      'd7_field_instance_widget_settings:taxonomy_term:vocablocalized' => [1, 1, 1, 0, 0],
      'd7_field_instance_widget_settings:taxonomy_term:vocabtranslate' => [1, 1, 1, 0, 0],
      'd7_field_instance_widget_settings:taxonomy_term:vocabulary_with_multifields' => [1, 1, 1, 0, 0],
      'd7_field_instance_widget_settings:user:user' => [3, 3, 3, 0, 0],
      'd7_field_option_translation:node:article' => [4, 4, 4, 0, 0],
      'd7_field_option_translation:node:blog' => [14, 14, 14, 0, 0],
      'd7_field_option_translation:node:test_content_type' => [2, 2, 2, 0, 0],
      'd7_filter_format' => [5, 5, 5, 0, 0],
      'd7_language_content_comment_settings:a_thirty_two_character_type_name' => [1, 1, 1, 0, 0],
      'd7_language_content_comment_settings:article' => [1, 1, 1, 0, 0],
      'd7_language_content_comment_settings:blog' => [1, 1, 1, 0, 0],
      'd7_language_content_comment_settings:book' => [1, 1, 0, 0, 0],
      'd7_language_content_comment_settings:et' => [1, 1, 1, 0, 0],
      'd7_language_content_comment_settings:forum' => [1, 1, 1, 0, 0],
      'd7_language_content_comment_settings:page' => [1, 1, 1, 0, 0],
      'd7_language_content_comment_settings:test_content_type' => [1, 1, 1, 0, 0],
      'd7_language_content_comment_settings:type_with_multifields' => [1, 1, 1, 0, 0],
      'd7_language_content_menu_settings' => [1, 1, 1, 0, 0],
      'd7_language_content_settings:a_thirty_two_character_type_name' => [1, 1, 1, 0, 0],
      'd7_language_content_settings:article' => [1, 1, 1, 0, 0],
      'd7_language_content_settings:blog' => [1, 1, 1, 0, 0],
      'd7_language_content_settings:book' => [1, 1, 0, 0, 0],
      'd7_language_content_settings:et' => [1, 1, 1, 0, 0],
      'd7_language_content_settings:forum' => [1, 1, 0, 0, 0],
      'd7_language_content_settings:page' => [1, 1, 1, 0, 0],
      'd7_language_content_settings:test_content_type' => [1, 1, 1, 0, 0],
      'd7_language_content_settings:type_with_multifields' => [1, 1, 0, 0, 0],
      'd7_language_content_taxonomy_vocabulary_settings:sujet_de_discussion' => [1, 1, 1, 0, 0],
      'd7_language_content_taxonomy_vocabulary_settings:tags' => [1, 1, 1, 0, 0],
      'd7_language_content_taxonomy_vocabulary_settings:test_vocabulary' => [1, 1, 1, 0, 0],
      'd7_language_content_taxonomy_vocabulary_settings:vocabfixed' => [1, 1, 1, 0, 0],
      'd7_language_content_taxonomy_vocabulary_settings:vocablocalized' => [1, 1, 1, 0, 0],
      'd7_language_content_taxonomy_vocabulary_settings:vocablocalized2' => [1, 1, 1, 0, 0],
      'd7_language_content_taxonomy_vocabulary_settings:vocabtranslate' => [1, 1, 1, 0, 0],
      'd7_language_content_taxonomy_vocabulary_settings:vocabulary_name_much_longer_than_thirty_two_characters' => [1, 1, 1, 0, 0],
      'd7_language_content_taxonomy_vocabulary_settings:vocabulary_with_multifields' => [1, 1, 1, 0, 0],
      'd7_language_negotiation_settings' => [1, 1, 1, 0, 0],
      'd7_language_types' => [1, 1, 1, 0, 0],
      'd7_menu' => [6, 6, 6, 0, 0],
      'd7_menu_translation' => [5, 5, 5, 0, 0],
      'd7_node_title_label:forum' => [1, 1, 1, 0, 0],
      'd7_node_type:a_thirty_two_character_type_name' => [1, 1, 1, 0, 0],
      'd7_node_type:article' => [1, 1, 1, 0, 0],
      'd7_node_type:blog' => [1, 1, 1, 0, 0],
      'd7_node_type:book' => [1, 1, 1, 0, 0],
      'd7_node_type:et' => [1, 1, 1, 0, 0],
      'd7_node_type:forum' => [1, 1, 1, 0, 0],
      'd7_node_type:page' => [1, 1, 1, 0, 0],
      'd7_node_type:test_content_type' => [1, 1, 1, 0, 0],
      'd7_node_type:type_with_multifields' => [1, 1, 1, 0, 0],
      'd7_shortcut_set' => [2, 2, 2, 0, 0],
      'd7_taxonomy_vocabulary:sujet_de_discussion' => [1, 1, 1, 0, 0],
      'd7_taxonomy_vocabulary:tags' => [1, 1, 1, 0, 0],
      'd7_taxonomy_vocabulary:test_vocabulary' => [1, 1, 1, 0, 0],
      'd7_taxonomy_vocabulary:vocabfixed' => [1, 1, 1, 0, 0],
      'd7_taxonomy_vocabulary:vocablocalized' => [1, 1, 1, 0, 0],
      'd7_taxonomy_vocabulary:vocablocalized2' => [1, 1, 1, 0, 0],
      'd7_taxonomy_vocabulary:vocabtranslate' => [1, 1, 1, 0, 0],
      'd7_taxonomy_vocabulary:vocabulary_name_much_longer_than_thirty_two_characters' => [1, 1, 1, 0, 0],
      'd7_taxonomy_vocabulary:vocabulary_with_multifields' => [1, 1, 1, 0, 0],
      'd7_taxonomy_vocabulary_translation:vocabfixed' => [1, 1, 1, 0, 0],
      'd7_taxonomy_vocabulary_translation:vocablocalized' => [4, 4, 4, 0, 0],
      'd7_taxonomy_vocabulary_translation:vocabtranslate' => [2, 2, 2, 0, 0],
      'd7_user_role' => [3, 3, 3, 0, 0],
      'd7_view_modes:comment' => [1, 1, 1, 0, 0],
      'd7_view_modes:multifield' => [1, 1, 1, 0, 0],
      'd7_view_modes:node' => [3, 3, 3, 0, 0],
      'd7_view_modes:taxonomy_term' => [1, 1, 1, 0, 0],
      'd7_view_modes:user' => [1, 1, 1, 0, 0],
      'default_language' => [1, 1, 1, 0, 0],
      'language' => [3, 3, 3, 0, 0],
      'language_prefixes_and_domains' => [3, 3, 3, 0, 0],
      'pm_multifield_translation_settings:field_multifield_complex_fields' => [1, 1, 1, 0, 0],
      'pm_multifield_translation_settings:field_multifield_w_text_fields' => [1, 1, 1, 0, 0],
      'pm_multifield_type:field_multifield_complex_fields' => [1, 1, 1, 0, 0],
      'pm_multifield_type:field_multifield_w_text_fields' => [1, 1, 1, 0, 0],
      'user_picture_entity_display' => [1, 1, 1, 0, 0],
      'user_picture_entity_form_display' => [1, 1, 1, 0, 0],
      'user_picture_field' => [1, 1, 1, 0, 0],
      'user_picture_field_instance' => [1, 1, 1, 0, 0],
    ]);
  }

}
