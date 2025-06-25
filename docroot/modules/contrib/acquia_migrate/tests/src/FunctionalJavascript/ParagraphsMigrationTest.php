<?php

namespace Drupal\Tests\acquia_migrate\FunctionalJavascript;

use Drupal\Core\Url;

/**
 * Tests language neutral and english paragraphs node migration.
 *
 * For the explanation why we use WebDriver test for testing the Paragraphs
 * migration integration, check the class comment of MigrateJsUiTrait.
 *
 * @requires function Drupal\Tests\acquia_migrate\FunctionalJavascript\ParagraphsMigrationTest::setupMigrationConnection
 * @group acquia_migrate
 * @group acquia_migrate__contrib
 */
class ParagraphsMigrationTest extends ParagraphsMigrationTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'standard';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'acquia_migrate',
    'media_migration',
    'paragraphs',
    'paragraphs_migration',
    'telephone',
  ];

  /**
   * Returns the path to the fixture file.
   */
  protected function getFixtureFilePath() {
    return drupal_get_path('module', 'paragraphs_migration') . '/tests/fixtures/drupal7.php';
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->setupMigrationConnection();

    // Write public and private file location into settings.php.
    $public_base_path = realpath(DRUPAL_ROOT . '/' . drupal_get_path('module', 'paragraphs_migration') . '/tests/fixtures');
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
   * Tests language neutral and english paragraphs node migration.
   */
  public function testParagraphsAndFieldCollectionMigration() {
    $this->assertParagraphsInitialImport();

    // Run only those migrations that are required for paragraphs and field
    // collection migrations.
    $this->runMigrations([
      'User accounts',
      'Shared data for nested field collection items',
      'Shared data for nested paragraphs',
    ]);
    $this->runMigrations([
      'Public files',
      'Private files',
    ]);
    $this->runMigrations([
      'Image media items',
      'Document (private) media items (private)',
      'Document media items',
    ]);
    $this->runMigrations([
      'Content type with Field Collections',
      'Content type with Paragraphs',
      'Paragraphs Test',
    ]);

    // Have to reset all the static caches after migration to ensure entities
    // are loadable.
    $this->resetAll();

    // Leave the dashboard.
    $this->drupalGet(Url::fromRoute('system.admin_content'));
    $this->assertNoWarningOrErrorMessages();

    $this->assertNode8Paragraphs();
    $this->assertNode9Paragraphs();
    $this->assertNode11Paragraphs();
    $this->assertNode12Paragraphs();
    $this->assertNode13Paragraphs();
    $this->assertNode14Paragraphs();
  }

  /**
   * Executing only the "Content type with Field Collections" migrations.
   *
   * Migrating users and "Shared data for nested field collection items" should
   * be enough to be able to execute the "Content type with Field Collections"
   * migration.
   */
  public function testOnlyFieldCollectionTestContentMigration() {
    $this->runMigrations([
      'User accounts',
      'Shared data for nested field collection items',
    ]);
    $this->runMigrations([
      'Public files',
      'Private files',
    ]);
    $this->runMigrations([
      'Image media items',
      'Document (private) media items (private)',
      'Document media items',
    ]);
    $this->runMigrations([
      'Content type with Field Collections',
    ]);

    $this->resetAll();

    $this->drupalGet(Url::fromRoute('system.admin_content'));
    $this->assertNoWarningOrErrorMessages();

    $this->assertNode13Paragraphs();
    $this->assertNode14Paragraphs();
  }

  /**
   * Executing only the "Content type with Paragraphs" migrations.
   *
   * Migrating users and "Shared data for nested paragraphs" should be enough to
   * be able to execute the "Content type with Paragraphs" migration.
   */
  public function testOnlyParagraphsTestContentMigration() {
    $this->runMigrations([
      'User accounts',
      'Shared data for nested paragraphs',
    ]);
    $this->runMigrations([
      'Public files',
      'Private files',
    ]);
    $this->runMigrations([
      'Image media items',
      'Document (private) media items (private)',
      'Document media items',
    ]);
    $this->runMigrations([
      'Content type with Paragraphs',
    ]);

    $this->resetAll();

    $this->drupalGet(Url::fromRoute('system.admin_content'));
    $this->assertNoWarningOrErrorMessages();

    $this->assertNode12Paragraphs();
  }

  /**
   * Asserts paragraphs initial imports.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function assertParagraphsInitialImport() {
    $this->assertStrictInitialImport([
      'block_content_body_field' => [1, 1, 1, 0, 0],
      'block_content_entity_display' => [1, 1, 1, 0, 0],
      'block_content_entity_form_display' => [1, 1, 1, 0, 0],
      'block_content_type' => [1, 1, 1, 0, 0],
      'd7_comment_entity_display:article' => [1, 1, 1, 0, 0],
      'd7_comment_entity_display:blog' => [1, 1, 1, 0, 0],
      'd7_comment_entity_display:book' => [1, 1, 1, 0, 0],
      'd7_comment_entity_display:content_with_coll' => [1, 1, 1, 0, 0],
      'd7_comment_entity_display:content_with_para' => [1, 1, 1, 0, 0],
      'd7_comment_entity_display:forum' => [1, 1, 1, 0, 0],
      'd7_comment_entity_display:page' => [1, 1, 1, 0, 0],
      'd7_comment_entity_display:paragraphs_test' => [1, 1, 1, 0, 0],
      'd7_comment_entity_display:test_content_type' => [1, 1, 1, 0, 0],
      'd7_comment_entity_form_display:article' => [1, 1, 1, 0, 0],
      'd7_comment_entity_form_display:blog' => [1, 1, 1, 0, 0],
      'd7_comment_entity_form_display:book' => [1, 1, 1, 0, 0],
      'd7_comment_entity_form_display:content_with_coll' => [1, 1, 1, 0, 0],
      'd7_comment_entity_form_display:content_with_para' => [1, 1, 1, 0, 0],
      'd7_comment_entity_form_display:forum' => [1, 1, 1, 0, 0],
      'd7_comment_entity_form_display:page' => [1, 1, 1, 0, 0],
      'd7_comment_entity_form_display:paragraphs_test' => [1, 1, 1, 0, 0],
      'd7_comment_entity_form_display:test_content_type' => [1, 1, 1, 0, 0],
      'd7_comment_entity_form_display_subject:article' => [1, 1, 1, 0, 0],
      'd7_comment_entity_form_display_subject:blog' => [1, 1, 1, 0, 0],
      'd7_comment_entity_form_display_subject:book' => [1, 1, 1, 0, 0],
      'd7_comment_entity_form_display_subject:content_with_coll' => [1, 1, 1, 0, 0],
      'd7_comment_entity_form_display_subject:content_with_para' => [1, 1, 1, 0, 0],
      'd7_comment_entity_form_display_subject:forum' => [1, 1, 1, 0, 0],
      'd7_comment_entity_form_display_subject:page' => [1, 1, 1, 0, 0],
      'd7_comment_entity_form_display_subject:paragraphs_test' => [1, 1, 1, 0, 0],
      'd7_comment_entity_form_display_subject:test_content_type' => [1, 1, 1, 0, 0],
      'd7_comment_field:article' => [1, 1, 1, 0, 0],
      'd7_comment_field:blog' => [1, 1, 1, 0, 0],
      'd7_comment_field:book' => [1, 1, 1, 0, 0],
      'd7_comment_field:content_with_coll' => [1, 1, 1, 0, 0],
      'd7_comment_field:content_with_para' => [1, 1, 1, 0, 0],
      'd7_comment_field:forum' => [1, 1, 1, 0, 0],
      'd7_comment_field:page' => [1, 1, 1, 0, 0],
      'd7_comment_field:paragraphs_test' => [1, 1, 1, 0, 0],
      'd7_comment_field:test_content_type' => [1, 1, 1, 0, 0],
      'd7_comment_field_instance:article' => [1, 1, 1, 0, 0],
      'd7_comment_field_instance:blog' => [1, 1, 1, 0, 0],
      'd7_comment_field_instance:book' => [1, 1, 1, 0, 0],
      'd7_comment_field_instance:content_with_coll' => [1, 1, 1, 0, 0],
      'd7_comment_field_instance:content_with_para' => [1, 1, 1, 0, 0],
      'd7_comment_field_instance:forum' => [1, 1, 1, 0, 0],
      'd7_comment_field_instance:page' => [1, 1, 1, 0, 0],
      'd7_comment_field_instance:paragraphs_test' => [1, 1, 1, 0, 0],
      'd7_comment_field_instance:test_content_type' => [1, 1, 1, 0, 0],
      'd7_comment_type:article' => [1, 1, 1, 0, 0],
      'd7_comment_type:blog' => [1, 1, 1, 0, 0],
      'd7_comment_type:book' => [1, 1, 1, 0, 0],
      'd7_comment_type:content_with_coll' => [1, 1, 1, 0, 0],
      'd7_comment_type:content_with_para' => [1, 1, 1, 0, 0],
      'd7_comment_type:forum' => [1, 1, 1, 0, 0],
      'd7_comment_type:page' => [1, 1, 1, 0, 0],
      'd7_comment_type:paragraphs_test' => [1, 1, 1, 0, 0],
      'd7_comment_type:test_content_type' => [1, 1, 1, 0, 0],
      'd7_field:comment' => [2, 2, 2, 0, 0],
      'd7_field:field_collection_item' => [4, 4, 4, 0, 0],
      'd7_field:node' => [39, 39, 39, 0, 0],
      'd7_field:paragraphs_item' => [5, 5, 5, 0, 0],
      'd7_field:taxonomy_term' => [2, 2, 2, 0, 0],
      'd7_field:user' => [2, 2, 2, 0, 0],
      'd7_pm_field_collection_type' => [5, 5, 5, 0, 0],
      'd7_field_formatter_settings:comment:article' => [1, 1, 1, 0, 0],
      'd7_field_formatter_settings:comment:blog' => [1, 1, 1, 0, 0],
      'd7_field_formatter_settings:comment:book' => [1, 1, 1, 0, 0],
      'd7_field_formatter_settings:comment:content_with_coll' => [1, 1, 1, 0, 0],
      'd7_field_formatter_settings:comment:content_with_para' => [1, 1, 1, 0, 0],
      'd7_field_formatter_settings:comment:forum' => [1, 1, 1, 0, 0],
      'd7_field_formatter_settings:comment:page' => [1, 1, 1, 0, 0],
      'd7_field_formatter_settings:comment:paragraphs_test' => [1, 1, 1, 0, 0],
      'd7_field_formatter_settings:comment:test_content_type' => [2, 2, 2, 0, 0],
      'd7_field_formatter_settings:field_collection_item:field_field_collection_test' => [2, 2, 2, 0, 0],
      'd7_field_formatter_settings:field_collection_item:field_nested_fc_inner' => [1, 1, 1, 0, 0],
      'd7_field_formatter_settings:field_collection_item:field_nested_fc_inner_outer' => [2, 2, 2, 0, 0],
      'd7_field_formatter_settings:field_collection_item:field_nested_fc_outer' => [1, 1, 1, 0, 0],
      'd7_field_formatter_settings:field_collection_item:field_nested_fc_outer_2' => [3, 3, 3, 0, 0],
      'd7_field_formatter_settings:node:article' => [17, 17, 17, 0, 0],
      'd7_field_formatter_settings:node:blog' => [3, 3, 3, 0, 0],
      'd7_field_formatter_settings:node:book' => [2, 2, 2, 0, 0],
      'd7_field_formatter_settings:node:content_with_coll' => [2, 2, 2, 0, 0],
      'd7_field_formatter_settings:node:content_with_para' => [1, 1, 1, 0, 0],
      'd7_field_formatter_settings:node:forum' => [4, 4, 4, 0, 0],
      'd7_field_formatter_settings:node:page' => [11, 11, 11, 0, 0],
      'd7_field_formatter_settings:node:paragraphs_test' => [6, 6, 6, 0, 0],
      'd7_field_formatter_settings:node:test_content_type' => [21, 21, 21, 0, 0],
      'd7_field_formatter_settings:paragraphs_item:nested_host' => [4, 4, 4, 0, 0],
      'd7_field_formatter_settings:paragraphs_item:nested_host_2' => [4, 4, 4, 0, 0],
      'd7_field_formatter_settings:paragraphs_item:paragraph_bundle_one' => [4, 4, 4, 0, 0],
      'd7_field_formatter_settings:paragraphs_item:paragraph_bundle_two' => [2, 2, 2, 0, 0],
      'd7_field_formatter_settings:taxonomy_term:test_vocabulary' => [2, 2, 2, 0, 0],
      'd7_field_formatter_settings:user:user' => [2, 2, 2, 0, 0],
      'd7_field_instance:comment:article' => [1, 1, 1, 0, 0],
      'd7_field_instance:comment:blog' => [1, 1, 1, 0, 0],
      'd7_field_instance:comment:book' => [1, 1, 1, 0, 0],
      'd7_field_instance:comment:content_with_coll' => [1, 1, 1, 0, 0],
      'd7_field_instance:comment:content_with_para' => [1, 1, 1, 0, 0],
      'd7_field_instance:comment:forum' => [1, 1, 1, 0, 0],
      'd7_field_instance:comment:page' => [1, 1, 1, 0, 0],
      'd7_field_instance:comment:paragraphs_test' => [1, 1, 1, 0, 0],
      'd7_field_instance:comment:test_content_type' => [2, 2, 2, 0, 0],
      'd7_field_instance:field_collection_item:field_field_collection_test' => [2, 2, 2, 0, 0],
      'd7_field_instance:field_collection_item:field_nested_fc_inner' => [1, 1, 1, 0, 0],
      'd7_field_instance:field_collection_item:field_nested_fc_inner_outer' => [2, 2, 2, 0, 0],
      'd7_field_instance:field_collection_item:field_nested_fc_outer' => [1, 1, 1, 0, 0],
      'd7_field_instance:field_collection_item:field_nested_fc_outer_2' => [3, 3, 3, 0, 0],
      'd7_field_instance:node:article' => [13, 13, 13, 0, 0],
      'd7_field_instance:node:blog' => [2, 2, 2, 0, 0],
      'd7_field_instance:node:book' => [1, 1, 1, 0, 0],
      'd7_field_instance:node:content_with_coll' => [2, 2, 2, 0, 0],
      'd7_field_instance:node:content_with_para' => [1, 1, 1, 0, 0],
      'd7_field_instance:node:forum' => [2, 2, 2, 0, 0],
      'd7_field_instance:node:page' => [10, 10, 10, 0, 0],
      'd7_field_instance:node:paragraphs_test' => [5, 5, 5, 0, 0],
      'd7_field_instance:node:test_content_type' => [21, 21, 21, 0, 0],
      'd7_field_instance:paragraphs_item:nested_host' => [2, 2, 2, 0, 0],
      'd7_field_instance:paragraphs_item:nested_host_2' => [2, 2, 2, 0, 0],
      'd7_field_instance:paragraphs_item:paragraph_bundle_one' => [2, 2, 2, 0, 0],
      'd7_field_instance:paragraphs_item:paragraph_bundle_two' => [2, 2, 2, 0, 0],
      'd7_field_instance:taxonomy_term:test_vocabulary' => [2, 2, 2, 0, 0],
      'd7_field_instance:user:user' => [2, 2, 2, 0, 0],
      'd7_field_instance_widget_settings:comment:article' => [1, 1, 1, 0, 0],
      'd7_field_instance_widget_settings:comment:blog' => [1, 1, 1, 0, 0],
      'd7_field_instance_widget_settings:comment:book' => [1, 1, 1, 0, 0],
      'd7_field_instance_widget_settings:comment:content_with_coll' => [1, 1, 1, 0, 0],
      'd7_field_instance_widget_settings:comment:content_with_para' => [1, 1, 1, 0, 0],
      'd7_field_instance_widget_settings:comment:forum' => [1, 1, 1, 0, 0],
      'd7_field_instance_widget_settings:comment:page' => [1, 1, 1, 0, 0],
      'd7_field_instance_widget_settings:comment:paragraphs_test' => [1, 1, 1, 0, 0],
      'd7_field_instance_widget_settings:comment:test_content_type' => [2, 2, 2, 0, 0],
      'd7_field_instance_widget_settings:field_collection_item:field_field_collection_test' => [2, 2, 2, 0, 0],
      'd7_field_instance_widget_settings:field_collection_item:field_nested_fc_inner' => [1, 1, 1, 0, 0],
      'd7_field_instance_widget_settings:field_collection_item:field_nested_fc_inner_outer' => [2, 2, 2, 0, 0],
      'd7_field_instance_widget_settings:field_collection_item:field_nested_fc_outer' => [1, 1, 1, 0, 0],
      'd7_field_instance_widget_settings:field_collection_item:field_nested_fc_outer_2' => [3, 3, 3, 0, 0],
      'd7_field_instance_widget_settings:node:article' => [13, 13, 13, 0, 0],
      'd7_field_instance_widget_settings:node:blog' => [2, 2, 2, 0, 0],
      'd7_field_instance_widget_settings:node:book' => [1, 1, 1, 0, 0],
      'd7_field_instance_widget_settings:node:content_with_coll' => [2, 2, 2, 0, 0],
      'd7_field_instance_widget_settings:node:content_with_para' => [1, 1, 1, 0, 0],
      'd7_field_instance_widget_settings:node:forum' => [2, 2, 2, 0, 0],
      'd7_field_instance_widget_settings:node:page' => [10, 10, 10, 0, 0],
      'd7_field_instance_widget_settings:node:paragraphs_test' => [5, 5, 5, 0, 0],
      'd7_field_instance_widget_settings:node:test_content_type' => [21, 21, 21, 0, 0],
      'd7_field_instance_widget_settings:paragraphs_item:nested_host' => [2, 2, 2, 0, 0],
      'd7_field_instance_widget_settings:paragraphs_item:nested_host_2' => [2, 2, 2, 0, 0],
      'd7_field_instance_widget_settings:paragraphs_item:paragraph_bundle_one' => [2, 2, 2, 0, 0],
      'd7_field_instance_widget_settings:paragraphs_item:paragraph_bundle_two' => [2, 2, 2, 0, 0],
      'd7_field_instance_widget_settings:taxonomy_term:test_vocabulary' => [2, 2, 2, 0, 0],
      'd7_field_instance_widget_settings:user:user' => [2, 2, 2, 0, 0],
      'd7_file_plain_formatter:document' => [5, 5, 5, 0, 0],
      'd7_file_plain_formatter:document_private' => [5, 5, 5, 0, 0],
      'd7_file_plain_formatter:image' => [5, 5, 5, 0, 0],
      'd7_file_plain_source_field:document' => [1, 1, 1, 0, 0],
      'd7_file_plain_source_field:document_private' => [1, 1, 1, 0, 0],
      'd7_file_plain_source_field:image' => [1, 1, 1, 0, 0],
      'd7_file_plain_source_field_config:document' => [1, 1, 1, 0, 0],
      'd7_file_plain_source_field_config:document_private' => [1, 1, 1, 0, 0],
      'd7_file_plain_source_field_config:image' => [1, 1, 1, 0, 0],
      'd7_file_plain_type:document' => [1, 1, 1, 0, 0],
      'd7_file_plain_type:document_private' => [1, 1, 1, 0, 0],
      'd7_file_plain_type:image' => [1, 1, 1, 0, 0],
      'd7_file_plain_widget:document' => [1, 1, 1, 0, 0],
      'd7_file_plain_widget:document_private' => [1, 1, 1, 0, 0],
      'd7_file_plain_widget:image' => [1, 1, 1, 0, 0],
      'd7_filter_format' => [5, 5, 5, 0, 0],
      'd7_menu' => [5, 5, 5, 0, 0],
      'd7_node_title_label:forum' => [1, 1, 1, 0, 0],
      'd7_node_type:article' => [1, 1, 1, 0, 0],
      'd7_node_type:blog' => [1, 1, 1, 0, 0],
      'd7_node_type:book' => [1, 1, 1, 0, 0],
      'd7_node_type:content_with_coll' => [1, 1, 1, 0, 0],
      'd7_node_type:content_with_para' => [1, 1, 1, 0, 0],
      'd7_node_type:forum' => [1, 1, 1, 0, 0],
      'd7_node_type:page' => [1, 1, 1, 0, 0],
      'd7_node_type:paragraphs_test' => [1, 1, 1, 0, 0],
      'd7_node_type:test_content_type' => [1, 1, 1, 0, 0],
      'd7_pm_paragraphs_type' => [4, 4, 4, 0, 0],
      'd7_shortcut_set' => [2, 2, 2, 0, 0],
      'd7_shortcut_set_users' => [1, 0, 0, 0, 0],
      'd7_taxonomy_vocabulary:sujet_de_discussion' => [1, 1, 1, 0, 0],
      'd7_taxonomy_vocabulary:tags' => [1, 1, 1, 0, 0],
      'd7_taxonomy_vocabulary:test_vocabulary' => [1, 1, 1, 0, 0],
      'd7_taxonomy_vocabulary:vocabulary_name_much_longer_than_thirty_two_characters' => [1, 1, 1, 0, 0],
      'd7_user_role' => [3, 3, 3, 0, 0],
      'd7_view_modes:comment' => [1, 1, 1, 0, 0],
      'd7_view_modes:field_collection_item' => [1, 1, 1, 0, 0],
      'd7_view_modes:node' => [3, 3, 3, 0, 0],
      'd7_view_modes:paragraphs_item' => [2, 2, 2, 0, 0],
      'd7_view_modes:taxonomy_term' => [1, 1, 1, 0, 0],
      'd7_view_modes:user' => [1, 1, 1, 0, 0],
      'user_picture_entity_display' => [1, 1, 1, 0, 0],
      'user_picture_entity_form_display' => [1, 1, 1, 0, 0],
      'user_picture_field' => [1, 1, 1, 0, 0],
      'user_picture_field_instance' => [1, 1, 1, 0, 0],
    ]);
  }

}
