<?php

namespace Drupal\Tests\webform_migrate\Kernel\Migrate\d7;

/**
 * Tests webform_validation migrations.
 */
class WebformValidationmigrationTest extends WebformMigrateTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'minimal';

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'datetime',
    'editor',
    'field',
    'file',
    'filter',
    'migrate',
    'migrate_drupal',
    'node',
    'options',
    'system',
    'text',
    'user',
    'webform',
    'webform_migrate',
    'webform_node',
    'webform_validation',
  ];

  /**
   * Returns the drupal-relative path to the database fixture file.
   *
   * @return string
   *   The path to the database file.
   */
  public function getDatabaseFixtureFilePath() {
    return drupal_get_path('module', 'webform_migrate') . '/tests/fixtures/drupal7.php';
  }

  /**
   * Returns the absolute path to the file system fixture directory.
   *
   * @return string
   *   The absolute path to the file system fixture directory.
   */
  public function getFilesystemFixturePath() {
    return implode(DIRECTORY_SEPARATOR, [
      DRUPAL_ROOT,
      drupal_get_path('module', 'webform_migrate'),
      'tests',
      'fixtures',
      'files',
    ]);
  }

  /**
   * Tests webform_validation migration.
   */
  public function testWebformValidationMigrations() {
    // Execute the relevant migrations.
    $this->executeWebformMigrations();
    $webform_2 = $this->container->get('entity_type.manager')->getStorage('webform')->load('webform_2');
    $webform_2->getElementDecoded('');
    $this->assertSame([
      '#type' => 'textfield',
      '#title' => 'test1234',
      '#description' => '',
      '#equal' => TRUE,
      '#equal_stepwise_validate' => 0,
      '#equal_components' => [
        'some_component' => 'some_component',
        'test' => 0,
        'date11' => 'date11',
        'date12' => 'date12',
        'test123456' => 0,
      ],
    ], $webform_2->get('elementsDecoded')['test1234']);

    $webform_3 = $this->container->get('entity_type.manager')->getStorage('webform')->load('webform_3');
    $webform_3->getElementDecoded('');
    $this->assertSame([
      '#type' => 'textfield',
      '#title' => 'an1',
      '#description' => '',
      '#equal' => TRUE,
      '#equal_stepwise_validate' => 0,
      '#equal_components' => [
        'an2' => 'an2',
      ],
    ], $webform_3->get('elementsDecoded')['an1']);
  }

}
