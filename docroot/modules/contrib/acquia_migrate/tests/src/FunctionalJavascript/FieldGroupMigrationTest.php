<?php

namespace Drupal\Tests\acquia_migrate\FunctionalJavascript;

use Drupal\Core\Url;

/**
 * Tests field group migrations.
 *
 * @requires function Drupal\Tests\acquia_migrate\FunctionalJavascript\FieldGroupMigrationTest::setupMigrationConnection
 * @group acquia_migrate
 * @group acquia_migrate__contrib
 */
class FieldGroupMigrationTest extends FieldGroupTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'standard';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'acquia_migrate',
    'field_group_migrate',
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
    $this->loadFixture(drupal_get_path('module', 'field_group_migrate') . '/tests/fixtures/drupal7.php');

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
   * Tests field group migration.
   */
  public function testFieldGroupMigration() {
    $this->assertInitialImport(TRUE, 437, 437, 424);

    // @todo Change the "page" assertions to "blog" assertions and update the
    // field_group DB fixture, because the D7 core DB fixture contains 0 page
    // nodes and is hence automatically skipped.
    // @see \Drupal\Tests\acquia_migrate\Functional\HttpApiStandardTest::expectedResourceObjectForBasicPage()
    // @see core/modules/migrate_drupal/tests/fixtures/drupal7.php
    // @see ::assertNodePageDefaultForm()
    // @see ::assertNodePageDefaultDisplay()
    // Run only those migrations that are required.
    $this->runMigrations([
      'User accounts',
      'Public files',
      'Tags taxonomy terms',
      'VocabFixed taxonomy terms',
      'VocabLocalized taxonomy terms',
      'VocabTranslate taxonomy terms',
    ]);
    $this->runMigrations([
      'Article',
    ]);

    // Have to reset all the static caches after migration to ensure entities
    // are loadable.
    $this->resetAll();

    // Leave the dashboard to get potential error messages (notices, warnings,
    // errors).
    $this->drupalGet(Url::fromRoute('system.admin_content'));
    $this->assertNoWarningOrErrorMessages();

    // Check the forms.
    $this->assertNodeArticleDefaultForm();

    // Check the view displays.
    $this->assertNodeArticleTeaserDisplay();
    $this->assertUserDefaultDisplay();
  }

}
