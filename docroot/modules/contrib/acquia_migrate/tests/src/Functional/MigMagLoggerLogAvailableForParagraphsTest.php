<?php

namespace Drupal\Tests\acquia_migrate\Functional;

use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\migrate\Plugin\MigratePluginManagerInterface;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests that MigMagLoggerLog is available if paragraphs was installed.
 *
 * @group acquia_migrate
 * @group acquia_migrate__contrib
 */
class MigMagLoggerLogAvailableForParagraphsTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'syslog',
    'migrate_drupal',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests that MigMagLoggerLog is available if paragraphs was installed later.
   */
  public function testLoggerPluginAvailabilityAfterParagraphsInstall() {
    $process_plugin_manager = $this->container->get('plugin.manager.migrate.process');
    assert($process_plugin_manager instanceof MigratePluginManagerInterface);
    $this->assertFalse($process_plugin_manager->hasDefinition('migmag_logger_log'));

    $installer = $this->container->get('module_installer');
    assert($installer instanceof ModuleInstallerInterface);
    $installer->install(['acquia_migrate'], TRUE);
    $this->resetAll();

    $installer->install(['paragraphs']);
    $this->resetAll();

    $process_plugin_manager = $this->container->get('plugin.manager.migrate.process');
    assert($process_plugin_manager instanceof MigratePluginManagerInterface);
    $this->assertTrue($process_plugin_manager->hasDefinition('migmag_logger_log'));
  }

  /**
   * Tests that MigMagLoggerLog is available if AMA was installed later.
   */
  public function testLoggerPluginAvailabilityAfterAcquiaMigrateInstall() {
    $process_plugin_manager = $this->container->get('plugin.manager.migrate.process');
    assert($process_plugin_manager instanceof MigratePluginManagerInterface);
    $this->assertFalse($process_plugin_manager->hasDefinition('migmag_logger_log'));

    $installer = $this->container->get('module_installer');
    assert($installer instanceof ModuleInstallerInterface);
    $installer->install(['paragraphs'], TRUE);
    $this->resetAll();

    $installer->install(['acquia_migrate']);
    $this->resetAll();

    $process_plugin_manager = $this->container->get('plugin.manager.migrate.process');
    assert($process_plugin_manager instanceof MigratePluginManagerInterface);
    $this->assertTrue($process_plugin_manager->hasDefinition('migmag_logger_log'));
  }

  /**
   * Tests that MigMagLoggerLog isn't available if only AMA was installed.
   */
  public function testLoggerPluginAvailabilityWithoutParagraphs() {
    $process_plugin_manager = $this->container->get('plugin.manager.migrate.process');
    assert($process_plugin_manager instanceof MigratePluginManagerInterface);
    $this->assertFalse($process_plugin_manager->hasDefinition('migmag_logger_log'));

    $installer = $this->container->get('module_installer');
    assert($installer instanceof ModuleInstallerInterface);
    $installer->install(['acquia_migrate']);
    $this->drupalGet('<front>');

    $process_plugin_manager = $this->container->get('plugin.manager.migrate.process');
    assert($process_plugin_manager instanceof MigratePluginManagerInterface);
    $this->assertFalse($process_plugin_manager->hasDefinition('migmag_logger_log'));
  }

}
