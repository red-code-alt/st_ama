<?php

namespace Drupal\Tests\acquia_migrate\Kernel\Migrate;

use Drupal\acquia_migrate\MigrationFingerprinter;
use Drupal\migrate\Plugin\Migration;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Verifies that executing file migration without users does not logs SQL error.
 *
 * @group acquia_migrate
 * @group acquia_migrate__core
 */
class MigrateFilesWithoutUsersTest extends MigrateDrupal7TestBase {

  use UserCreationTrait {
    createUser as drupalCreateUser;
  }

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'acquia_migrate',
    'image',
    'migrate_drupal_ui',
    'node',
    'syslog',
  ];

  /**
   * Returns the absolute path to the file system fixture directory.
   *
   * @return string
   *   The absolute path to the file system fixture directory.
   */
  public function getFilesystemFixturePath() {
    return implode(DIRECTORY_SEPARATOR, [
      DRUPAL_ROOT,
      drupal_get_path('module', 'migrate_drupal_ui'),
      'tests',
      'src',
      'Functional',
      'd7',
      'files',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('file');
    $this->installSchema('acquia_migrate', [MigrationFingerprinter::FLAGS_TABLE]);
    $this->installSchema('file', ['file_usage']);
  }

  /**
   * Tests file migration without executed user migration.
   */
  public function testFileMigration() {
    // Create user 1 – this triggers the regression that we want to fix.
    $this->drupalCreateUser([], NULL, FALSE, ['uid' => 1]);

    $fs_fixture_path = $this->getFilesystemFixturePath();
    $file_migration = $this->getMigration('d7_file');
    $source = $file_migration->getSourceConfiguration();
    $source['constants']['source_base_path'] = $fs_fixture_path;
    $file_migration->set('source', $source);
    $this->executeMigration($file_migration);

    // Tests that the file stub has been created.
    $user_migration = $this->getMigration('d7_user');
    assert($user_migration instanceof Migration);
    $this->assertCount(0, iterator_to_array($user_migration->getIdMap()->getMessages()));
  }

}
