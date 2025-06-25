<?php

namespace Drupal\Tests\acquia_migrate\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * @group acquia_migrate
 * @group acquia_migrate__core
 */
class ReinstallationExperienceTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'acquia_migrate',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Checks the experience of (re)installing Acquia Migrate Accelerate.
   */
  public function testReInstallationExperience() {
    // The frontpage should contain a link to configure user 1.
    $this->drupalGet('');
    $this->assertLink('Essential configuration');
    $this->assertNoLink('Log in');
    $this->assertText('Import your content');

    // Clicking that link should allow configuring user 1.
    $this->clickLink('Essential configuration');
    $this->assertElementPresent('form[data-drupal-selector="user-acquia-migrate-configure-user-one-form"]');
    $input = [
      'source_site_info[base_url]' => 'https://example.com',
      'mail' => 'john.doe@example.com',
      'name' => 'john.doe',
      'pass[pass1]' => 'https://xkcd.com/936/',
      'pass[pass2]' => 'https://xkcd.com/936/',
    ];

    // Saving that form should redirect us to the original page, now without the
    // link we previously clicked, but with a link to log in instead.
    $this->submitForm($input, 'Save');
    $this->assertUrl('/acquia-migrate-accelerate/get-started');
    $this->assertNoLink('Configure user 1');
    $this->assertLink('Log in');
    $this->assertText('Import your content');

    // Clicking that link should allow logging in.
    $this->clickLink('Log in');
    $this->assertElementPresent('form[data-drupal-selector="user-login-form"]');
    $input = [
      'name' => 'john.doe',
      'pass' => 'https://xkcd.com/936/',
    ];

    // Logging in should redirect us once again to the original page.
    $this->submitForm($input, 'Log in');
    $this->assertUrl('/acquia-migrate-accelerate/get-started');
    $this->assertNoLink('Configure user 1');
    $this->assertNoLink('Log in');
    $this->assertText('Import your content');

    // Log out and reinstall the module.
    $this->drupalLogout();
    $this->container->get('module_installer')->uninstall(['acquia_migrate']);
    $this->container->get('module_installer')->install(['acquia_migrate']);

    // The frontpage should NOT contain a link to configure user 1.
    $this->drupalGet('');
    $this->assertNoLink('Configure user 1');
    $this->assertLink('Log in');
    $this->assertText('Import your content');
  }

}
