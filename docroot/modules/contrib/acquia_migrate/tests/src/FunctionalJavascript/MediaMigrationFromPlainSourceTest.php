<?php

namespace Drupal\Tests\acquia_migrate\FunctionalJavascript;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\Core\Url;

/**
 * Tests media migration from plain image and file fields.
 *
 * For the explanation why we use WebDriver test for testing the Media migration
 * integration, check the class comment of MigrateJsUiTrait.
 *
 * @requires function Drupal\media_migration\MediaMigration::getEmbedTokenDestinationFilterPlugin
 * @group acquia_migrate
 * @group acquia_migrate__contrib
 */
class MediaMigrationFromPlainSourceTest extends MediaMigrationPlainTestBase {

  /**
   * {@inheritdoc}
   */
  protected function getFixtureFilePath() {
    return drupal_get_path('module', 'media_migration') . '/tests/fixtures/drupal7_nomedia.php';
  }

  /**
   * {@inheritdoc}
   */
  protected $profile = 'standard';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'acquia_migrate',
    'linkit',
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
  }

  /**
   * Tests the migration of files and image fields to media.
   */
  public function testPlainToMediaMigration() {
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

    $this->submitMigrationContentSelectionScreen();
    $this->visitMigrationDashboard();
    $this->assertInitialImport(TRUE, 80, 80, 80);
    // Run every migrations.
    $this->runAllMigrations();
    // Have to reset all the static caches after migration to ensure entities
    // are loadable.
    $this->resetAll();
    // Leave the dashboard.
    $this->drupalGet(Url::fromRoute('system.admin_content'));

    // Check configurations.
    $this->assertMediaFieldsAllowedTypes('node', 'article', 'field_image', ['image']);
    $this->assertMediaFieldsAllowedTypes('node', 'article', 'field_image_multi', ['image']);
    $this->assertMediaFieldsAllowedTypes('node', 'article', 'field_file', [
      'audio',
      'document',
      'image',
      'video',
    ]);
    $this->assertMediaFieldsAllowedTypes('node', 'article', 'field_file_multi', [
      'audio',
      'document',
      'image',
      'video',
    ]);

    // Check media source field config entities.
    $this->assertNonMediaToMediaImageMediaBundleSourceFieldProperties();
    $this->assertNonMediaToMediaDocumentMediaBundleSourceFieldProperties();

    // Test view modes.
    $this->assertMediaAudioDisplayModes();
    $this->assertMediaDocumentDisplayModes();
    $this->assertMediaImageDisplayModes();
    $this->assertMediaVideoDisplayModes();

    // Check media entities.
    $this->assertNonMediaToMedia1FieldValues();
    $this->assertNonMediaToMedia2FieldValues();
    $this->assertNonMediaToMedia3FieldValues();
    $this->assertNonMediaToMedia6FieldValues();
    $this->assertNonMediaToMedia7FieldValues();
    $this->assertNonMediaToMedia8FieldValues();
    $this->assertNonMediaToMedia9FieldValues();
    $this->assertNonMediaToMedia10FieldValues();
    $this->assertNonMediaToMedia11FieldValues();
    $this->assertNonMediaToMedia12FieldValues();

    // Check the nodes.
    $this->assertNonMediaToMediaNode1FieldValues();
    $this->assertNonMediaToMediaNode2FieldValues();

    $this->assertFilterFormats();
  }

}
