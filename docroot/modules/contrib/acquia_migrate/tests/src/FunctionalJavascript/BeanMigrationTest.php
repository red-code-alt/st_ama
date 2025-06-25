<?php

namespace Drupal\Tests\acquia_migrate\FunctionalJavascript;

use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\Core\Url;

/**
 * Tests bean entity and bean block placement migrations.
 *
 * For the explanation why we use WebDriver test for testing the Webform
 * migration integration, check the class comment of MigrateJsUiTrait.
 *
 * @requires function Drupal\Tests\acquia_migrate\FunctionalJavascript\BeanMigrationTest::assertBean1
 * @group acquia_migrate
 * @group acquia_migrate__contrib
 */
class BeanMigrationTest extends BeanMigrationTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'standard';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'bartik';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'acquia_migrate',
    'bean_migrate',
  ];

  /**
   * Multilingual migration test.
   *
   * @var bool
   */
  protected $isMultilingualTest = FALSE;

  /**
   * Expected default language code of bean config entities.
   *
   * @var string
   */
  protected $expectedDefaultLanguageCode = 'en';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Write public and private file location into settings.php.
    $public_base_path = realpath(DRUPAL_ROOT . '/' . drupal_get_path('module', 'bean_migrate') . '/tests/fixtures/files');
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
  }

  /**
   * Tests bean migrations.
   *
   * @dataProvider providerTestBeanMigrations
   */
  public function testBeanMigrations(bool $multilingual, string $bean_fixture_path, array $initial_import_nums, array $migration_labels_to_verify) {
    if ($multilingual) {
      $this->isMultilingualTest = $multilingual;
      $this->expectedDefaultLanguageCode = 'is';

      $module_installer = $this->container->get('module_installer');
      assert($module_installer instanceof ModuleInstallerInterface);
      $module_installer->install([
        'config_translation',
        'content_translation',
      ]);
    }

    $this->setupMigrationConnection(implode('/', [
      drupal_get_path('module', 'bean_migrate'),
      $bean_fixture_path,
    ]));

    // Log in as user 1. Migrations in the UI can only be performed as user 1.
    $this->drupalLogin($this->rootUser);

    $this->submitMigrationContentSelectionScreen();
    $this->visitMigrationDashboard();

    // Asserting the number of the initially processed and imported rows.
    call_user_func_array([$this, 'assertInitialImport'], $initial_import_nums);

    foreach ($migration_labels_to_verify as $label) {
      $this->assertSession()->pageTextContains($label);
    }

    // Run every migrations.
    $this->runAllMigrations();

    // Have to reset all the static caches after migration to ensure entities
    // are loadable.
    $this->resetAll();

    // Leave the dashboard to get potential error messages (notices, warnings,
    // errors).
    $this->drupalGet(Url::fromRoute('system.admin_content'));
    $this->assertNoWarningOrErrorMessages();

    // Check the results.
    $this->performBeanMigrationAssertions();
  }

  /**
   * Data provider for ::testBeanMigrations.
   *
   * @return array
   *   The test cases.
   */
  public function providerTestBeanMigrations() {
    return [
      'Monolingual beans' => [
        'Multilingual' => FALSE,
        'Bean DB fixture' => 'tests/fixtures/drupal7_bean.php',
        'Initial import' => [
          'expected_completed' => TRUE,
          'expected_total' => 50,
          'expected_processed' => 50,
          'expected_imported' => 42,
        ],
        'Migrations should exist' => [
          'Bean block placements',
          'Image custom blocks from Bean',
          'Simple custom blocks from Bean',
        ],
      ],
      'Multilingual beans' => [
        'Multilingual' => TRUE,
        'Bean DB fixture' => 'tests/fixtures/drupal7_bean_multilingual.php',
        'Initial import' => [
          'expected_completed' => TRUE,
          'expected_total' => 88,
          'expected_processed' => 88,
          'expected_imported' => 79,
        ],
        'Migrations should exist' => [
          'Bean block placements',
          'Fully translatable custom blocks from Bean',
          'Image custom blocks from Bean',
          'Simple custom blocks from Bean',
          'Weird custom blocks from Bean',
        ],
      ],
    ];
  }

}
