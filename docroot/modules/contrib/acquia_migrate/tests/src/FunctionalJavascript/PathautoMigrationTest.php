<?php

namespace Drupal\Tests\acquia_migrate\FunctionalJavascript;

use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\path_alias\AliasRepositoryInterface;
use Drupal\pathauto\Plugin\migrate\process\PathautoPatternSelectionCriteria;
use Drupal\pathauto_test_uuid_generator\UuidTestGenerator;
use Drupal\taxonomy\Entity\Term;

/**
 * Tests pathauto migration.
 *
 * For the explanation why we use WebDriver test for testing the location
 * migration integration, check the class comment of MigrateJsUiTrait.
 *
 * @requires function \Drupal\Tests\acquia_migrate\FunctionalJavascript\PathautoMigrationTest::setupMigrationConnection
 * @group acquia_migrate
 * @group acquia_migrate__contrib
 */
class PathautoMigrationTest extends PathautoMigrationTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'standard';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'acquia_migrate',
    'content_translation',
    'config_translation',
    'pathauto',
    'pathauto_test_uuid_generator',
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
    $this->loadFixture(drupal_get_path('module', 'pathauto') . '/tests/fixtures/drupal7.php');

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

    // UUIDs used in pathauto pattern's selection criteria must be predictable.
    \Drupal::state()->set(
      UuidTestGenerator::WATCHED_CLASSES_STATE_KEY,
      PathautoPatternSelectionCriteria::class
    );

    // Log in as user 1. Migrations in the UI can only be performed as user 1.
    $this->drupalLogin($this->rootUser);

    $this->submitMigrationContentSelectionScreen();
    $this->visitMigrationDashboard();
  }

  /**
   * Tests the migration of pathauto settings, patterns and states.
   */
  public function testPathautoMigrations() {
    $this->assertPathautoInitialImports();

    // Run required migrations.
    $this->runMigrations([
      // "User accounts" contains the user pattern.
      'User accounts',
      // "Sujet de discussion" and "Tags" also have pathauto pattern.
      'Sujet de discussion taxonomy terms',
      'Tags taxonomy terms',
      // "Test Vocabulary" terms do not have bundle-specific pattern, but have
      // disabled path alias state (so they shoudn't get generated alias after a
      // resave).
      'Test Vocabulary taxonomy terms',
      // "VocabLocalized", "VocabTranslate", "VocabFixed" and "Public files" are
      // the dependencies of the "Article" cluster.
      'VocabFixed taxonomy terms',
      'VocabLocalized taxonomy terms',
      'VocabTranslate taxonomy terms',
      'Public files',
      // Pathauto settings should be placed into "Site configuration".
      'Site configuration',
    ]);

    $this->runMigrations([
      'Article',
      'Blog entry',
      'Entity translation test',
    ]);

    // Have to reset all the static caches after migration to ensure entities
    // are loadable.
    $this->resetAll();

    // Leave the dashboard.
    $this->drupalGet(Url::fromRoute('system.admin_content'));
    $this->assertNoWarningOrErrorMessages();

    $this->assertPathautoSettings();

    $this->assertUserPattern();
    $this->assertTermPattern();
    $this->assertNodePattern();
    $this->assertTermTagsPattern(1);
    $this->assertNodeArticleEnPattern(2);
    $this->assertNodeArticleFrPattern(4);
    $this->assertNodeArticleIsPattern(6);
    $this->assertNodeArticlePattern(8);
    $this->assertTermForumsPattern(9);
    $this->assertNodeBlogPattern(10);
    $this->assertNodeEtPattern(11);

    $path_alias_repository = $this->container->get('path_alias.repository');
    assert($path_alias_repository instanceof AliasRepositoryInterface);

    // Check that the migrated URL aliases are present.
    $this->assertEquals('/term33', $path_alias_repository->lookupBySystemPath('/taxonomy/term/4', 'en')['alias']);
    $this->assertEquals('/deep-space-9', $path_alias_repository->lookupBySystemPath('/node/2', 'en')['alias']);
    $this->assertEquals('/firefly', $path_alias_repository->lookupBySystemPath('/node/4', 'en')['alias']);

    // Node 11 and taxonomy term 11 will have a generated path alias (after a
    // resave), since they have pathalias = 1 on the source site.
    $this->assertEquals(NULL, $path_alias_repository->lookupBySystemPath('/node/11', 'en'));
    $this->assertEquals(NULL, $path_alias_repository->lookupBySystemPath('/taxonomy/term/11', 'en'));
    Node::load(11)->save();
    Term::load(11)->save();
    $this->assertEquals('/entity-translation-test/11/page-one', $path_alias_repository->lookupBySystemPath('/node/11', 'en')['alias']);
    $this->assertEquals('/tag/dax', $path_alias_repository->lookupBySystemPath('/taxonomy/term/11', 'en')['alias']);

    // Taxonomy terms 2 and 3 do not have path alias, and their path alias state
    // is "0": They shouldn't get (new) path alias, neither after a resave.
    $this->assertEquals(NULL, $path_alias_repository->lookupBySystemPath('/taxonomy/term/2', 'en'));
    $this->assertEquals(NULL, $path_alias_repository->lookupBySystemPath('/taxonomy/term/3', 'en'));
    Term::load(2)->save();
    Term::load(3)->save();
    $this->assertEquals(NULL, $path_alias_repository->lookupBySystemPath('/taxonomy/term/2', 'en'));
    $this->assertEquals(NULL, $path_alias_repository->lookupBySystemPath('/taxonomy/term/3', 'en'));

    // The French translation of node 8 (its node ID on source is "9") has
    // path auto state "0", but the other translations do not have states.
    // So node 8 should't get generated path aliases.
    $this->assertEquals(NULL, $path_alias_repository->lookupBySystemPath('/node/8', 'en'));
    Node::load(8)->save();
    $this->assertEquals(NULL, $path_alias_repository->lookupBySystemPath('/node/8', 'en'));
  }

  /**
   * Asserts pathauto initial imports.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function assertPathautoInitialImports() {
    $this->assertStrictInitialImport([
      'block_content_body_field' => [1, 1, 1, 0, 0],
      'block_content_entity_display' => [1, 1, 1, 0, 0],
      'block_content_entity_form_display' => [1, 1, 1, 0, 0],
      'block_content_type' => [1, 1, 1, 0, 0],
      'd7_comment_entity_display:article' => [1, 1, 1, 0, 0],
      'd7_comment_entity_display:a_thirty_two_character_type_name' => [1, 1, 1, 0, 0],
      'd7_comment_entity_display:blog' => [1, 1, 1, 0, 0],
      'd7_comment_entity_display:book' => [1, 1, 1, 0, 0],
      'd7_comment_entity_display:et' => [1, 1, 1, 0, 0],
      'd7_comment_entity_display:forum' => [1, 1, 1, 0, 0],
      'd7_comment_entity_display:page' => [1, 1, 1, 0, 0],
      'd7_comment_entity_display:test_content_type' => [1, 1, 1, 0, 0],
      'd7_comment_entity_form_display:article' => [1, 1, 1, 0, 0],
      'd7_comment_entity_form_display:a_thirty_two_character_type_name' => [1, 1, 1, 0, 0],
      'd7_comment_entity_form_display:blog' => [1, 1, 1, 0, 0],
      'd7_comment_entity_form_display:book' => [1, 1, 1, 0, 0],
      'd7_comment_entity_form_display:et' => [1, 1, 1, 0, 0],
      'd7_comment_entity_form_display:forum' => [1, 1, 1, 0, 0],
      'd7_comment_entity_form_display:page' => [1, 1, 1, 0, 0],
      'd7_comment_entity_form_display:test_content_type' => [1, 1, 1, 0, 0],
      'd7_comment_entity_form_display_subject:article' => [1, 1, 1, 0, 0],
      'd7_comment_entity_form_display_subject:a_thirty_two_character_type_name' => [1, 1, 1, 0, 0],
      'd7_comment_entity_form_display_subject:blog' => [1, 1, 1, 0, 0],
      'd7_comment_entity_form_display_subject:book' => [1, 1, 1, 0, 0],
      'd7_comment_entity_form_display_subject:et' => [1, 1, 1, 0, 0],
      'd7_comment_entity_form_display_subject:forum' => [1, 1, 1, 0, 0],
      'd7_comment_entity_form_display_subject:page' => [1, 1, 1, 0, 0],
      'd7_comment_entity_form_display_subject:test_content_type' => [1, 1, 1, 0, 0],
      'd7_comment_field:article' => [1, 1, 1, 0, 0],
      'd7_comment_field:a_thirty_two_character_type_name' => [1, 1, 1, 0, 0],
      'd7_comment_field:blog' => [1, 1, 1, 0, 0],
      'd7_comment_field:book' => [1, 1, 1, 0, 0],
      'd7_comment_field:et' => [1, 1, 1, 0, 0],
      'd7_comment_field:forum' => [1, 1, 1, 0, 0],
      'd7_comment_field:page' => [1, 1, 1, 0, 0],
      'd7_comment_field:test_content_type' => [1, 1, 1, 0, 0],
      'd7_comment_field_instance:article' => [1, 1, 1, 0, 0],
      'd7_comment_field_instance:a_thirty_two_character_type_name' => [1, 1, 1, 0, 0],
      'd7_comment_field_instance:blog' => [1, 1, 1, 0, 0],
      'd7_comment_field_instance:book' => [1, 1, 1, 0, 0],
      'd7_comment_field_instance:et' => [1, 1, 1, 0, 0],
      'd7_comment_field_instance:forum' => [1, 1, 1, 0, 0],
      'd7_comment_field_instance:page' => [1, 1, 1, 0, 0],
      'd7_comment_field_instance:test_content_type' => [1, 1, 1, 0, 0],
      'd7_comment_type:article' => [1, 1, 1, 0, 0],
      'd7_comment_type:a_thirty_two_character_type_name' => [1, 1, 1, 0, 0],
      'd7_comment_type:blog' => [1, 1, 1, 0, 0],
      'd7_comment_type:book' => [1, 1, 1, 0, 0],
      'd7_comment_type:et' => [1, 1, 1, 0, 0],
      'd7_comment_type:forum' => [1, 1, 1, 0, 0],
      'd7_comment_type:page' => [1, 1, 1, 0, 0],
      'd7_comment_type:test_content_type' => [1, 1, 1, 0, 0],
      'd7_field:comment' => [2, 2, 2, 0, 0],
      'd7_field:node' => [52, 52, 51, 0, 1],
      'd7_field:taxonomy_term' => [5, 5, 5, 0, 0],
      'd7_field:user' => [3, 3, 3, 0, 0],
      'd7_field_formatter_settings:comment:article' => [1, 1, 1, 0, 0],
      'd7_field_formatter_settings:comment:a_thirty_two_character_type_name' => [1, 1, 1, 0, 0],
      'd7_field_formatter_settings:comment:blog' => [1, 1, 1, 0, 0],
      'd7_field_formatter_settings:comment:book' => [1, 1, 1, 0, 0],
      'd7_field_formatter_settings:comment:et' => [1, 1, 1, 0, 0],
      'd7_field_formatter_settings:comment:forum' => [1, 1, 1, 0, 0],
      'd7_field_formatter_settings:comment:page' => [1, 1, 1, 0, 0],
      'd7_field_formatter_settings:comment:test_content_type' => [2, 2, 2, 0, 0],
      'd7_field_formatter_settings:node:article' => [25, 25, 25, 0, 0],
      'd7_field_formatter_settings:node:a_thirty_two_character_type_name' => [2, 2, 2, 0, 0],
      'd7_field_formatter_settings:node:blog' => [10, 10, 10, 0, 0],
      'd7_field_formatter_settings:node:book' => [2, 2, 2, 0, 0],
      'd7_field_formatter_settings:node:et' => [1, 1, 1, 0, 0],
      'd7_field_formatter_settings:node:forum' => [5, 5, 4, 0, 0],
      'd7_field_formatter_settings:node:page' => [11, 11, 11, 0, 0],
      'd7_field_formatter_settings:node:test_content_type' => [24, 24, 24, 0, 0],
      'd7_field_formatter_settings:taxonomy_term:test_vocabulary' => [2, 2, 2, 0, 0],
      'd7_field_formatter_settings:taxonomy_term:vocabfixed' => [1, 1, 1, 0, 0],
      'd7_field_formatter_settings:taxonomy_term:vocablocalized' => [1, 1, 1, 0, 0],
      'd7_field_formatter_settings:taxonomy_term:vocabtranslate' => [1, 1, 1, 0, 0],
      'd7_field_formatter_settings:user:user' => [3, 3, 3, 0, 0],
      'd7_field_instance:comment:article' => [1, 1, 1, 0, 0],
      'd7_field_instance:comment:a_thirty_two_character_type_name' => [1, 1, 1, 0, 0],
      'd7_field_instance:comment:blog' => [1, 1, 1, 0, 0],
      'd7_field_instance:comment:book' => [1, 1, 1, 0, 0],
      'd7_field_instance:comment:et' => [1, 1, 1, 0, 0],
      'd7_field_instance:comment:forum' => [1, 1, 1, 0, 0],
      'd7_field_instance:comment:page' => [1, 1, 1, 0, 0],
      'd7_field_instance:comment:test_content_type' => [2, 2, 2, 0, 0],
      'd7_field_instance:node:article' => [21, 21, 21, 0, 0],
      'd7_field_instance:node:a_thirty_two_character_type_name' => [1, 1, 1, 0, 0],
      'd7_field_instance:node:blog' => [9, 9, 9, 0, 0],
      'd7_field_instance:node:book' => [1, 1, 1, 0, 0],
      'd7_field_instance:node:et' => [1, 1, 1, 0, 0],
      'd7_field_instance:node:forum' => [3, 3, 2, 0, 1],
      'd7_field_instance:node:page' => [10, 10, 10, 0, 0],
      'd7_field_instance:node:test_content_type' => [23, 23, 23, 0, 0],
      'd7_field_instance:taxonomy_term:test_vocabulary' => [2, 2, 2, 0, 0],
      'd7_field_instance:taxonomy_term:vocabfixed' => [1, 1, 1, 0, 0],
      'd7_field_instance:taxonomy_term:vocablocalized' => [1, 1, 1, 0, 0],
      'd7_field_instance:taxonomy_term:vocabtranslate' => [1, 1, 1, 0, 0],
      'd7_field_instance:user:user' => [3, 3, 3, 0, 0],
      'd7_field_instance_widget_settings:comment:article' => [1, 1, 1, 0, 0],
      'd7_field_instance_widget_settings:comment:a_thirty_two_character_type_name' => [1, 1, 1, 0, 0],
      'd7_field_instance_widget_settings:comment:blog' => [1, 1, 1, 0, 0],
      'd7_field_instance_widget_settings:comment:book' => [1, 1, 1, 0, 0],
      'd7_field_instance_widget_settings:comment:et' => [1, 1, 1, 0, 0],
      'd7_field_instance_widget_settings:comment:forum' => [1, 1, 1, 0, 0],
      'd7_field_instance_widget_settings:comment:page' => [1, 1, 1, 0, 0],
      'd7_field_instance_widget_settings:comment:test_content_type' => [2, 2, 2, 0, 0],
      'd7_field_instance_widget_settings:node:article' => [21, 21, 21, 0, 0],
      'd7_field_instance_widget_settings:node:a_thirty_two_character_type_name' => [1, 1, 1, 0, 0],
      'd7_field_instance_widget_settings:node:blog' => [9, 9, 9, 0, 0],
      'd7_field_instance_widget_settings:node:book' => [1, 1, 1, 0, 0],
      'd7_field_instance_widget_settings:node:et' => [1, 1, 1, 0, 0],
      'd7_field_instance_widget_settings:node:forum' => [3, 3, 2, 0, 0],
      'd7_field_instance_widget_settings:node:page' => [10, 10, 10, 0, 0],
      'd7_field_instance_widget_settings:node:test_content_type' => [23, 23, 23, 0, 0],
      'd7_field_instance_widget_settings:taxonomy_term:test_vocabulary' => [2, 2, 2, 0, 0],
      'd7_field_instance_widget_settings:taxonomy_term:vocabfixed' => [1, 1, 1, 0, 0],
      'd7_field_instance_widget_settings:taxonomy_term:vocablocalized' => [1, 1, 1, 0, 0],
      'd7_field_instance_widget_settings:taxonomy_term:vocabtranslate' => [1, 1, 1, 0, 0],
      'd7_field_instance_widget_settings:user:user' => [3, 3, 3, 0, 0],
      'd7_filter_format' => [5, 5, 5, 0, 0],
      'd7_menu' => [6, 6, 6, 0, 0],
      'd7_node_title_label:forum' => [1, 1, 1, 0, 0],
      'd7_node_type:article' => [1, 1, 1, 0, 0],
      'd7_node_type:a_thirty_two_character_type_name' => [1, 1, 1, 0, 0],
      'd7_node_type:blog' => [1, 1, 1, 0, 0],
      'd7_node_type:book' => [1, 1, 1, 0, 0],
      'd7_node_type:et' => [1, 1, 1, 0, 0],
      'd7_node_type:forum' => [1, 1, 1, 0, 0],
      'd7_node_type:page' => [1, 1, 1, 0, 0],
      'd7_node_type:test_content_type' => [1, 1, 1, 0, 0],
      'd7_pathauto_patterns:node:article' => [4, 4, 4, 0, 0],
      'd7_pathauto_patterns:node:blog' => [1, 1, 1, 0, 0],
      'd7_pathauto_patterns:node:et' => [1, 1, 1, 0, 0],
      'd7_pathauto_patterns:node_default' => [1, 1, 1, 0, 0],
      'd7_pathauto_patterns:taxonomy_term:sujet_de_discussion' => [1, 1, 1, 0, 0],
      'd7_pathauto_patterns:taxonomy_term:tags' => [1, 1, 1, 0, 0],
      'd7_pathauto_patterns:taxonomy_term_default' => [1, 1, 1, 0, 0],
      'd7_pathauto_patterns:user_default' => [1, 1, 1, 0, 0],
      'd7_shortcut_set' => [2, 2, 2, 0, 0],
      'd7_taxonomy_vocabulary:sujet_de_discussion' => [1, 1, 1, 0, 0],
      'd7_taxonomy_vocabulary:tags' => [1, 1, 1, 0, 0],
      'd7_taxonomy_vocabulary:test_vocabulary' => [1, 1, 1, 0, 0],
      'd7_taxonomy_vocabulary:vocabfixed' => [1, 1, 1, 0, 0],
      'd7_taxonomy_vocabulary:vocablocalized' => [1, 1, 1, 0, 0],
      'd7_taxonomy_vocabulary:vocablocalized2' => [1, 1, 1, 0, 0],
      'd7_taxonomy_vocabulary:vocabtranslate' => [1, 1, 1, 0, 0],
      'd7_taxonomy_vocabulary:vocabulary_name_much_longer_than_thirty_two_characters' => [1, 1, 1, 0, 0],
      'd7_user_role' => [3, 3, 3, 0, 0],
      'd7_view_modes:comment' => [1, 1, 1, 0, 0],
      'd7_view_modes:node' => [3, 3, 3, 0, 0],
      'd7_view_modes:taxonomy_term' => [1, 1, 1, 0, 0],
      'd7_view_modes:user' => [1, 1, 1, 0, 0],
      'user_picture_entity_display' => [1, 1, 1, 0, 0],
      'user_picture_entity_form_display' => [1, 1, 1, 0, 0],
      'user_picture_field' => [1, 1, 1, 0, 0],
      'user_picture_field_instance' => [1, 1, 1, 0, 0],
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
    ]);
  }

}
