<?php

namespace Drupal\Tests\acquia_migrate\FunctionalJavascript;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\Core\Url;

/**
 * Tests media migration.
 *
 * For the explanation why we use WebDriver test for testing the Media migration
 * integration, check the class comment of MigrateJsUiTrait.
 *
 * @requires function Drupal\media_migration\MediaMigration::getEmbedTokenDestinationFilterPlugin
 * @group acquia_migrate
 * @group acquia_migrate__contrib
 */
class MediaMigrationTest extends MediaMigrationTestBase {

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
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->setupMigrationConnection();

    // Write public and private file location into settings.php.
    $public_base_path = realpath(DRUPAL_ROOT . '/' . drupal_get_path('module', 'media_migration') . '/tests/fixtures');
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

    // Delete preexisting media types.
    $media_types = $this->container->get('entity_type.manager')
      ->getStorage('media_type')
      ->loadMultiple();
    foreach ($media_types as $media_type) {
      $media_type->delete();
    }

    // Uninstall path module.
    $module_installer = $this->container->get('module_installer');
    assert($module_installer instanceof ModuleInstallerInterface);
    $module_installer->uninstall(['path']);

    // Log in as user 1. Migrations in the UI can only be performed as user 1.
    $this->drupalLogin($this->rootUser);

    // Delete 'article' content type. The destination Drupal 8|9 instance's
    // article content type will contain an image type field with the same name
    // that we have in the source Drupal 7 database. Media Migration tries to
    // change the field type of file and image fields to media reference, but
    // since the type of an existing field cannot be changed, this is the only
    // way to test the migration of media until we solve this issue.
    $entity_type_manager = $this->container->get('entity_type.manager');
    assert($entity_type_manager instanceof EntityTypeManagerInterface);
    $node_type_storage = $entity_type_manager->getStorage('node_type');
    if ($article_node_type = $node_type_storage->load('article')) {
      $article_node_type->delete();
    }
  }

  /**
   * Tests the migration of media entities and of a node with embed media.
   *
   * @dataProvider providerTestMediaMigration
   */
  public function testMediaMigration(bool $with_extra_file, bool $with_long_source_field_names) {
    // Add a plain image if needed.
    if ($with_extra_file) {
      $this->sourceDatabase->insert('file_managed')
        ->fields([
          'fid' => 101,
          'uid' => 1,
          'filename' => 'another-yellow.webp',
          'uri' => 'public://yellow_0.webp',
          'filemime' => 'image/webp',
          'filesize' => 3238,
          'status' => 1,
          'timestamp' => 1600000000,
          'type' => 'undefined',
        ])
        ->execute();
    }

    if ($with_long_source_field_names) {
      $module_installer = $this->container->get('module_installer');
      assert($module_installer instanceof ModuleInstallerInterface);
      $this->assertTrue(
        $module_installer->install(['media_migration_test_long_field_name'], TRUE)
      );
    }

    $this->submitMigrationContentSelectionScreen();
    $this->visitMigrationDashboard();

    $expected_initial_imports = $this->expectedInitialImports;
    $additional_initial_imports = $with_extra_file
      ? $this->extraFileInitialImports
      : [];

    $this->assertStrictInitialImport($expected_initial_imports + $additional_initial_imports);

    // Check configurations.
    $this->assertArticleImageFieldsAllowedTypes();
    $this->assertArticleMediaFieldsAllowedTypes();

    // Test view modes.
    $this->assertMediaAudioDisplayModes();
    $this->assertMediaDocumentDisplayModes();
    $this->assertMediaImageDisplayModes(TRUE);
    $this->assertMediaRemoteVideoDisplayModes();
    $this->assertMediaVideoDisplayModes();

    // Run every migrations.
    $this->runAllMigrations();

    // Have to reset all the static caches after migration to ensure entities
    // are loadable.
    $this->resetAll();

    // Leave the dashboard.
    $this->drupalGet(Url::fromRoute('system.admin_content'));

    // Check the migrated media entities.
    $this->assertMedia1FieldValues();
    $this->assertMedia2FieldValues();
    $this->assertMedia3FieldValues();
    $this->assertMedia4FieldValues();
    $this->assertMedia5FieldValues();
    $this->assertMedia6FieldValues();
    $this->assertMedia7FieldValues();
    $this->assertMedia8FieldValues();
    $this->assertMedia9FieldValues();
    $this->assertMedia10FieldValues();
    $this->assertMedia11FieldValues();
    $this->assertMedia12FieldValues();
    if ($with_extra_file) {
      $this->assertMedia101FieldValues();
    }

    $expected_node1_embed_attributes = [
      0 => [
        'data-entity-type' => 'media',
        'data-entity-uuid' => TRUE,
        'data-view-mode' => 'wysiwyg',
        'alt' => 'Different alternative text about blue.png in the test article',
        'title' => 'Different title copy for blue.png in the test article',
        'data-align' => 'center',
      ],
      [
        'data-entity-type' => 'media',
        'data-entity-uuid' => TRUE,
        'data-view-mode' => 'default',
        'alt' => 'A yellow image',
        'title' => 'This is a yellow image',
      ],
    ];
    $this->assertNode1FieldValues($expected_node1_embed_attributes);

    $this->assertFilterFormats();
  }

  /**
   * Data provider for ::testMediaMigration.
   *
   * @return array[]
   *   The test cases.
   */
  public function providerTestMediaMigration(): array {
    return [
      'No complications' => [
        'Add extra file' => FALSE,
        'Mock long source field names' => FALSE,
      ],
      'An extra file' => [
        'Add extra file' => TRUE,
        'Mock long source field names' => FALSE,
      ],
      'Long source field names' => [
        'Add extra file' => FALSE,
        'Mock long source field names' => TRUE,
      ],
      'Long source field names with an extra file' => [
        'Add extra file' => TRUE,
        'Mock long source field names' => TRUE,
      ],
    ];
  }

  /**
   * Tests media remote_video's default form and view mode configuration.
   */
  protected function assertMediaRemoteVideoDisplayModes() {
    $source_field_name = $this->getFinalSourceFieldName('remote_video');
    $entity_form_display = $this->container->get('entity_type.manager')
      ->getStorage('entity_form_display')
      ->load(implode('.', [
        'media',
        'remote_video',
        'default',
      ]));
    $this->assertEquals([
      'status' => TRUE,
      'id' => 'media.remote_video.default',
      'targetEntityType' => 'media',
      'bundle' => 'remote_video',
      'mode' => 'default',
      'content' => [
        'created' => [
          'type' => 'datetime_timestamp',
          'weight' => 10,
          'region' => 'content',
          'settings' => [],
          'third_party_settings' => [],
        ],
        $source_field_name => [
          'type' => 'string_textfield',
          'weight' => 0,
          'settings' => [
            'size' => 60,
            'placeholder' => '',
          ],
          'third_party_settings' => [],
          'region' => 'content',
        ],
        'name' => [
          'type' => 'string_textfield',
          'weight' => -5,
          'settings' => [
            'size' => 60,
            'placeholder' => '',
          ],
          'third_party_settings' => [],
          'region' => 'content',
        ],
        'status' => [
          'type' => 'boolean_checkbox',
          'weight' => 100,
          'settings' => [
            'display_label' => TRUE,
          ],
          'third_party_settings' => [],
          'region' => 'content',
        ],
        'uid' => [
          'type' => 'entity_reference_autocomplete',
          'weight' => 5,
          'settings' => [
            'match_operator' => 'CONTAINS',
            'size' => 60,
            'placeholder' => '',
            'match_limit' => 10,
          ],
          'third_party_settings' => [],
          'region' => 'content',
        ],
      ],
      'hidden' => [],
    ], $this->getImportantEntityProperties($entity_form_display));

    $entity_view_display = $this->container->get('entity_type.manager')
      ->getStorage('entity_view_display')
      ->load(implode('.', [
        'media',
        'remote_video',
        'default',
      ]));
    $this->assertEquals([
      'status' => TRUE,
      'id' => 'media.remote_video.default',
      'targetEntityType' => 'media',
      'bundle' => 'remote_video',
      'mode' => 'default',
      'content' => [
        $source_field_name => [
          'type' => 'oembed',
          'weight' => 0,
          'settings' => [
            'max_width' => 0,
            'max_height' => 0,
          ],
          'third_party_settings' => [],
          'region' => 'content',
          'label' => 'visually_hidden',
        ],
      ],
      'hidden' => [
        'created' => TRUE,
        'name' => TRUE,
        'thumbnail' => TRUE,
        'uid' => TRUE,
      ],
    ], $this->getImportantEntityProperties($entity_view_display));
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
    'd7_comment_entity_display:page' => [1, 1, 1, 0, 0],
    'd7_comment_entity_form_display:article' => [1, 1, 1, 0, 0],
    'd7_comment_entity_form_display:page' => [1, 1, 1, 0, 0],
    'd7_comment_entity_form_display_subject:article' => [1, 1, 1, 0, 0],
    'd7_comment_entity_form_display_subject:page' => [1, 1, 1, 0, 0],
    'd7_comment_field:article' => [1, 1, 1, 0, 0],
    'd7_comment_field:page' => [1, 1, 1, 0, 0],
    'd7_comment_field_instance:article' => [1, 1, 1, 0, 0],
    'd7_comment_field_instance:page' => [1, 1, 1, 0, 0],
    'd7_comment_type:article' => [1, 1, 1, 0, 0],
    'd7_comment_type:page' => [1, 1, 1, 0, 0],
    'd7_field:comment' => [1, 1, 1, 0, 0],
    'd7_field:file' => [3, 3, 1, 0, 0],
    'd7_field:node' => [3, 3, 3, 0, 0],
    'd7_field_formatter_settings:comment:article' => [1, 1, 1, 0, 0],
    'd7_field_formatter_settings:comment:page' => [1, 1, 1, 0, 0],
    'd7_field_formatter_settings:file:image' => [9, 9, 1, 0, 0],
    'd7_field_formatter_settings:node:article' => [5, 5, 5, 0, 0],
    'd7_field_formatter_settings:node:page' => [2, 2, 2, 0, 0],
    'd7_field_instance:comment:article' => [1, 1, 1, 0, 0],
    'd7_field_instance:comment:page' => [1, 1, 1, 0, 0],
    'd7_field_instance:file:image' => [3, 3, 1, 0, 0],
    'd7_field_instance:node:article' => [3, 3, 3, 0, 0],
    'd7_field_instance:node:page' => [1, 1, 1, 0, 0],
    'd7_field_instance_widget_settings:comment:article' => [1, 1, 1, 0, 0],
    'd7_field_instance_widget_settings:comment:page' => [1, 1, 1, 0, 0],
    'd7_field_instance_widget_settings:file:image' => [3, 3, 1, 0, 0],
    'd7_field_instance_widget_settings:node:article' => [3, 3, 3, 0, 0],
    'd7_field_instance_widget_settings:node:page' => [1, 1, 1, 0, 0],
    'd7_file_entity_formatter:audio' => [5, 5, 5, 0, 0],
    'd7_file_entity_formatter:document' => [5, 5, 5, 0, 0],
    'd7_file_entity_formatter:image' => [5, 5, 5, 0, 0],
    'd7_file_entity_formatter:remote_video' => [5, 5, 5, 0, 0],
    'd7_file_entity_formatter:video' => [5, 5, 5, 0, 0],
    'd7_file_entity_source_field:audio' => [1, 1, 1, 0, 0],
    'd7_file_entity_source_field:document' => [1, 1, 1, 0, 0],
    'd7_file_entity_source_field:image' => [1, 1, 1, 0, 0],
    'd7_file_entity_source_field:remote_video' => [1, 1, 1, 0, 0],
    'd7_file_entity_source_field:video' => [1, 1, 1, 0, 0],
    'd7_file_entity_source_field_config:audio' => [1, 1, 1, 0, 0],
    'd7_file_entity_source_field_config:document' => [1, 1, 1, 0, 0],
    'd7_file_entity_source_field_config:image' => [1, 1, 1, 0, 0],
    'd7_file_entity_source_field_config:remote_video' => [1, 1, 1, 0, 0],
    'd7_file_entity_source_field_config:video' => [1, 1, 1, 0, 0],
    'd7_file_entity_type:audio' => [1, 1, 1, 0, 0],
    'd7_file_entity_type:document' => [1, 1, 1, 0, 0],
    'd7_file_entity_type:image' => [1, 1, 1, 0, 0],
    'd7_file_entity_type:remote_video' => [1, 1, 1, 0, 0],
    'd7_file_entity_type:video' => [1, 1, 1, 0, 0],
    'd7_file_entity_widget:audio' => [1, 1, 1, 0, 0],
    'd7_file_entity_widget:document' => [1, 1, 1, 0, 0],
    'd7_file_entity_widget:image' => [1, 1, 1, 0, 0],
    'd7_file_entity_widget:remote_video' => [1, 1, 1, 0, 0],
    'd7_file_entity_widget:video' => [1, 1, 1, 0, 0],
    'd7_filter_format' => [3, 3, 3, 0, 0],
    'd7_media_view_modes' => [7, 7, 7, 0, 0],
    'd7_menu' => [4, 4, 4, 0, 0],
    'd7_node_type:article' => [1, 1, 1, 0, 0],
    'd7_node_type:page' => [1, 1, 1, 0, 0],
    'd7_shortcut_set' => [1, 1, 1, 0, 0],
    'd7_taxonomy_vocabulary:tags' => [1, 1, 1, 0, 0],
    'd7_user_role' => [4, 4, 4, 0, 0],
    'd7_view_modes:comment' => [1, 1, 1, 0, 0],
    'd7_view_modes:file' => [4, 4, 4, 0, 0],
    'd7_view_modes:node' => [2, 2, 2, 0, 0],
    'user_picture_entity_display' => [1, 1, 1, 0, 0],
    'user_picture_entity_form_display' => [1, 1, 1, 0, 0],
    'user_picture_field' => [1, 1, 1, 0, 0],
    'user_picture_field_instance' => [1, 1, 1, 0, 0],
  ];

  /**
   * Additional initial imports with an extra non-media file.
   *
   * @var int[][]
   */
  protected $extraFileInitialImports = [
    'd7_file_plain_formatter:image' => [5, 5, 5, 0, 0],
    'd7_file_plain_source_field_config:image' => [1, 1, 1, 0, 0],
    'd7_file_plain_source_field:image' => [1, 1, 1, 0, 0],
    'd7_file_plain_type:image' => [1, 1, 1, 0, 0],
    'd7_file_plain_widget:image' => [1, 1, 1, 0, 0],
  ];

}
