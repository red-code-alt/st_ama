<?php

namespace Drupal\Tests\acquia_migrate\FunctionalJavascript;

use Drupal\Core\Url;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\menu_link_content\MenuLinkContentInterface;
use Drupal\node\NodeInterface;
use Drupal\node\NodeStorageInterface;
use Drupal\Tests\acquia_migrate\Traits\MigrateDatabaseFixtureTrait;
use Drupal\Tests\acquia_migrate\Traits\MigrateJsUiTrait;

/**
 * Tests single-language migration from the core Drupal 7 database fixture.
 *
 * @group acquia_migrate
 * @group acquia_migrate__core
 */
class StandardMigrationTest extends WebDriverTestBase {

  use MigrateDatabaseFixtureTrait;
  use MigrateJsUiTrait;

  /**
   * Menu link content properties to skip while checking the migrated entities.
   *
   * @const string[]
   */
  const MENU_LINK_CONTENT_PROPS_TO_IGNORE = [
    'bundle',
    'changed',
    'content_translation_created',
    'content_translation_outdated',
    'content_translation_source',
    'content_translation_status',
    'content_translation_uid',
    'default_langcode',
    'langcode',
    'revision_created',
    'revision_default',
    'revision_id',
    'revision_log_message',
    'revision_translation_affected',
    'revision_user',
    'uuid',
  ];

