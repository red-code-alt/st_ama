<?php

declare(strict_types = 1);

namespace Drupal\Tests\acquia_migrate\Kernel;

use Drupal\acquia_migrate\MigrationFingerprinter;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Random;
use Drupal\Core\Database\Database;
use Drupal\Tests\acquia_migrate\Traits\AmaHttpApiTestTrait;
use Drupal\Tests\acquia_migrate\Traits\AmaKernelTestSetupTrait;
use Drupal\Tests\acquia_migrate\Traits\AmaMigrateKernelTestTrait;
use Drupal\Tests\migrate_drupal\Kernel\MigrateDrupalTestBase;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests source DB refresh detection.
 *
 * @group acquia_migrate
 * @group acquia_migrate__core
 * @group acquia_migrate__mysql
 * @group legacy
 */
class RefreshDetectionTest extends MigrateDrupalTestBase {

  use AmaHttpApiTestTrait;
  use AmaKernelTestSetupTrait;
  use AmaMigrateKernelTestTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installAma();
    $this->installDrupalThemes();
  }

  /**
   * Gets the path to the fixture file.
   */
  protected function getFixtureFilePath(): string {
    return drupal_get_path('module', 'migrate_drupal') . '/tests/fixtures/drupal7.php';
  }

  /**
   * Tests refresh through HTTP API.
   *
   * We will have the re-import link: most of the should-have migrate
   * destinations modules aren't enabled.
   *
   * @todo Create a minimal DB fixture.
   *
   * @dataProvider providerTestRefresh
   */
  public function testHttpApi(bool $joinable_databases): void {
    $this->loadFixture($this->getFixtureFilePath(), $joinable_databases);

    $this->performMigrationPreselection();
    $this->performUpdateCheck(FALSE);
    $this->performInitialMigration();

    // Execute all the migrations we have.
    $collection = $this->getMigrationCollection();
    foreach ($collection['data'] as $resource) {
      $import_link = $this->findLinkWithTitle('Import', $resource);
      if (!empty($import_link)) {
        $this->executeBatch($import_link);
      }
    }

    // We have nothing to refresh.
    $this->assertEmpty($this->findLinkWithTitle('Check for updates', $this->getMigrationCollection()));

    // But if we set the fingerprint compute time back into the past...
    $this->setBackComputeTime();

    // Perform check if necessary.
    $this->performUpdateCheck(FALSE);
    // We shouldn't have to check the source anymore.
    $collection = $this->getMigrationCollection();
    $this->assertEmpty($this->findLinkWithTitle('Check for updates', $collection));

    // Now add a new user to the source, and also set the fingerprint compute
    // time back in the past.
    $this->addNewUserToSource();
    // On a non-Acquia env, operating on not joinable sources, we have to modify
    // something in the canary table.
    $this->modifyCanaryTable();
    $this->setBackComputeTime();

    // On a non-joinable sources, we have to modify the canary table.
    if (!$joinable_databases) {
      $this->modifyCanaryTable();
    }

    // We should have a check link again, lets perform the check!
    $response = $this->performUpdateCheck();
    $update_response_data = Json::decode($response->getContent())['data'] ?? [];
    // SQLite fingerprinting isn't perfect; on SQLite we will have 4 or 5
    // migrations to refresh.
    if (Database::getConnectionInfo()['default']['driver'] === 'mysql') {
      $this->assertCount(2, $update_response_data);
    }
    // Use migration labels as key.
    $update_response_data = array_reduce(
      $update_response_data,
      function (array $carry, array $data) {
        if (!empty($data['attributes']['label'])) {
          $carry[$data['attributes']['label']] = $data;
        }
        return $carry;
      },
      []
    );
    $this->assertEquals(
      [
        'User accounts' => [
          'type' => 'migration',
          'id' => 'dbdd6377389228728e6ab594c50ad011-User accounts',
          'attributes' => ['label' => 'User accounts'],
        ],
        'Site configuration' => [
          'type' => 'migration',
          'id' => 'b11ec035a0ea55f7bf0af42f84083be8-Site configuration',
          'attributes' => ['label' => 'Site configuration'],
        ],
      ],
      array_intersect_key(
        $update_response_data,
        ['User accounts' => 1, 'Site configuration' => 1]
      )
    );

    // We will have the initial import link again... with title like
    // "Re-importing supporting configuration (26) after a rollback…".
    $this->assertNotEmpty($refresh_link = $this->findLinkWithTitle('Re-importing supporting configuration .*', $this->getMigrationCollection()));
    $this->executeBatch($refresh_link);
    $this->assertEmpty($this->findLinkWithTitle('Check for updates', $this->getMigrationCollection()));

    // User collection must have a refresh link.
    $user_collection = $this->getCollection('User accounts');
    $this->assertNotEmpty($user_refresh_link = $this->findLinkWithTitle('Refresh', $user_collection));

    // Before we refresh the user migration, we should have 3 user accounts.
    $this->assertCount(3, User::loadMultiple());
    $this->executeBatch($user_refresh_link);
    // After refresh, we should have 4 user accounts.
    $this->assertCount(4, User::loadMultiple());

    // Suppress the messages logged during testing.
    $this->getActualOutputForAssertion();
  }

  /**
   * Performs update check using HTTP API. Update link must be present.
   *
   * @return \Symfony\Component\HttpFoundation\Response|null
   *   The update response, or NULL if there wasn't any update link.
   */
  protected function performUpdateCheck(bool $strict = TRUE): ?Response {
    $update_link = $this->findLinkWithTitle('Check for updates', $this->getMigrationCollection());
    if ($strict) {
      $this->assertNotEmpty($update_link);
    }
    elseif (!$update_link) {
      return NULL;
    }
    $response = $this->requestAndHandle(static::getRelativeUrl($update_link));
    $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
    // Any time recompute happened, we have to drop the query log.
    static::dropQueryLog();
    return $response;
  }

  /**
   * Data provider of refresh tests.
   *
   * @return array
   *   The test cases.
   */
  public function providerTestRefresh(): array {
    return [
      'local env, joinable' => ['joinable' => TRUE],
      'local env, unjoinable' => ['joinable' => FALSE],
    ];
  }

  /**
   * Adds a new user to the original source database.
   */
  protected function addNewUserToSource(): void {
    $this->sourceDatabase->insert('users')
      ->fields([
        'uid' => 11,
        'name' => 'new',
        'pass' => \Drupal::service('password')->hash(trim('pass')),
        'mail' => 'new@localhost',
        'theme' => '',
        'signature' => '',
        'signature_format' => NULL,
        'created' => '1500000000',
        'access' => '1500000100',
        'login' => '1500000100',
        'status' => 1,
        'timezone' => NULL,
        'language' => '',
        'picture' => 0,
        'init' => 'new@localhost',
        'data' => serialize([]),
      ])
      ->execute();
  }

  /**
   * Modifies a value in the canary table.
   */
  protected function modifyCanaryTable(): void {
    $this->sourceDatabase->upsert(MigrationFingerprinter::CANARY_TABLE)
      ->key('name')
      ->fields([
        'name' => 'css_js_query_string',
        'value' => serialize((new Random())->string(6, TRUE)),
      ])
      ->execute();
  }

}
