<?php

namespace Drupal\Tests\paragraphs_migration\Functional\Migrate;

use Behat\Mink\Exception\ExpectationException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\Tests\migrate_drupal_ui\Functional\MigrateUpgradeTestBase;

/**
 * Provides a base class for testing Paragraphs migration via the UI.
 */
abstract class MigrateUiParagraphsTestBase extends MigrateUpgradeTestBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'content_translation',
    'migrate_drupal_ui',
    'telephone',
    'paragraphs_migration',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getSourceBasePath() {
    return drupal_get_path('module', 'paragraphs_migration') . '/tests/fixtures';
  }

  /**
   * {@inheritdoc}
   */
  protected function getSourcePrivateFilesPath() {
    return drupal_get_path('module', 'paragraphs_migration') . '/tests/fixtures';
  }

  /**
   * Gets the expected entity IDs and labels per entity type after migration.
   *
   * @return mixed[][]
   *   An array of expected entity labels keyed by IDs, grouped by entity type
   *   ID. For some of the entities, label can be NULL. When all of the given
   *   entity IDs are numbers, then only the existence of the given labels is
   *   checked.
   */
  protected function getExpectedEntities() {
    $expected_entities = [
      'entity_form_display' => [
        'block_content.basic.default' => NULL,
        'comment.comment.default' => NULL,
        'comment.comment_forum.default' => NULL,
        'comment.comment_node_article.default' => NULL,
        'comment.comment_node_blog.default' => NULL,
        'comment.comment_node_book.default' => NULL,
        'comment.comment_node_content_with_coll.default' => NULL,
        'comment.comment_node_content_with_para.default' => NULL,
        'comment.comment_node_page.default' => NULL,
        'comment.comment_node_paragraphs_test.default' => NULL,
        'comment.comment_node_test_content_type.default' => NULL,
        'node.article.default' => NULL,
        'node.blog.default' => NULL,
        'node.book.default' => NULL,
        'node.content_with_coll.default' => NULL,
        'node.content_with_para.default' => NULL,
        'node.forum.default' => NULL,
        'node.page.default' => NULL,
        'node.paragraphs_test.default' => NULL,
        'node.test_content_type.default' => NULL,
        'paragraph.field_collection_test.default' => NULL,
        'paragraph.nested_fc_inner.default' => NULL,
        'paragraph.nested_fc_inner_outer.default' => NULL,
        'paragraph.nested_fc_outer.default' => NULL,
        'paragraph.nested_fc_outer_2.default' => NULL,
        'paragraph.nested_host.default' => NULL,
        'paragraph.nested_host_2.default' => NULL,
        'paragraph.paragraph_bundle_one.default' => NULL,
        'paragraph.paragraph_bundle_two.default' => NULL,
        'taxonomy_term.test_vocabulary.default' => NULL,
        'user.user.default' => NULL,
      ],
      'entity_form_mode' => [
        'user.register' => 'Register',
      ],
      'entity_view_display' => [
        'block_content.basic.default' => NULL,
        'comment.comment.default' => NULL,
        'comment.comment_forum.default' => NULL,
        'comment.comment_node_article.default' => NULL,
        'comment.comment_node_blog.default' => NULL,
        'comment.comment_node_book.default' => NULL,
        'comment.comment_node_content_with_coll.default' => NULL,
        'comment.comment_node_content_with_para.default' => NULL,
        'comment.comment_node_page.default' => NULL,
        'comment.comment_node_paragraphs_test.default' => NULL,
        'comment.comment_node_test_content_type.default' => NULL,
        'node.article.custom' => NULL,
        'node.article.default' => NULL,
        'node.article.rss' => NULL,
        'node.article.teaser' => NULL,
        'node.blog.default' => NULL,
        'node.blog.teaser' => NULL,
        'node.book.default' => NULL,
        'node.book.teaser' => NULL,
        'node.content_with_coll.default' => NULL,
        'node.content_with_para.default' => NULL,
        'node.forum.default' => NULL,
        'node.forum.teaser' => NULL,
        'node.page.default' => NULL,
        'node.page.teaser' => NULL,
        'node.paragraphs_test.default' => NULL,
        'node.paragraphs_test.teaser' => NULL,
        'node.test_content_type.default' => NULL,
        'paragraph.field_collection_test.default' => NULL,
        'paragraph.nested_fc_inner.default' => NULL,
        'paragraph.nested_fc_inner_outer.default' => NULL,
        'paragraph.nested_fc_outer.default' => NULL,
        'paragraph.nested_fc_outer_2.default' => NULL,
        'paragraph.nested_host.default' => NULL,
        'paragraph.nested_host.paragraphs_editor_preview' => NULL,
        'paragraph.nested_host_2.default' => NULL,
        'paragraph.nested_host_2.paragraphs_editor_preview' => NULL,
        'paragraph.paragraph_bundle_one.default' => NULL,
        'paragraph.paragraph_bundle_one.paragraphs_editor_preview' => NULL,
        'paragraph.paragraph_bundle_two.default' => NULL,
        'taxonomy_term.test_vocabulary.default' => NULL,
        'user.user.compact' => NULL,
        'user.user.default' => NULL,
      ],
      'entity_view_mode' => [
        'block_content.full' => 'Full',
        'comment.full' => 'Full',
        'node.custom' => 'custom',
        'node.full' => 'Full',
        'node.rss' => 'RSS',
        'node.search_index' => 'Search index',
        'node.search_result' => 'Search result highlighting input',
        'node.teaser' => 'Teaser',
        'paragraph.full' => 'Full',
        'paragraph.paragraphs_editor_preview' => 'paragraphs_editor_preview',
        'paragraph.preview' => 'Preview',
        'taxonomy_term.full' => 'Full',
        'user.compact' => 'Compact',
        'user.full' => 'Full',
      ],
      'field_storage_config' => [
        'block_content.body' => 'block_content.body',
        'comment.comment_body' => 'comment.comment_body',
        'comment.field_integer' => 'comment.field_integer',
        'node.body' => 'node.body',
        'node.comment' => 'node.comment',
        'node.comment_forum' => 'node.comment_forum',
        'node.comment_node_article' => 'node.comment_node_article',
        'node.comment_node_blog' => 'node.comment_node_blog',
        'node.comment_node_book' => 'node.comment_node_book',
        'node.comment_node_content_with_coll' => 'node.comment_node_content_with_coll',
        'node.comment_node_content_with_para' => 'node.comment_node_content_with_para',
        'node.comment_node_page' => 'node.comment_node_page',
        'node.comment_node_paragraphs_test' => 'node.comment_node_paragraphs_test',
        'node.comment_node_test_content_type' => 'node.comment_node_test_content_type',
        'node.field_any_paragraph' => 'node.field_any_paragraph',
        'node.field_boolean' => 'node.field_boolean',
        'node.field_date' => 'node.field_date',
        'node.field_date_with_end_time' => 'node.field_date_with_end_time',
        'node.field_date_without_time' => 'node.field_date_without_time',
        'node.field_datetime_without_time' => 'node.field_datetime_without_time',
        'node.field_email' => 'node.field_email',
        'node.field_field_collection_test' => 'node.field_field_collection_test',
        'node.field_file' => 'node.field_file',
        'node.field_float' => 'node.field_float',
        'node.field_image' => 'node.field_image',
        'node.field_images' => 'node.field_images',
        'node.field_integer' => 'node.field_integer',
        'node.field_integer_list' => 'node.field_integer_list',
        'node.field_link' => 'node.field_link',
        'node.field_long_text' => 'node.field_long_text',
        'node.field_nested_fc_outer' => 'node.field_nested_fc_outer',
        'node.field_nested_fc_outer_2' => 'node.field_nested_fc_outer_2',
        'node.field_node_entityreference' => 'node.field_node_entityreference',
        'node.field_paragraph_one_only' => 'node.field_paragraph_one_only',
        'node.field_phone' => 'node.field_phone',
        'node.field_private_file' => 'node.field_private_file',
        'node.field_tags' => 'node.field_tags',
        'node.field_term_entityreference' => 'node.field_term_entityreference',
        'node.field_term_reference' => 'node.field_term_reference',
        'node.field_text' => 'node.field_text',
        'node.field_text_filtered' => 'node.field_text_filtered',
        'node.field_text_list' => 'node.field_text_list',
        'node.field_text_long_filtered' => 'node.field_text_long_filtered',
        'node.field_text_long_plain' => 'node.field_text_long_plain',
        'node.field_text_plain' => 'node.field_text_plain',
        'node.field_text_sum_filtered' => 'node.field_text_sum_filtered',
        'node.field_user_entityreference' => 'node.field_user_entityreference',
        'node.taxonomy_forums' => 'node.taxonomy_forums',
        'paragraph.field_any_paragraph' => 'paragraph.field_any_paragraph',
        'paragraph.field_email' => 'paragraph.field_email',
        'paragraph.field_integer_list' => 'paragraph.field_integer_list',
        'paragraph.field_nested_fc_inner' => 'paragraph.field_nested_fc_inner',
        'paragraph.field_nested_fc_inner_outer' => 'paragraph.field_nested_fc_inner_outer',
        'paragraph.field_text' => 'paragraph.field_text',
        'paragraph.field_text_list' => 'paragraph.field_text_list',
        'paragraph.field_text_long_filtered' => 'paragraph.field_text_long_filtered',
        'taxonomy_term.field_integer' => 'taxonomy_term.field_integer',
        'taxonomy_term.field_term_reference' => 'taxonomy_term.field_term_reference',
        'user.field_file' => 'user.field_file',
        'user.field_integer' => 'user.field_integer',
        'user.user_picture' => 'user.user_picture',
      ],
      'field_config' => [
        'block_content.basic.body' => 'Body',
        'comment.comment.comment_body' => 'Comment',
        'comment.comment_forum.comment_body' => 'Comment',
        'comment.comment_node_article.comment_body' => 'Comment',
        'comment.comment_node_blog.comment_body' => 'Comment',
        'comment.comment_node_book.comment_body' => 'Comment',
        'comment.comment_node_content_with_coll.comment_body' => 'Comment',
        'comment.comment_node_content_with_para.comment_body' => 'Comment',
        'comment.comment_node_page.comment_body' => 'Comment',
        'comment.comment_node_paragraphs_test.comment_body' => 'Comment',
        'comment.comment_node_test_content_type.comment_body' => 'Comment',
        'comment.comment_node_test_content_type.field_integer' => 'Integer',
        'node.article.body' => 'Body',
        'node.article.comment' => 'Comments',
        'node.article.comment_node_article' => 'Comments',
        'node.article.field_image' => 'Image',
        'node.article.field_link' => 'Link',
        'node.article.field_tags' => 'Tags',
        'node.article.field_text_filtered' => 'Text filtered',
        'node.article.field_text_long_filtered' => 'Text long filtered',
        'node.article.field_text_long_plain' => 'Text long plain',
        'node.article.field_text_plain' => 'Text plain',
        'node.article.field_text_sum_filtered' => 'Text summary filtered',
        'node.blog.body' => 'Body',
        'node.blog.comment_node_blog' => 'Comments',
        'node.blog.field_link' => 'Link',
        'node.book.body' => 'Body',
        'node.book.comment_node_book' => 'Comments',
        'node.content_with_coll.comment_node_content_with_coll' => 'Comments',
        'node.content_with_coll.field_nested_fc_outer' => 'Nested FC Outer',
        'node.content_with_coll.field_nested_fc_outer_2' => 'Nested FC Outer 2',
        'node.content_with_para.comment_node_content_with_para' => 'Comments',
        'node.content_with_para.field_any_paragraph' => 'Any paragraphs',
        'node.forum.body' => 'Body',
        'node.forum.comment_forum' => 'Comments',
        'node.forum.taxonomy_forums' => 'Forums',
        'node.page.body' => 'Body',
        'node.page.comment_node_page' => 'Comments',
        'node.page.field_text_filtered' => 'Text filtered',
        'node.page.field_text_long_filtered' => 'Text long filtered',
        'node.page.field_text_long_plain' => 'Text long plain',
        'node.page.field_text_plain' => 'Text plain',
        'node.page.field_text_sum_filtered' => 'Text summary filtered',
        'node.paragraphs_test.body' => 'Body',
        'node.paragraphs_test.comment_node_paragraphs_test' => 'Comments',
        'node.paragraphs_test.field_any_paragraph' => 'Any Paragraph',
        'node.paragraphs_test.field_field_collection_test' => 'Field Collection Test',
        'node.paragraphs_test.field_nested_fc_outer' => 'Nested FC Outer',
        'node.paragraphs_test.field_paragraph_one_only' => 'Paragraph One Only',
        'node.test_content_type.field_boolean' => 'Boolean',
        'node.test_content_type.comment_node_test_content_type' => 'Comments',
        'node.test_content_type.field_date' => 'Date',
        'node.test_content_type.field_date_with_end_time' => 'Date With End Time',
        'node.test_content_type.field_date_without_time' => 'Date without time',
        'node.test_content_type.field_datetime_without_time' => 'Datetime without time',
        'node.test_content_type.field_email' => 'Email',
        'node.test_content_type.field_file' => 'File',
        'node.test_content_type.field_float' => 'Float',
        'node.test_content_type.field_images' => 'Images',
        'node.test_content_type.field_integer' => 'Integer',
        'node.test_content_type.field_integer_list' => 'Integer List',
        'node.test_content_type.field_link' => 'Link',
        'node.test_content_type.field_long_text' => 'Long text',
        'node.test_content_type.field_node_entityreference' => 'Node Entity Reference',
        'node.test_content_type.field_phone' => 'Phone',
        'node.test_content_type.field_private_file' => 'Private file',
        'node.test_content_type.field_term_entityreference' => 'Term Entity Reference',
        'node.test_content_type.field_term_reference' => 'Term Reference',
        'node.test_content_type.field_text' => 'Text',
        'node.test_content_type.field_text_list' => 'Text List',
        'node.test_content_type.field_user_entityreference' => 'User Entity Reference',
        'paragraph.field_collection_test.field_integer_list' => 'Integer List',
        'paragraph.field_collection_test.field_text' => 'Text',
        'paragraph.nested_fc_inner.field_text' => 'Text',
        'paragraph.nested_fc_inner_outer.field_nested_fc_inner' => 'Nested FC Inner',
        'paragraph.nested_fc_inner_outer.field_text' => 'Text',
        'paragraph.nested_fc_outer.field_nested_fc_inner' => 'Nested FC Inner',
        'paragraph.nested_fc_outer_2.field_nested_fc_inner' => 'Nested FC Inner',
        'paragraph.nested_fc_outer_2.field_nested_fc_inner_outer' => 'Nested FC Inner and Outer',
        'paragraph.nested_fc_outer_2.field_text' => 'Text',
        'paragraph.nested_host.field_any_paragraph' => "Nested host's Paragraphs",
        'paragraph.nested_host.field_text_long_filtered' => 'Text long filtered',
        'paragraph.nested_host_2.field_any_paragraph' => 'Host 2 Paragraphs',
        'paragraph.nested_host_2.field_text' => 'Text',
        'paragraph.paragraph_bundle_one.field_text' => 'Text',
        'paragraph.paragraph_bundle_one.field_text_list' => 'Text List',
        'paragraph.paragraph_bundle_two.field_email' => 'Email',
        'paragraph.paragraph_bundle_two.field_text' => 'Text',
        'taxonomy_term.test_vocabulary.field_integer' => 'Integer',
        'taxonomy_term.test_vocabulary.field_term_reference' => 'Term Reference',
        'user.user.field_file' => 'File',
        'user.user.field_integer' => 'Integer',
        'user.user.user_picture' => 'Picture',
      ],
      'node_type' => [
        'article' => 'Article',
        'blog' => 'Blog entry',
        'book' => 'Book page',
        'forum' => 'Forum topic',
        'page' => 'Basic page',
        'paragraphs_test' => 'Paragraphs Test',
        'test_content_type' => 'Test content type',
        'content_with_coll' => 'Content type with Field Collections',
        'content_with_para' => 'Content type with Paragraphs',
      ],
      'node' => [
        '1' => 'A Node',
        '2' => 'The thing about Deep Space 9',
        '4' => 'is - The thing about Firefly',
        '6' => 'Comments are closed :-(',
        '7' => 'Comments are open :-)',
        '8' => 'Paragraph Migration Test Content UND',
        '9' => 'Paragraph Migration Test Content EN',
        '11' => 'Content with nested paragraphs ENG',
        '12' => 'Nested Paragraphs',
        '13' => 'Nested Field Collections',
        '14' => 'Nested Field Collections 2',
      ],
      'paragraphs_type' => [
        'field_collection_test' => 'Field collection test',
        'nested_fc_inner' => 'Nested fc inner',
        'nested_fc_inner_outer' => 'Nested fc inner outer',
        'nested_fc_outer' => 'Nested fc outer',
        'nested_fc_outer_2' => 'Nested fc outer 2',
        'nested_host' => 'Host for nested paragraphs',
        'nested_host_2' => 'Another host',
        'paragraph_bundle_one' => 'Paragraph Bundle One',
        'paragraph_bundle_two' => 'Paragraph Bundle Two',
      ],
      // Paragraph IDs and labels with 'complete' migration, where node
      // revisions (even the active one) and node translations are migrated in a
      // single, complete node migration. The final IDs of the paragraph
      // entities aren't the same as the ones migrated with the 'classic'
      // migration.
      // @see https://www.drupal.org/node/3105503
      'paragraph' => [
        'Content with nested paragraphs ENG > Any Paragraph',
        'Content with nested paragraphs ENG > Any Paragraph',
        'Content with nested paragraphs ENG > Any Paragraph > Nested host\'s Paragraphs',
        'Content with nested paragraphs ENG > Any Paragraph > Nested host\'s Paragraphs',
        'Nested Field Collections 2 > Nested FC Outer',
        'Nested Field Collections 2 > Nested FC Outer',
        'Nested Field Collections 2 > Nested FC Outer > Nested FC Inner',
        'Nested Field Collections 2 > Nested FC Outer > Nested FC Inner',
        'Nested Field Collections 2 > Nested FC Outer > Nested FC Inner',
        'Nested Field Collections 2 > Nested FC Outer > Nested FC Inner',
        'Nested Field Collections > Nested FC Outer 2',
        'Nested Field Collections > Nested FC Outer 2 > Nested FC Inner',
        'Nested Field Collections > Nested FC Outer 2 > Nested FC Inner',
        'Nested Field Collections > Nested FC Outer 2 > Nested FC Inner and Outer',
        'Nested Field Collections > Nested FC Outer 2 > Nested FC Inner and Outer',
        'Nested Field Collections > Nested FC Outer 2 > Nested FC Inner and Outer > Nested FC Inner',
        'Nested Field Collections > Nested FC Outer 2 > Nested FC Inner and Outer > Nested FC Inner',
        'Nested Field Collections > Nested FC Outer 2 > Nested FC Inner and Outer > Nested FC Inner',
        'Nested Field Collections > Nested FC Outer 2 > Nested FC Inner and Outer > Nested FC Inner',
        'Nested Paragraphs > Any paragraphs',
        'Nested Paragraphs > Any paragraphs',
        'Nested Paragraphs > Any paragraphs',
        'Nested Paragraphs > Any paragraphs > Nested host\'s Paragraphs',
        'Nested Paragraphs > Any paragraphs > Nested host\'s Paragraphs',
        'Nested Paragraphs > Any paragraphs > Nested host\'s Paragraphs > Host 2 Paragraphs',
        'Paragraph Migration Test Content EN > Any Paragraph',
        'Paragraph Migration Test Content EN > Any Paragraph',
        'Paragraph Migration Test Content EN > Any Paragraph (previous revision)',
        'Paragraph Migration Test Content EN > Any Paragraph (previous revision)',
        'Paragraph Migration Test Content EN > Any Paragraph (previous revision)',
        'Paragraph Migration Test Content EN > Field Collection Test',
        'Paragraph Migration Test Content EN > Field Collection Test (previous revision)',
        'Paragraph Migration Test Content EN > Field Collection Test (previous revision)',
        'Paragraph Migration Test Content EN > Field Collection Test (previous revision)',
        'Paragraph Migration Test Content EN > Paragraph One Only',
        'Paragraph Migration Test Content EN > Paragraph One Only (previous revision)',
        'Paragraph Migration Test Content UND > Any Paragraph',
        'Paragraph Migration Test Content UND > Any Paragraph',
        'Paragraph Migration Test Content UND > Field Collection Test',
        'Paragraph Migration Test Content UND > Field Collection Test',
        'Paragraph Migration Test Content UND > Nested FC Outer',
        'Paragraph Migration Test Content UND > Nested FC Outer > Nested FC Inner',
        'Paragraph Migration Test Content UND > Paragraph One Only',
      ],
    ];

    // Paragraph IDs and labels with 'classic' node migration (core 8.8.x has
    // only this), where nodes, node revisions and node translations are
    // migrated separately.
    if (Settings::get('migrate_node_migrate_type_classic', FALSE)) {
      $expected_entities['paragraph'] = [
        'Content with nested paragraphs ENG > Any Paragraph',
        'Content with nested paragraphs ENG > Any Paragraph',
        'Content with nested paragraphs ENG > Any Paragraph > Nested host\'s Paragraphs',
        'Content with nested paragraphs ENG > Any Paragraph > Nested host\'s Paragraphs',
        'Nested Field Collections 2 > Nested FC Outer',
        'Nested Field Collections 2 > Nested FC Outer',
        'Nested Field Collections 2 > Nested FC Outer > Nested FC Inner',
        'Nested Field Collections 2 > Nested FC Outer > Nested FC Inner',
        'Nested Field Collections 2 > Nested FC Outer > Nested FC Inner',
        'Nested Field Collections 2 > Nested FC Outer > Nested FC Inner',
        'Nested Field Collections > Nested FC Outer 2',
        'Nested Field Collections > Nested FC Outer 2 > Nested FC Inner',
        'Nested Field Collections > Nested FC Outer 2 > Nested FC Inner',
        'Nested Field Collections > Nested FC Outer 2 > Nested FC Inner and Outer',
        'Nested Field Collections > Nested FC Outer 2 > Nested FC Inner and Outer',
        'Nested Field Collections > Nested FC Outer 2 > Nested FC Inner and Outer > Nested FC Inner',
        'Nested Field Collections > Nested FC Outer 2 > Nested FC Inner and Outer > Nested FC Inner',
        'Nested Field Collections > Nested FC Outer 2 > Nested FC Inner and Outer > Nested FC Inner',
        'Nested Field Collections > Nested FC Outer 2 > Nested FC Inner and Outer > Nested FC Inner',
        'Nested Paragraphs > Any paragraphs',
        'Nested Paragraphs > Any paragraphs',
        'Nested Paragraphs > Any paragraphs',
        'Nested Paragraphs > Any paragraphs > Nested host\'s Paragraphs',
        'Nested Paragraphs > Any paragraphs > Nested host\'s Paragraphs',
        'Nested Paragraphs > Any paragraphs > Nested host\'s Paragraphs > Host 2 Paragraphs',
        'Paragraph Migration Test Content EN > Any Paragraph',
        'Paragraph Migration Test Content EN > Any Paragraph',
        'Paragraph Migration Test Content EN > Any Paragraph (previous revision)',
        'Paragraph Migration Test Content EN > Any Paragraph (previous revision)',
        'Paragraph Migration Test Content EN > Any Paragraph (previous revision)',
        'Paragraph Migration Test Content EN > Field Collection Test',
        'Paragraph Migration Test Content EN > Field Collection Test (previous revision)',
        'Paragraph Migration Test Content EN > Field Collection Test (previous revision)',
        'Paragraph Migration Test Content EN > Field Collection Test (previous revision)',
        'Paragraph Migration Test Content EN > Paragraph One Only',
        'Paragraph Migration Test Content EN > Paragraph One Only (previous revision)',
        'Paragraph Migration Test Content UND > Any Paragraph (previous revision)',
        'Paragraph Migration Test Content UND > Any Paragraph (previous revision)',
        'Paragraph Migration Test Content UND > Field Collection Test (previous revision)',
        'Paragraph Migration Test Content UND > Field Collection Test (previous revision)',
        'Paragraph Migration Test Content UND > Nested FC Outer',
        'Paragraph Migration Test Content UND > Nested FC Outer > Nested FC Inner',
        'Paragraph Migration Test Content UND > Paragraph One Only (previous revision)',
      ];
    }

    return $expected_entities;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityCounts() {
    $entity_counts = [];

    foreach ($this->getExpectedEntities() as $entity_type_id => $expected_entities) {
      $entity_counts[$entity_type_id] = count($expected_entities);
    }

    return $entity_counts;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityCountsIncremental() {
    // Unused.
    return $this->getEntityCounts();
  }

  /**
   * {@inheritdoc}
   */
  protected function getAvailablePaths() {
    // Unused.
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function getMissingPaths() {
    // Unused.
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->loadFixture(drupal_get_path('module', 'paragraphs_migration') . '/tests/fixtures/drupal7.php');
  }

  /**
   * Executes the upgrade process by the UI and asserts basic expectations.
   */
  protected function assertMigrateUpgradeViaUi() {
    $this->submitCredentialForm();
    $session = $this->assertSession();
    $session->pageTextNotContains('Resolve all issues below to continue the upgrade.');

    // ID conflict form.
    $session->buttonExists($this->t('I acknowledge I may lose data. Continue anyway.'));
    $this->drupalPostForm(NULL, [], $this->t('I acknowledge I may lose data. Continue anyway.'));
    $session->statusCodeEquals(200);

    // Perform the upgrade.
    $this->submitForm([], $this->t('Perform upgrade'));
    $session->pageTextContains($this->t('Congratulations, you upgraded Drupal!'));

    // Have to reset all the statics after migration to ensure entities are
    // loadable.
    $this->resetAll();
  }

  /**
   * Checks that migrations have been performed successfully.
   */
  protected function assertParagraphsMigrationResults() {
    $version = $this->getLegacyDrupalVersion($this->sourceDatabase);

    $this->assertEntities();

    $plugin_manager = $this->container->get('plugin.manager.migration');
    /** @var \Drupal\migrate\Plugin\Migration[] $all_migrations */
    $all_migrations = $plugin_manager->createInstancesByTag('Drupal ' . $version);

    foreach ($all_migrations as $migration) {
      $id_map = $migration->getIdMap();
      foreach ($id_map as $source_id => $map) {
        // Convert $source_id into a keyless array so that
        // \Drupal\migrate\Plugin\migrate\id_map\Sql::getSourceHash() works as
        // expected.
        $source_id_values = array_values(unserialize($source_id));
        $row = $id_map->getRowBySource($source_id_values);
        $destination = serialize($id_map->currentDestination());
        $message = "Migration of $source_id to $destination as part of the {$migration->id()} migration. The source row status is " . $row['source_row_status'];
        // A completed migration should have maps with
        // MigrateIdMapInterface::STATUS_IGNORED or
        // MigrateIdMapInterface::STATUS_IMPORTED.
        $this->assertNotSame(MigrateIdMapInterface::STATUS_FAILED, $row['source_row_status'], $message);
        $this->assertNotSame(MigrateIdMapInterface::STATUS_NEEDS_UPDATE, $row['source_row_status'], $message);
      }
    }
  }

  /**
   * Pass if the page HTML title is the given string.
   *
   * @param string $expected_title
   *   The string the page title should be.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   *   Thrown when element doesn't exist, or the title is a different one.
   */
  protected function assertPageTitle($expected_title) {
    $page_title_element = $this->getSession()->getPage()->find('css', 'h1.page-title');
    if (!$page_title_element) {
      throw new ExpectationException('No page title element found on the page', $this->getSession()->getDriver());
    }
    $actual_title = $page_title_element->getText();
    $this->assertSame($expected_title, $actual_title, 'The page title is not the same as expected.');
  }

  /**
   * Asserts that the expected entities exist.
   */
  protected function assertEntities() {
    foreach ($this->getExpectedEntities() as $entity_type_id => $expected_entity_labels) {
      if ($storage = $this->getEntityStorage($entity_type_id)) {
        $entities = $storage->loadMultiple();
        $actual_labels = array_reduce($entities, function ($carry, EntityInterface $entity) {
          $carry[$entity->id()] = (string) $entity->label();
          return $carry;
        });

        // When all of the given entity IDs are numbers, then only the existence
        // of the given labels is checked.
        // @see ::getExpectedEntities()
        if (count(array_filter(array_keys($expected_entity_labels), 'is_int')) === count($expected_entity_labels)) {
          sort($expected_entity_labels);
          $expected_entity_labels = array_values($expected_entity_labels);
          sort($actual_labels);
          $actual_labels = array_values($actual_labels);
        }

        $this->assertEquals(asort($expected_entity_labels), asort($actual_labels), sprintf('The expected %s entities are not matching the actual ones.', $entity_type_id));
      }
      else {
        $this->fail(sprintf('The expected %s entity type is missing.', $entity_type_id));
      }
    }
  }

  /**
   * Returns the specified entity's storage when the entity definition exists.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface|null
   *   The entity storage, or NULL if it does not exist.
   */
  protected function getEntityStorage(string $entity_type_id) {
    $entity_type_manager = $this->container->get('entity_type.manager');
    assert($entity_type_manager instanceof EntityTypeManagerInterface);

    try {
      $storage = $entity_type_manager->getStorage($entity_type_id);
    }
    catch (PluginNotFoundException $e) {
      // The entity type does not exist.
      return NULL;
    }

    return $storage;
  }

  /**
   * Sets the type of the node migration.
   *
   * @param bool $classic_node_migration
   *   Whether nodes should be migrated with the 'classic' way. If this is
   *   FALSE, and the current Drupal instance has the 'complete' migration, then
   *   the complete node migration will be used.
   */
  protected function setClassicNodeMigration(bool $classic_node_migration) {
    $current_method = Settings::get('migrate_node_migrate_type_classic', FALSE);

    if ($current_method !== $classic_node_migration) {
      $settings['settings']['migrate_node_migrate_type_classic'] = (object) [
        'value' => $classic_node_migration,
        'required' => TRUE,
      ];
      $this->writeSettings($settings);
    }
  }

}
