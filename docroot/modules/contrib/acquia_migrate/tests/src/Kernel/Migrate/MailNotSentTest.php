<?php

namespace Drupal\Tests\acquia_migrate\Kernel\Migrate;

use Drupal\migmag_rollbackable\RollbackableInterface;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Regression test for ensuring that emails aren't sent.
 *
 * @group acquia_migrate
 * @group acquia_migrate__core
 */
class MailNotSentTest extends MigrateDrupal7TestBase {

  /**
   * ID of our terrible mail man.
   *
   * @const string
   */
  const TERRIBLE_MAIL_MAN_ID = 'terrible_mail_man';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'acquia_migrate',
    'syslog',
    'migmag',
    'migmag_rollbackable',
    'migmag_rollbackable_replace',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installSchema('acquia_migrate', ['acquia_migrate_migration_flags']);
    $this->installSchema('migmag_rollbackable', [RollbackableInterface::ROLLBACK_DATA_TABLE, RollbackableInterface::ROLLBACK_STATE_TABLE]);

    $this->installConfig(['system', 'user']);

    // We have to remove the system.mail override set in our base class.
    // @see \Drupal\KernelTests\KernelTestBase::bootKernel
    // phpcs:ignore SlevomatCodingStandard.Variables.DisallowSuperGlobalVariable
    unset($GLOBALS['config']['system.mail']);
  }

  /**
   * Regression test for ensuring that emails aren't sent.
   */
  public function testEmailsNotSent(): void {
    // Terrible mail man should be available.
    $mail_plugin_manager = \Drupal::service('plugin.manager.mail');
    $this->assertNotEmpty($mail_plugin_manager->getDefinitions()[self::TERRIBLE_MAIL_MAN_ID]);

    // Set an initial value for the default interface mailer plugin.
    $this->config('system.mail')
      ->set('interface.default', self::TERRIBLE_MAIL_MAN_ID)
      ->save(TRUE);
    // ...so before the mail settings migration is executed, the interface mail
    // plugin must be 'terrible_mail_man'.
    $mail_config = \Drupal::configFactory()->get('system.mail');
    $this->assertEquals(
      self::TERRIBLE_MAIL_MAN_ID,
      $mail_config->getOriginal('interface.default', FALSE)
    );
    // ...and the overridden data is also 'terrible_mail_man'.
    $this->assertEquals(
      self::TERRIBLE_MAIL_MAN_ID,
      $mail_config->get('interface.default')
    );

    // Execute the mail settings migration.
    $this->startCollectingMessages();
    $this->executeMigrations(['d7_system_mail']);
    $this->assertEmpty($this->migrateMessages);

    // After the mail settings migration was executed, the new interface mail
    // plugin must be set to 'php_mail'.
    $mail_config = \Drupal::configFactory()->get('system.mail');
    $this->assertEquals(
      'php_mail',
      $mail_config->getOriginal('interface.default', FALSE)
    );
    // ...but the real (overridden) value should be still 'terrible_mail_man'.
    $this->assertEquals(
      self::TERRIBLE_MAIL_MAN_ID,
      $mail_config->get('interface.default')
    );

    // If customers uninstall 'acquia_migrate', then their real (possibly
    // overridden) mail plugin must be 'php_mail'.
    $this->disableModules(['acquia_migrate']);
    $mail_config = \Drupal::configFactory()->get('system.mail');
    $this->assertEquals(
      'php_mail',
      $mail_config->get('interface.default')
    );
  }

}
