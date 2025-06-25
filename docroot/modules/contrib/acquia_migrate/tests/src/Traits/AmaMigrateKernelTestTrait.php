<?php

declare(strict_types = 1);

namespace Drupal\Tests\acquia_migrate\Traits;

use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\Core\Extension\ThemeInstallerInterface;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;

/**
 * Trait for basic routines required for testing with AMA in kernel tests.
 */
trait AmaMigrateKernelTestTrait {

  use UserCreationTrait;

  /**
   * Enables AMA, Media Migration and all of their dependencies.
   */
  protected function installAma(bool $with_media_migration = TRUE): void {
    $this->enableModules(['user', 'system', 'file']);
    $this->installConfig(['user', 'system']);
    $this->installEntitySchema('file');
    $this->installEntitySchema('user');
    $this->installSchema('system', ['sequences', 'sessions']);

    // We have to create at least an anonymous user.
    // @see https://drupal.org/i/3056234#comment-13275077
    if (User::load(0) instanceof UserInterface) {
      $this->createUser([], '', FALSE, [
        'uid' => 0,
        'langcode' => 'und',
      ]);
    }
    // ..And we also have to create the user with uid 1.
    $admin = $this->createUser([], '', TRUE, [
      'uid' => 1,
      'langcode' => 'und',
    ]);
    $this->setCurrentUser($admin);

    // Set up filesystem related stuffs.
    $site_path = $this->container->hasParameter('site.path')
      ? $this->container->getParameter('site.path')
      : $this->container->get('site.path');
    $this->setSetting('file_private_path', $site_path . '/private');

    $this->enableModulesWithDependencies(array_filter([
      'acquia_migrate',
      $with_media_migration ? 'media_migration' : NULL,
    ]));
  }

  /**
   * Installs the given themes.
   *
   * @param string $front_end
   *   Machine name of front end theme. Defaults to 'bartik'.
   * @param string $admin
   *   Machine name of admin theme. Defaults to 'seven'.
   * @param string[] $extra
   *   Machine names of any other themes to install. Defaults to ['claro'].
   */
  protected function installDrupalThemes(string $front_end = 'bartik', string $admin = 'seven', array $extra = ['claro']): void {
    $admin = $admin ?? $front_end;
    $theme_installer = \Drupal::service('theme_installer');
    $this->assertInstanceOf(ThemeInstallerInterface::class, $theme_installer);
    $theme_installer->install(array_unique(
      array_merge([$front_end, $admin], $extra)
    ));
    $this->config('system.theme')
      ->set('default', $front_end)
      ->set('admin', $admin)
      ->save();
  }

  /**
   * Enables the given modules with module installer.
   *
   * This means that all of the configurations, DB schemas are installed, and
   * the install hooks are also invoked.
   *
   * @param string[] $modules
   *   Modules to install.
   */
  protected function enableModulesWithDependencies(array $modules): void {
    $module_installer = $this->container->get('module_installer');
    $this->assertInstanceOf(ModuleInstallerInterface::class, $module_installer);
    $module_installer->install($modules);
  }

}
