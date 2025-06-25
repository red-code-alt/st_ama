<?php

namespace Drupal\Tests\acquia_migrate\Kernel\Migrate;

use Drupal\acquia_migrate\Controller\GetStarted;
use Drupal\migrate_drupal\NodeMigrateType;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Tests S3 File System configuration requirements on the "Get Started" page.
 *
 * @group acquia_migrate
 * @group acquia_migrate__core
 */
class S3fsRequirementsTest extends MigrateDrupal7TestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'acquia_migrate',
    'syslog',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->removeNodeMigrateMapTable(NodeMigrateType::NODE_MIGRATE_TYPE_CLASSIC, 7);

    $this->installSchema('acquia_migrate', ['acquia_migrate_migration_flags']);

    // Set file_public_path settings.
    $this->setSetting('migrate_source_base_path', implode(DIRECTORY_SEPARATOR, [
      \Drupal::service('extension.list.module')->getPath('migrate'),
      '..',
      'migrate_drupal_ui',
      'tests',
      'src',
      'Functional',
      'd7',
      'files',
    ]));

    // Insert a record into the system table about the s3fs module.
    $this->sourceDatabase->insert('system')
      ->fields([
        'filename' => 'sites/all/modules/s3fs/s3fs.module',
        'name' => 's3fs',
        'type' => 'module',
        'owner' => '',
        'status' => '1',
        'bootstrap' => '0',
        'schema_version' => '7207',
        'weight' => '0',
        'info' => serialize([
          'name' => 'S3 File System',
          'description' => 'Adds an Amazon Simple Storage Service-based remote file system to Drupal.',
          'php' => 5.5,
          'configure' => 'admin/config/media/s3fs/settings',
          'files' => [
            0 => 'S3fsStreamWrapper.inc',
            1 => 'tests/s3fs.test',
          ],
          'version' => '7.x-3.0-alpha2',
          'core' => '7.x',
          'project' => 's3fs',
          'datestamp' => '1605345413',
        ]),
      ])
      ->execute();

    $admin = $this->createUser([], 'admin', TRUE, ['uid' => 1]);
    $this->setCurrentUser($admin);
  }

  /**
   * Tests s3fs configuration logic on the "Get Started" page.
   *
   * @dataProvider providerTestGetStartedScreen
   */
  public function testGetStartedScreen(bool $s3fs_in_source, array $source_variables, array $destination_settings, bool $expected_s3fs_step_is_present, bool $expected_s3fs_step_completed, bool $expected_preselect_step_active): void {
    $this->setDrupal7ModuleStatus('s3fs', $s3fs_in_source);

    foreach ($source_variables as $variable_name => $variable_value) {
      $this->setSourceDrupal7Variable($variable_name, $variable_value);
    }

    foreach ($destination_settings as $settings_key => $setting_value) {
      $this->setSetting($settings_key, $setting_value);
    }

    $controller = new GetStarted($this->container->get('acquia_migrate.migration_repository'));
    $get_started_build = $controller->build();

    // Check the steps: assert that the 'preselect' step has the appropriate
    // status.
    $steps = $get_started_build['content']['#context']['checklist']['#context']['steps'] ?? NULL;
    $this->assertIsArray($steps);
    if ($expected_s3fs_step_is_present) {
      $this->assertArrayHasKey(GetStarted::S3FS_STEP_KEY, $steps);
      $this->assertIsArray($steps[GetStarted::S3FS_STEP_KEY]);
      $this->assertSame($expected_s3fs_step_completed, $steps[GetStarted::S3FS_STEP_KEY]['completed']);
    }
    else {
      $this->assertArrayNotHasKey(GetStarted::S3FS_STEP_KEY, $steps);
    }

    $this->assertIsArray($steps['preselect']);
    $this->assertFalse($steps['preselect']['completed']);
    $this->assertEquals($expected_preselect_step_active, $steps['preselect']['active']);

    // Rendering shouldn't throw any exception.
    $this->render($get_started_build);
  }

  /**
   * Data provider for ::testGetStartedScreen.
   *
   * @return array[]
   *   The test cases.
   */
  public function providerTestGetStartedScreen(): array {
    return [
      'S3fs disabled, public&private on s3, missing D9 s3fs settings' => [
        's3fs enabled in source' => FALSE,
        'Source variables to set' => [
          's3fs_use_s3_for_public' => 1,
          's3fs_use_s3_for_private' => 1,
        ],
        'Destination settings' => [],
        's3fs step present' => FALSE,
        's3fs step completed' => FALSE,
        'Preselect is active' => TRUE,
      ],
      'S3fs enabled, no s3 override' => [
        's3fs enabled in source' => TRUE,
        'Source variables to set' => [],
        'Destination settings' => [],
        's3fs step present' => FALSE,
        's3fs step completed' => FALSE,
        'Preselect is active' => TRUE,
      ],

      'S3fs enabled, public stored on s3, missing D9 s3fs settings' => [
        's3fs enabled in source' => TRUE,
        'Source variables to set' => [
          's3fs_use_s3_for_public' => 1,
        ],
        'Destination settings' => [],
        's3fs step present' => TRUE,
        's3fs step completed' => FALSE,
        'Preselect is active' => FALSE,
      ],
      'S3fs enabled, public stored on s3, with public D9 s3fs settings' => [
        's3fs enabled in source' => TRUE,
        'Source variables to set' => [
          's3fs_use_s3_for_public' => 1,
        ],
        'Destination settings' => [
          's3fs.use_s3_for_public' => TRUE,
        ],
        's3fs step present' => TRUE,
        's3fs step completed' => TRUE,
        'Preselect is active' => TRUE,
      ],

      'S3fs enabled, private stored on s3, missing D9 s3fs settings' => [
        's3fs enabled in source' => TRUE,
        'Source variables to set' => [
          's3fs_use_s3_for_private' => 1,
        ],
        'Destination settings' => [],
        's3fs step present' => TRUE,
        's3fs step completed' => FALSE,
        'Preselect is active' => FALSE,
      ],
      'S3fs enabled, private stored on s3, with private D9 s3fs settings' => [
        's3fs enabled in source' => TRUE,
        'Source variables to set' => [
          's3fs_use_s3_for_private' => 1,
        ],
        'Destination settings' => [
          's3fs.use_s3_for_private' => TRUE,
        ],
        's3fs step present' => TRUE,
        's3fs step completed' => TRUE,
        'Preselect is active' => TRUE,
      ],

      'S3fs enabled, public&private on s3, missing D9 s3fs settings' => [
        's3fs enabled in source' => TRUE,
        'Source variables to set' => [
          's3fs_use_s3_for_public' => 1,
          's3fs_use_s3_for_private' => 1,
        ],
        'Destination settings' => [],
        's3fs step present' => TRUE,
        's3fs step completed' => FALSE,
        'Preselect is active' => FALSE,
      ],

      'S3fs enabled, public&private on s3, missing public D9 s3fs settings' => [
        's3fs enabled in source' => TRUE,
        'Source variables to set' => [
          's3fs_use_s3_for_public' => 1,
          's3fs_use_s3_for_private' => 1,
        ],
        'Destination settings' => [
          's3fs.use_s3_for_private' => TRUE,
        ],
        's3fs step present' => TRUE,
        's3fs step completed' => FALSE,
        'Preselect is active' => FALSE,
      ],

      'S3fs enabled, public&private on s3, missing private D9 s3fs settings' => [
        's3fs enabled in source' => TRUE,
        'Source variables to set' => [
          's3fs_use_s3_for_public' => 1,
          's3fs_use_s3_for_private' => 1,
        ],
        'Destination settings' => [
          's3fs.use_s3_for_public' => TRUE,
        ],
        's3fs step present' => TRUE,
        's3fs step completed' => FALSE,
        'Preselect is active' => FALSE,
      ],

      'S3fs enabled, public&private on s3, every D9 settings in place' => [
        's3fs enabled in source' => TRUE,
        'Source variables to set' => [
          's3fs_use_s3_for_public' => 1,
          's3fs_use_s3_for_private' => 1,
        ],
        'Destination settings' => [
          's3fs.use_s3_for_public' => TRUE,
          's3fs.use_s3_for_private' => TRUE,
        ],
        's3fs step present' => TRUE,
        's3fs step completed' => TRUE,
        'Preselect is active' => TRUE,
      ],
    ];
  }

  /**
   * Adds/changes a variable in the Drupal 7 source database.
   *
   * @param string $variable_name
   *   The name of the Drupal 7 variable.
   * @param mixed $variable_value
   *   The (unserialized) value of the Drupal 7 variable.
   */
  protected function setSourceDrupal7Variable(string $variable_name, $variable_value): void {
    $this->sourceDatabase->upsert('variable')
      ->key('name')
      ->fields([
        'name' => $variable_name,
        'value' => serialize($variable_value),
      ])
      ->execute();
  }

  /**
   * Set the specified status for the given module in the Drupal 7 source.
   *
   * @param string $module_name
   *   The name of the Drupal 7 module.
   * @param bool $status
   *   The status to set for the given module.
   */
  protected function setDrupal7ModuleStatus(string $module_name, bool $status): void {
    $this->sourceDatabase->update('system')
      ->condition('name', $module_name)
      ->fields([
        'status' => (int) $status,
      ])
      ->execute();
  }

}
