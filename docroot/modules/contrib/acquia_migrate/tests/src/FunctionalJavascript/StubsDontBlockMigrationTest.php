<?php

namespace Drupal\Tests\acquia_migrate\FunctionalJavascript;

use Drupal\Core\Url;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\node\Entity\Node;
use Drupal\Tests\acquia_migrate\Traits\MigrateDatabaseFixtureTrait;
use Drupal\Tests\acquia_migrate\Traits\MigrateJsUiTrait;

/**
 * Tests migration with node stubs.
 *
 * @group acquia_migrate
 * @group acquia_migrate__core
 */
class StubsDontBlockMigrationTest extends WebDriverTestBase {

  use MigrateDatabaseFixtureTrait;
  use MigrateJsUiTrait;

  /**
   * {@inheritdoc}
   */
  protected function getFixtureFilePath() {
    return __DIR__ . '/../../fixtures/drupal7-stubs.php';
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
  ];

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
   * Tests the migration.
   */
  public function testMigration() {
    $this->submitMigrationContentSelectionScreen();
    $this->visitMigrationDashboard();

    // Run required migrations.
    $this->runSingleMigration('User accounts');
    $this->runSingleMigration('Article type');
    $this->runSingleMigration('Blog type');

    // Have to reset all the static caches after migration to ensure entities
    // are loadable.
    $this->resetAll();

    // Leave the dashboard.
    $this->drupalGet(Url::fromRoute('system.admin_content'));
    $this->assertNoWarningOrErrorMessages();

    $props_to_compare = ['title', 'body', 'field_node_reference'];

    $this->assertEquals(
      [
        'title' => [['value' => 'Article #1']],
        'body' => [
          [
            'value' => 'Body of article #1.',
            'summary' => '',
            'format' => 'plain_text',
          ],
        ],
        'field_node_reference' => [['target_id' => '2']],
      ],
      array_intersect_key(
        Node::load(1)->toArray(),
        array_combine($props_to_compare, $props_to_compare)
      )
    );

    $this->assertEquals(
      [
        'title' => [['value' => 'Blog #2']],
        'body' => [
          [
            'value' => 'Body of blog #2.',
            'summary' => '',
            'format' => 'plain_text',
          ],
        ],
        'field_node_reference' => [['target_id' => '1']],
      ],
      array_intersect_key(
        Node::load(2)->toArray(),
        array_combine($props_to_compare, $props_to_compare)
      )
    );

    $this->assertEquals(
      [
        'title' => [['value' => 'Another article #3']],
        'body' => [
          [
            'value' => 'Body of article #3.',
            'summary' => '',
            'format' => 'plain_text',
          ],
        ],
        'field_node_reference' => [
          ['target_id' => '1'],
          ['target_id' => '2'],
        ],
      ],
      array_intersect_key(
        Node::load(3)->toArray(),
        array_combine($props_to_compare, $props_to_compare)
      )
    );
  }

}
