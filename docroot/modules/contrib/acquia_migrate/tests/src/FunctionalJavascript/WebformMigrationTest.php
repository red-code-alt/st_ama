<?php

namespace Drupal\Tests\acquia_migrate\FunctionalJavascript;

use Drupal\Core\Url;

/**
 * Tests webform form and webform submission migrations.
 *
 * For the explanation why we use WebDriver test for testing the Webform
 * migration integration, check the class comment of MigrateJsUiTrait.
 *
 * @requires function Drupal\Tests\acquia_migrate\FunctionalJavascript\WebformMigrationTest::setupMigrationConnection
 * @group acquia_migrate
 * @group acquia_migrate__contrib
 */
class WebformMigrationTest extends WebformTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'standard';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'acquia_migrate',
    'webform_migrate',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getFixtureFilePath() {
    return drupal_get_path('module', 'webform_migrate') . '/tests/fixtures/drupal7_webform.php';
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->setupMigrationConnection();

    // Write public and private file location into settings.php.
    $public_base_path = realpath(DRUPAL_ROOT . '/' . drupal_get_path('module', 'webform_migrate') . '/tests/fixtures/files');
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
   * Tests webform migration.
   */
  public function testWebformMigration() {
    $this->assertInitialImport(TRUE, 31, 31, 31);

    // Run only those migrations that are required.
    $this->runMigrations([
      'User accounts',
      'Public files',
    ]);
    $this->runSingleMigration('Webform submissions (including webforms)');

    // Have to reset all the static caches after migration to ensure entities
    // are loadable.
    $this->resetAll();

    // Leave the dashboard to get potential error messages (notices, warnings,
    // errors).
    $this->drupalGet(Url::fromRoute('system.admin_content'));
    $this->assertNoWarningOrErrorMessages();

    // Check the forms.
    $this->assertWebform3Values();
    $this->assertWebform4Values();
    $this->assertWebform5Values();

    // Check the form submissions. There must be 10.
    $this->assertWebformSubmission1Values();
    $this->assertWebformSubmission2Values();
    $this->assertWebformSubmission3Values();
    $this->assertWebformSubmission4Values();
    $this->assertWebformSubmission5Values();
    $this->assertWebformSubmission6Values();
    $this->assertWebformSubmission7Values();
    $this->assertWebformSubmission8Values();
    $this->assertWebformSubmission9Values();
    $this->assertWebformSubmission10Values();
  }

}