  /**
   * {@inheritdoc}
   */
  protected $profile = 'standard';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'acquia_migrate',
    'telephone',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->setupMigrationConnection();

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
  }

  /**
   * Tests the migration.
   */
  public function testStandardMigration() {
    $this->submitMigrationContentSelectionScreen();
    $this->visitMigrationDashboard();

    $this->assertStrictInitialImport($this->expectedInitialImports);

    // Run every migrations.
    $this->runAllMigrations();

    // Have to reset all the static caches after migration to ensure entities
    // are loadable.
    $this->resetAll();

    // Leave the dashboard to get potential error messages (notices, warnings,
    // errors).
    $this->drupalGet(Url::fromRoute('system.admin_content'));
    $this->assertNoWarningOrErrorMessages();

    // Test that text fields with conflicting text processing settings are also
    // migrated.
    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');
    assert($node_storage instanceof NodeStorageInterface);
    $node12 = $node_storage->load(12);
    if ($node12 instanceof NodeInterface) {
      $node12_array = $node12->toArray();
      unset($node12_array['uuid']);
      unset($node12_array['path']);

      $expected = [
        'nid' => [['value' => 12]],
        'vid' => [['value' => 19]],
        'langcode' => [['value' => 'und']],
        'type' => [['target_id' => 'article']],
        'revision_timestamp' => [['value' => 1620825323]],
        'revision_uid' => [['target_id' => 1]],
        'revision_log' => [],
        'status' => [['value' => 1]],
        'uid' => [['target_id' => 3]],
        'title' => [
          [
            'value' => 'Article with content in text fields with conflicting text processing settings',
          ],
        ],
        'created' => [['value' => 1620825323]],
        'changed' => [['value' => 1620825323]],
        'promote' => [['value' => 1]],
        'sticky' => [['value' => 0]],
        'default_langcode' => [['value' => 1]],
        'revision_default' => [['value' => 1]],
        'revision_translation_affected' => [['value' => 1]],
        'body' => [],
        'comment_node_article' => [
          [
            'status' => 1,
            'cid' => 0,
            'last_comment_timestamp' => 1620825323,
            'last_comment_name' => NULL,
            'last_comment_uid' => 3,
            'comment_count' => 0,
          ],
        ],
        'field_image' => [],
        'field_link' => [],
        'field_node_reference' => [],
        'field_reference' => [],
        'field_reference_2' => [],
        'field_tags' => [],
        'field_text_filtered' => [],
        'field_text_long_filtered' => [],
        'field_text_long_plain' => [],
        'field_text_long_plain_filtered' => [
          [
            'value' => 'Text long plain and filtered – here it is <em>plain</em>',
            'format' => 'plain_text',
          ],
        ],
        'field_text_plain' => [],
        'field_text_plain_filtered' => [
          [
            'value' => 'Text plain and filtered – here it is <em>plain</em>',
            'format' => 'plain_text',
          ],
        ],
        'field_text_sum_filtered' => [],
        'field_text_sum_plain' => [],
        'field_text_sum_plain_filtered' => [
          [
            'value' => 'Text summary plain and filtered – here it is <em>plain</em>',
            'summary' => '',
            'format' => 'plain_text',
          ],
        ],
        'field_user_reference' => [],
        'field_vocab_fixed' => [],
        'field_vocab_localize' => [],
        'field_vocab_translate' => [],
        'field_checkbox' => [],
      ];
      if (\Drupal::moduleHandler()->moduleExists('content_translation')) {
        $expected['content_translation_source'] = [];
        $expected['content_translation_outdated'] = [['value' => 0]];
      }
      $this->assertEquals($expected, $node12_array);
    }

    $node13 = $node_storage->load(13);
    if ($node13 instanceof NodeInterface) {
      $node13_array = $node13->toArray();
      unset($node13_array['uuid']);
      unset($node13_array['path']);

      $expected = [
        'nid' => [['value' => 13]],
        'vid' => [['value' => 20]],
        'langcode' => [['value' => 'und']],
        'type' => [['target_id' => 'page']],
        'revision_timestamp' => [['value' => 1620825477]],
        'revision_uid' => [['target_id' => 1]],
        'revision_log' => [],
        'status' => [['value' => 1]],
        'uid' => [['target_id' => 2]],
        'title' => [
          [
            'value' => 'Basic page with content in text fields with conflicting text processing settings',
          ],
        ],
        'created' => [['value' => 1620825477]],
        'changed' => [['value' => 1620825477]],
        'promote' => [['value' => 0]],
        'sticky' => [['value' => 0]],
        'default_langcode' => [['value' => 1]],
        'revision_default' => [['value' => 1]],
        'revision_translation_affected' => [['value' => 1]],
        'body' => [],
        'comment_node_page' => [
          [
            'status' => 1,
            'cid' => 0,
            'last_comment_timestamp' => 1620825477,
            'last_comment_name' => NULL,
            'last_comment_uid' => 2,
            'comment_count' => 0,
          ],
        ],
        'field_text_filtered' => [],
        'field_text_long_filtered' => [],
        'field_text_long_plain' => [],
        'field_text_long_plain_filtered' => [
          [
            'value' => 'Text long plain and filtered – here it is <em>filtered</em> (uses full_html)',
            'format' => 'full_html',
          ],
        ],
        'field_text_plain' => [],
        'field_text_plain_filtered' => [
          [
            'value' => 'Text plain and filtered – here it is <em>filtered</em> (uses custom_text_format)',
            'format' => 'custom_text_format',
          ],
        ],
        'field_text_sum_filtered' => [],
        'field_text_sum_plain' => [],
        'field_text_sum_plain_filtered' => [
          [
            'value' => 'Text summary plain and filtered – here it is <em>filtered</em> (uses plain_text)',
            'summary' => '',
            'format' => 'plain_text',
          ],
        ],
      ];
      if (\Drupal::moduleHandler()->moduleExists('content_translation')) {
        $expected['content_translation_source'] = [];
        $expected['content_translation_outdated'] = [['value' => 0]];
      }
      $this->assertEquals($expected, $node13_array);
    }

    $this->assertCount(11, MenuLinkContent::loadMultiple());

    // Check some menu links.
    $menu_link_content_storage = \Drupal::entityTypeManager()->getStorage('menu_link_content');

    // A custom menu link pointing to an external resource: 'http://bing.com'.
    $menu_link_469 = $menu_link_content_storage->load(469);
    $this->assertInstanceOf(MenuLinkContentInterface::class, $menu_link_469);
    $this->assertEquals(
      [
        'id' => [['value' => '469']],
        'parent' => [],
        'enabled' => [['value' => '1']],
        'title' => [['value' => 'Bing']],
        'description' => [['value' => 'Bing']],
        'menu_name' => [['value' => 'menu-test-menu']],
        'link' => [
          [
            'uri' => 'http://bing.com',
            'title' => NULL,
            'options' => [
              'attributes' => [
                'title' => 'Bing',
              ],
            ],
          ],
        ],
        'external' => [['value' => '1']],
        'rediscover' => [['value' => '0']],
        'weight' => [['value' => '0']],
        'expanded' => [['value' => '0']],
      ],
      array_diff_key(
        $menu_link_469->toArray(),
        array_combine(
          self::MENU_LINK_CONTENT_PROPS_TO_IGNORE,
          self::MENU_LINK_CONTENT_PROPS_TO_IGNORE
        )
      )
    );

    // A custom menu link pointing to an external resource: 'http://google.com'.
    // Its parent should be the previous link ('http://bing.com').
    $menu_link_467 = $menu_link_content_storage->load(467);
    $this->assertInstanceOf(MenuLinkContentInterface::class, $menu_link_467);
    $this->assertEquals(
      [
        'id' => [['value' => '467']],
        'parent' => [
          [
            'value' => 'menu_link_content:' . $menu_link_469->uuid(),
          ],
        ],
        'enabled' => [['value' => '1']],
        'title' => [['value' => 'Google']],
        'description' => [['value' => 'Google']],
        'menu_name' => [['value' => 'menu-test-menu']],
        'link' => [
          [
            'uri' => 'http://google.com',
            'title' => NULL,
            'options' => [
              'attributes' => [
                'title' => 'Google',
              ],
            ],
          ],
        ],
        'external' => [['value' => '1']],
        'rediscover' => [['value' => '0']],
        'weight' => [['value' => '0']],
        'expanded' => [['value' => '0']],
      ],
      array_diff_key(
        $menu_link_467->toArray(),
        array_combine(
          self::MENU_LINK_CONTENT_PROPS_TO_IGNORE,
          self::MENU_LINK_CONTENT_PROPS_TO_IGNORE
        )
      )
    );

    // A customized, module-provided menu link pointing to '/admin/content'.
    // Its parent should be a yaml-provided menu link plugin with ID
    // 'system.admin_structure'.
    $menu_link_478 = $menu_link_content_storage->load(478);
    $this->assertInstanceOf(MenuLinkContentInterface::class, $menu_link_478);
    $this->assertEquals(
      [
        'id' => [['value' => '478']],
        'parent' => [
          [
            'value' => 'system.admin_structure',
          ],
        ],
        'enabled' => [['value' => '1']],
        'title' => [['value' => 'custom link test']],
        'description' => [],
        'menu_name' => [['value' => 'admin']],
        'link' => [
          [
            'uri' => 'internal:/admin/content',
            'title' => NULL,
            'options' => [
              'attributes' => [
                'title' => '',
              ],
            ],
          ],
        ],
        'external' => [['value' => '0']],
        'rediscover' => [['value' => '1']],
        'weight' => [['value' => '0']],
        'expanded' => [['value' => '0']],
      ],
      array_diff_key(
        $menu_link_478->toArray(),
        array_combine(
          self::MENU_LINK_CONTENT_PROPS_TO_IGNORE,
          self::MENU_LINK_CONTENT_PROPS_TO_IGNORE
        )
      )
    );

    // A menu link referring node #2. Surprisingly it should be in the admin
    // menu.
    $menu_link_484 = $menu_link_content_storage->load(484);
    $this->assertInstanceOf(MenuLinkContentInterface::class, $menu_link_484);
    $this->assertEquals(
      [
        'id' => [['value' => '484']],
        'parent' => [],
        'enabled' => [['value' => '1']],
        'title' => [['value' => 'The thing about Deep Space 9']],
        'description' => [],
        'menu_name' => [['value' => 'tools']],
        'link' => [
          [
            'uri' => 'entity:node/2',
            'title' => NULL,
            'options' => [
              'attributes' => [
                'title' => '',
              ],
            ],
          ],
        ],
        'external' => [['value' => '0']],
        'rediscover' => [['value' => '0']],
        'weight' => [['value' => '9']],
        'expanded' => [['value' => '0']],
      ],
      array_diff_key(
        $menu_link_484->toArray(),
        array_combine(
          self::MENU_LINK_CONTENT_PROPS_TO_IGNORE,
          self::MENU_LINK_CONTENT_PROPS_TO_IGNORE
        )
      )
    );

    // Menu link #485 was referencing node #3 on the source, which was the
    // Icelandic translation of node #3.
    $menu_link_485 = \Drupal::entityTypeManager()->getStorage('menu_link_content')->load(485);
    $this->assertInstanceOf(MenuLinkContentInterface::class, $menu_link_485);
    $menu_link_485_expected = [
      'id' => [['value' => '485']],
      'parent' => [],
      'enabled' => [['value' => '0']],
      'title' => [['value' => "is - The thing about Deep Space 9 (unavailable: 'node/3')"]],
      'description' => [],
      'menu_name' => [['value' => 'tools']],
      'link' => [
        [
          'uri' => 'route:<current>',
          'title' => NULL,
          'options' => [
            'attributes' => [
              'title' => '',
            ],
          ],
        ],
      ],
      'external' => [['value' => '0']],
      'rediscover' => [['value' => '0']],
      'weight' => [['value' => '10']],
      'expanded' => [['value' => '0']],
    ];
    if (\Drupal::moduleHandler()->moduleExists('content_translation')) {
      $menu_link_485_expected = [
        'enabled' => [['value' => '1']],
        'title' => [['value' => 'is - The thing about Deep Space 9']],
        'link' => [
          [
            'uri' => 'entity:node/2',
            'title' => NULL,
            'options' => [
              'attributes' => [
                'title' => '',
              ],
            ],
          ],
        ],
      ] + $menu_link_485_expected;
    }
    $this->assertEquals(
      $menu_link_485_expected,
      array_diff_key(
        $menu_link_485->toArray(),
        array_combine(
          self::MENU_LINK_CONTENT_PROPS_TO_IGNORE,
          self::MENU_LINK_CONTENT_PROPS_TO_IGNORE
        )
      )
    );
  }

  /**
   * Num of total/processed/imported/to-update/errored rows per initial migr.
   *
   * @var int[][]
   *
   * @see InitialImportAssertionTrait::assertStrictInitialImport()
   */
  protected $expectedInitialImports = [
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
  ];

}
