<?php

namespace Drupal\Tests\acquia_migrate\Kernel\Migrate;

use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\Entity\User;

/**
 * Verifies that executing user migration never marks user one as blocked.
 *
 * @group acquia_migrate
 * @group acquia_migrate__core
 */
class UserOneMustNeverBeBlockedTest extends MigrateDrupal7TestBase {

  use UserCreationTrait {
    createUser as drupalCreateUser;
  }

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'acquia_migrate',
    'syslog',
    'migmag_process',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Whenever a migraton is started using AM:A, user one already exists. If we
    // don't do this, then
    // \Drupal\acquia_migrate\Plugin\migrate\destination\AcquiaMigrateUser would
    // get in the way: it'd cause user one to be created without name/mail/init.
    $this->drupalCreateUser([], NULL, FALSE, ['uid' => 1]);
  }

  /**
   * Tests blocked user one on source site does not affect the destination site.
   */
  public function testUserMigration() {
    // Pretend user one on the source site is blocked.
    $this->sourceDatabase->update('users')
      ->condition('uid', 1)
      ->fields([
        'status' => 0,
      ])
      ->execute();

    $this->executeMigrations([
      'd7_user_role',
      'd7_user',
    ]);

    $this->assertSame(FALSE, User::load(1)->isBlocked());
  }

}
