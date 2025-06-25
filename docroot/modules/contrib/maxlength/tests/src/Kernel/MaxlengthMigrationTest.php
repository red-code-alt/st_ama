<?php

namespace Drupal\Tests\maxlength\Kernel;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Tests D7 maxlength migration plugin.
 *
 * @group maxlength
 */
class MaxlengthMigrationTest extends MigrateDrupal7TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'link',
    'maxlength',
    'node',
    'text',
    'comment',
    'filter',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getFixtureFilePath() {
    return implode(DIRECTORY_SEPARATOR, [
      \Drupal::service('extension.list.module')->getPath('maxlength'),
      'tests',
      'fixtures',
      'drupal7.php',
    ]);
  }

  /**
   * Asserts that maxlength configuration migrated.
   */
  public function testMaxlengthMigration() {
    $this->executeMigrations(['d7_field']);
    $this->executeMigrations([
      'd7_node_type',
      'd7_comment_type',
    ]);
    $this->executeMigration('d7_filter_format');
    $this->executeMigrations(['d7_field_instance']);
    $this->executeMigrations(['d7_field_instance_widget_settings']);
    $this->executeMigrations(['d7_maxlength_title_settings']);
    // Testcases for maxlength config migration of article fields.
    $article_default_form = EntityFormDisplay::load('node.article.default');
    $field_test_text_component = $article_default_form->getComponent('field_test_text');
    $this->assertEquals([
      'weight' => 2,
      'type' => 'string_textfield',
      'settings' => [
        'size' => 60,
        'placeholder' => '',
      ],
      'third_party_settings' => [
        'maxlength' => [
          'maxlength_js' => 255,
          'maxlength_js_label' => 'Content limited to @limit characters, remaining: <strong>@remaining</strong>',
        ],
      ],
      'region' => 'content',
    ], $field_test_text_component);
    $field_test_text_long_component = $article_default_form->getComponent('field_test_text_long');
    $this->assertEquals([
      'weight' => 3,
      'type' => 'string_textarea',
      'settings' => [
        'rows' => 5,
        'placeholder' => '',
      ],
      'third_party_settings' => [
        'maxlength' => [
          'maxlength_js' => 47,
          'maxlength_js_enforce' => TRUE,
          'maxlength_js_truncate_html' => TRUE,
          'maxlength_js_label' => 'Content limited to @limit characters, remaining: <strong>@remaining</strong>',
        ],
      ],
      'region' => 'content',
    ], $field_test_text_long_component);
    $field_test_text__long_summary_component = $article_default_form->getComponent('field_test_text_long_summary');
    $this->assertEquals([
      'weight' => 4,
      'type' => 'text_textarea_with_summary',
      'settings' => [
        'rows' => 9,
        'summary_rows' => 3,
        'placeholder' => '',
        'show_summary' => FALSE,
      ],
      'third_party_settings' => [
        'maxlength' => [
          'maxlength_js_summary' => 23,
          'maxlength_js_label_summary' => 'Content limited to @limit characters, remaining: <strong>@remaining</strong>',
          'maxlength_js' => 44,
          'maxlength_js_enforce' => TRUE,
          'maxlength_js_truncate_html' => FALSE,
          'maxlength_js_label' => 'Content limited to @limit characters, remaining: <strong>@remaining</strong>',
        ],
      ],
      'region' => 'content',
    ], $field_test_text__long_summary_component);
    $field_test_link = $article_default_form->getComponent('field_test_link');
    $this->assertEquals([
      'weight' => 5,
      'type' => 'link_default',
      'settings' => [
        'placeholder_url' => '',
        'placeholder_title' => '',
      ],
      'third_party_settings' => [
        'maxlength' => [
          'maxlength_js' => 128,
        ],
      ],
      'region' => 'content',
    ], $field_test_link);
    // Testcase for maxlength config migration of article comment fields.
    $comment_article_default_form = EntityFormDisplay::load('comment.comment_node_article.default');
    $field_comment_4_component = $comment_article_default_form->getComponent('field_comment_4');
    $this->assertEquals([
      'weight' => 4,
      'type' => 'string_textfield',
      'settings' => [
        'size' => 60,
        'placeholder' => '',
      ],
      'third_party_settings' => [
        'maxlength' => [
          'maxlength_js' => 176,
          'maxlength_js_label' => 'Content limited to @limit characters, remaining: <strong>@remaining</strong>',
        ],
      ],
      'region' => 'content',
    ], $field_comment_4_component);
    // Testcases for maxlength config migration of article title.
    $title_article_component = $article_default_form->getComponent('title');
    $this->assertEquals([
      'maxlength' => [
        'maxlength_js' => 71,
        'maxlength_js_label' => 's:83:"Content_custom limited to @limit characters, remaining: <strong>@remaining</strong>";',
      ],
    ], $title_article_component['third_party_settings']);
    // Testcases for maxlength config migration of page title.
    $page_default_form = EntityFormDisplay::load('node.page.default');
    $title_page_component = $page_default_form->getComponent('title');
    $this->assertEquals([
      'maxlength' => [
        'maxlength_js' => 55,
        'maxlength_js_label' => 's:76:"Content limited to @limit characters, remaining: <strong>@remaining</strong>";',
      ],
    ], $title_page_component['third_party_settings']);
  }

}
