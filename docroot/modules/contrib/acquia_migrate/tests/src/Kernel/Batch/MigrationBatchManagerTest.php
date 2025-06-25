<?php

namespace Drupal\Tests\acquia_migrate\Kernel\Batch;

use Drupal\acquia_migrate\Batch\BatchStatus;
use Drupal\acquia_migrate\Batch\BatchUnknown;
use Drupal\acquia_migrate\Batch\MigrationBatchManager;
use Drupal\Core\Routing\RouteObjectInterface;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Route;

/**
 * Tests the migration batch manager.
 *
 * @coversDefaultClass \Drupal\acquia_migrate\Batch\MigrationBatchManager
 *
 * @group acquia_migrate
 * @group acquia_migrate__core
 */
class MigrationBatchManagerTest extends MigrateDrupal7TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'acquia_migrate',
  ];

  /**
   * The migration batch manager.
   *
   * @var \Drupal\acquia_migrate\Batch\MigrationBatchManager
   */
  protected $sut;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installSchema('system', ['sequences']);
    $this->installSchema('acquia_migrate', ['acquia_migrate_migration_flags']);

    // Mock an incoming request.
    $session_id = $this->randomMachineName();
    $request = Request::create('/acquia-migrate-accelerate/api/migration/process/1');
    $request->attributes->set(RouteObjectInterface::ROUTE_NAME, 'acquia_migrate.api.migration.process');
    $request->attributes->set(RouteObjectInterface::ROUTE_OBJECT, new Route('/acquia-migrate-accelerate/api/migration/process/{process_id}', [], [], ['process_id' => 1]));
    $request->attributes->set('process_id', '1');
    $request->attributes->set('_raw_variables', new ParameterBag(['process_id' => '1']));
    $session = new Session();
    $session->setId($session_id);
    $request->setSession($session);
    $this->container->get('request_stack')->push($request);
    // In kernel tests, `SCRIPT_NAME` is set to `'Standard input code'`, which
    // leads to Symfony concluding the base URL is `http://localhost.`, which
    // leads to Drupal concluding an untrusted redirect response is happening.
    // This prevents that.
    // @see \Drupal\acquia_migrate\Batch\MigrationBatchManager::isMigrationBatchOngoing()
    // @see \Symfony\Component\HttpFoundation\Request::createFromGlobals()
    // @see \Drupal\Core\DrupalKernel::initializeRequestGlobals()
    // phpcs:ignore SlevomatCodingStandard.Variables.DisallowSuperGlobalVariable.DisallowedSuperGlobalVariable
    $GLOBALS['_SERVER']['SCRIPT_NAME'] = '/index.php';

    $this->sut = $this->container->get('acquia_migrate.batch_manager');
  }

  /**
   * Tests that this method creates a new batch.
   *
   * @covers ::createMigrationBatch
   */
  public function testCreateMigrationBatch() {
    $migration_id = 'dbdd6377389228728e6ab594c50ad011-User accounts';
    $migration_info = $this->sut->createMigrationBatch($migration_id, MigrationBatchManager::ACTION_IMPORT);
    $this->assertInstanceOf(BatchStatus::class, $migration_info);
    $batch = batch_get();
    $this->assertArrayHasKey('id', $batch);
    $this->assertSame((int) $batch['id'], $migration_info->getId());
  }

  /**
   * Tests that this method processes and reports on batch progress.
   *
   * @covers ::isMigrationBatchOngoing
   */
  public function testIsMigrationOngoing() {
    $migration_id = 'dbdd6377389228728e6ab594c50ad011-User accounts';
    $migration_info = $this->sut->createMigrationBatch($migration_id, MigrationBatchManager::ACTION_IMPORT);
    $this->assertInstanceOf(BatchStatus::class, $migration_info);

    // Simulate the batch being executed.
    $this->container->get('acquia_migrate.coordinator')->startOperation();

    // Unset the global batch to test that the batch manager can load a batch by
    // its ID.
    $batch =& batch_get();
    $batch = [];

    $current_status = $migration_info;
    for ($i = 0; $current_status instanceof BatchStatus && $i < 10; $i++) {
      $previous_status = $current_status;
      $current_status = $this->sut->isMigrationBatchOngoing($migration_info->getId());
      if ($previous_status->getProgress() < 1) {
        $this->assertGreaterThan($previous_status->getProgress(), $current_status->getProgress());
      }
    }
    $this->assertInstanceOf(BatchUnknown::class, $this->sut->isMigrationBatchOngoing($migration_info->getId()));
  }

}
