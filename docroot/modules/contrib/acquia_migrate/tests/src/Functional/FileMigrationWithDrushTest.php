<?php

namespace Drupal\Tests\acquia_migrate\Functional;

use Drupal\file\FileInterface;
use Drupal\file\FileStorageInterface;
use Drupal\Tests\acquia_migrate\Traits\MigrateDatabaseFixtureTrait;
use Drupal\Tests\BrowserTestBase;
use Drush\TestTraits\DrushTestTrait;

/**
 * Tests file migrations executed with Drush.
 *
 * @group acquia_migrate
 * @group acquia_migrate__core
 */
class FileMigrationWithDrushTest extends BrowserTestBase {

  use DrushTestTrait;
  use MigrateDatabaseFixtureTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'acquia_migrate',
    'file',
    'syslog',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

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
          'value' => $public_base_path,
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
   * Tests that file migrations are executable with Drush.
   */
  public function testFileMigrationWithDrush(): void {
    $file_storage = $this->container->get('entity_type.manager')->getStorage('file');
    assert($file_storage instanceof FileStorageInterface);
    $this->assertEquals([], $file_storage->loadMultiple());

    $this->drush('migrate:import', ['d7_file,d7_file_private']);

    $files = array_reduce(
      $file_storage->loadMultiple(),
      function (array $carry, FileInterface $file) {
        $carry[$file->id()] = array_diff_key(
          $file->toArray(),
          array_fill_keys(['uuid', 'changed'], '')
        );
        return $carry;
      },
      []
    );
    $this->assertEquals(
      [
        1 => [
          'fid' => [['value' => 1]],
          'langcode' => [['value' => 'en']],
          'uid' => [['target_id' => 1]],
          'filename' => [['value' => 'cube.jpeg']],
          'uri' => [['value' => 'public://cube.jpeg']],
          'filemime' => [['value' => 'image/jpeg']],
          'filesize' => [['value' => 3620]],
          'status' => [['value' => 1]],
          'created' => [['value' => 1421727515]],
        ],
        2 => [
          'fid' => [['value' => 2]],
          'langcode' => [['value' => 'en']],
          'uid' => [['target_id' => 1]],
          'filename' => [['value' => 'ds9.txt']],
          'uri' => [['value' => 'public://ds9.txt']],
          'filemime' => [['value' => 'text/plain']],
          'filesize' => [['value' => 4]],
          'status' => [['value' => 1]],
          'created' => [['value' => 1421727516]],
        ],
        3 => [
          'fid' => [['value' => 3]],
          'langcode' => [['value' => 'en']],
          'uid' => [['target_id' => 1]],
          'filename' => [['value' => 'Babylon5.txt']],
          'uri' => [['value' => 'private://Babylon5.txt']],
          'filemime' => [['value' => 'text/plain']],
          'filesize' => [['value' => 4]],
          'status' => [['value' => 1]],
          'created' => [['value' => 1486104045]],
        ],
      ],
      $files
    );
  }

}
