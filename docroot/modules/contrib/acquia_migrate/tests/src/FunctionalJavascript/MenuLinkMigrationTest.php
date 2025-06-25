<?php

namespace Drupal\Tests\acquia_migrate\FunctionalJavascript;

use Drupal\Core\Menu\MenuActiveTrailInterface;
use Drupal\Core\Menu\MenuLinkTreeElement;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Url;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\menu_link_content\Plugin\Menu\MenuLinkContent as MenuLinkContentPlugin;
use Drupal\Tests\acquia_migrate\Traits\MigrateDatabaseFixtureTrait;
use Drupal\Tests\acquia_migrate\Traits\MigrateJsUiTrait;

/**
 * Tests single-language migration from the core Drupal 7 database fixture.
 *
 * @group acquia_migrate
 * @group acquia_migrate__core
 */
class MenuLinkMigrationTest extends WebDriverTestBase {

  use MigrateDatabaseFixtureTrait {
    MigrateDatabaseFixtureTrait::getFixtureFilePath as getCoreFixtureFilePath;
  }
  use MigrateJsUiTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'acquia_migrate',
    'menu_link_content',
    'migmag_menu_link_migrate',
    'node',
  ];

  /**
   * Returns the path to the fixture file.
   */
  protected function getFixtureFilePath() {
    return drupal_get_path('module', 'migmag_menu_link_migrate') . '/tests/fixtures/d7-menu-link-db.php';
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

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
   * Tests menu link migration with a minimal fixture of an AMA customer.
   */
  public function testMenuLinkMigrationWithRv() {
    $this->setupMigrationConnection();

    // Hotfix for missing 'actions' table in Migrate Magician Menu Link Migrate
    // fixture: create an empty actions table in source.
    $this->sourceDatabase->schema()->createTable(
      'actions',
      [
        'fields' => [
          'aid' => [
            'type' => 'varchar',
            'not null' => TRUE,
            'length' => '255',
            'default' => '0',
          ],
          'type' => [
            'type' => 'varchar',
            'not null' => TRUE,
            'length' => '32',
            'default' => '',
          ],
          'callback' => [
            'type' => 'varchar',
            'not null' => TRUE,
            'length' => '255',
            'default' => '',
          ],
          'parameters' => [
            'type' => 'blob',
            'not null' => TRUE,
            'size' => 'big',
          ],
          'label' => [
            'type' => 'varchar',
            'not null' => TRUE,
            'length' => '255',
            'default' => '0',
          ],
        ],
        'primary key' => ['aid'],
        'mysql_character_set' => 'utf8',
      ]
    );

    $this->submitMigrationContentSelectionScreen();
    $this->visitMigrationDashboard();

    // Execute only test type 5 migrations, and then check the hierarchy of
    // "main" menu.
    $this->runSingleMigration('Test type 5');
    $this->resetAll();
    // 8 menu link migrated for "test_type5" nodes, and 5 stub menu links.
    $this->assertCount(8 + 5, MenuLinkContent::loadMultiple());

    // 8 menu links (3 stub and 5 final) should be found in the main menu.
    $this->assertSame(
      [
        690 => [
          'title' => "Menu link mlid #690 (stub: 'node/850')",
          'status' => FALSE,
          'children' => [
            1184 => [
              'title' => 'Menu link mlid #1184',
              'status' => TRUE,
              'children' => [
                1139 => [
                  'title' => 'Menu link mlid #1139',
                  'status' => TRUE,
                ],
                4026 => [
                  'title' => 'Menu link mlid #4026',
                  'status' => TRUE,
                ],
              ],
            ],
          ],
        ],
        701 => [
          'title' => "Menu link mlid #701 (stub: 'node/894')",
          'status' => FALSE,
          'children' => [
            2318 => [
              'title' => 'Menu link mlid #2318',
              'status' => TRUE,
            ],
          ],
        ],
        700 => [
          'title' => "Menu link mlid #700 (stub: 'node/1074')",
          'status' => FALSE,
          'children' => [
            1920 => [
              'title' => 'Menu link mlid #1920',
              'status' => TRUE,
            ],
          ],
        ],
      ],
      $this->getMenuHierarchy('main')
    );

    // Run every other migrations.
    $this->runAllMigrations();

    // Have to reset all the static caches after migration to ensure entities
    // are loadable.
    $this->resetAll();

    // Leave the dashboard to get potential error messages (notices, warnings,
    // errors).
    $this->drupalGet(Url::fromRoute('system.admin_content'));
    $this->assertNoWarningOrErrorMessages();

    $this->assertCount(285, MenuLinkContent::loadMultiple());
  }

  /**
   * Returns the actual structure of the provided menu link.
   *
   * @param string $menu_name
   *   The ID of the menu.
   *
   * @return array
   *   A simplified array of the menu links and their children found in the
   *   given menu.
   */
  protected function getMenuHierarchy(string $menu_name): array {
    $menu_parameters = new MenuTreeParameters();
    $menu_parameters->setMinDepth(1);
    $active_trail = \Drupal::service('menu.active_trail');
    assert($active_trail instanceof MenuActiveTrailInterface);
    $menu_parameters->setActiveTrail($active_trail->getActiveTrailIds($menu_name));

    $menu_tree_service = \Drupal::service('menu.link_tree');
    assert($menu_tree_service instanceof MenuLinkTreeInterface);
    $tree = $menu_tree_service->load($menu_name, $menu_parameters);
    $tree = $menu_tree_service->transform(
      $tree,
      [
        ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
      ]
    );
    $structure = [];
    foreach ($tree as $menu_link_tree_element) {
      assert($menu_link_tree_element instanceof MenuLinkTreeElement);
      $this->mergeTreeElementData($structure, $menu_link_tree_element);
    }
    return $structure;
  }

  /**
   * Merges the structure of a menu link tree element into the provided var.
   *
   * @param array|null $structure
   *   The structure array to merge the discovered structure into.
   * @param \Drupal\Core\Menu\MenuLinkTreeElement $mlte
   *   The menu link tree element whose data and children should be discovered.
   */
  protected function mergeTreeElementData(&$structure, MenuLinkTreeElement $mlte): void {
    $link = $mlte->link;
    $link_id = $link instanceof MenuLinkContentPlugin
      ? $link->getMetaData()['entity_id']
      : $link->getPluginId();
    $key = $link_id;

    $structure[$key] = [
      'title' => $link->getTitle(),
      'status' => $link->isEnabled(),
    ];

    foreach ($mlte->subtree as $sub_mlte) {
      $this->mergeTreeElementData($structure[$key]['children'], $sub_mlte);
    }
  }

}
