<?php

namespace Drupal\Tests\acquia_migrate\FunctionalJavascript;

use Drupal\Core\Url;

/**
 * Tests location migration.
 *
 * For the explanation why we use WebDriver test for testing the location
 * migration integration, check the class comment of MigrateJsUiTrait.
 *
 * @requires function \Drupal\location_migration\LocationMigration::getEntityLocationFieldBaseName
 * @group acquia_migrate
 * @group acquia_migrate__contrib
 */
class LocationMigrationTest extends LocationMigrationTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'standard';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'acquia_migrate',
    'location_migration',
    'telephone',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getFixtureFilePath() {
    return drupal_get_path('module', 'location_migration') . '/tests/fixtures/d7/drupal7_location.php';
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->setupMigrationConnection();

    // Location migration database fixture does not requires files to be
    // migrated at all, but Acquia Migrate Accelerate enforces having
    // "migrate_source_base_path" and "migrate_source_private_file_path" set up.
    $public_base_path = '/tmp';
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
   * Tests the migration of the entity locations and location fields.
   */
  public function testLocationMigration() {
    $this->submitMigrationContentSelectionScreen();
    $this->visitMigrationDashboard();

    $this->assertInitialImport(TRUE, 192, 192, 192);

    // Run every migrations.
    $this->runAllMigrations();

    // Have to reset all the static caches after migration to ensure entities
    // are loadable.
    $this->resetAll();

    // Leave the dashboard.
    $this->drupalGet(Url::fromRoute('system.admin_content'));
    $this->assertNoWarningOrErrorMessages();

    $expected_features = [
      'entity' => TRUE,
      'email' => TRUE,
      'fax' => TRUE,
      'phone' => TRUE,
      'www' => TRUE,
    ];

    // Check the migrated content entities.
    $this->assertTerm1FieldValues($expected_features);
    $this->assertUser2FieldValues($expected_features);
    $this->assertNode1FieldValues($expected_features);
    $this->assertNode2FieldValues($expected_features);
    $this->assertNode3FieldValues($expected_features);
  }

}
