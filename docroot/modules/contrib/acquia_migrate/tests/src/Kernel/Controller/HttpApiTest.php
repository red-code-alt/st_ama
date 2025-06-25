<?php

namespace Drupal\Tests\acquia_migrate\Kernel\Controller;

use Drupal\acquia_migrate\Batch\MigrationBatchManager;
use Drupal\acquia_migrate\Exception\AcquiaMigrateHttpExceptionInterface;
use Drupal\acquia_migrate\MigrationRepository;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\NestedArray;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Fast(er) tests for esoteric, non-critical HTTP API behavior.
 *
 * Functional tests are provided for the critical bits.
 *
 * @coversDefaultClass \Drupal\acquia_migrate\Controller\HttpApi
 *
 * @group acquia_migrate
 * @group acquia_migrate__core
 */
class HttpApiTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'migrate',
    'acquia_migrate',
    'syslog',
  ];

  /**
   * @var \Drupal\acquia_migrate\Controller\HttpApi
   */
  protected $sut;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installSchema('acquia_migrate', ['acquia_migrate_migration_flags']);
    // The migration repository must be mocked so that the tests below do not
    // depend on a source database and thus fail because an error is thrown
    // about missing source connection configuration.
    $migration_repository = $this->prophesize(MigrationRepository::class);
    $migration_repository->getMigrations()->willReturn([]);
    $this->container->set('acquia_migrate.migration_repository', $migration_repository->reveal());
    $this->sut = $this->container->get('controller.acquia_migrate.http_api');
  }

  /**
   * Tests that the `migrationId` query parameter is requires.
   *
   * @covers ::migrationStart
   */
  public function testMigrationStartQueryStringValidation() {
    $request = Request::create('/acquia-migrate-accelerate/api/migration/import');
    try {
      $this->sut->migrationStart($request, MigrationBatchManager::ACTION_IMPORT);
    }
    catch (\Exception $e) {
      assert($e instanceof AcquiaMigrateHttpExceptionInterface);
      $response = $e->getHttpResponse();
    }
    $this->assertSame(400, $response->getStatusCode());
    $document = Json::decode($response->getContent());
    $key_exists = FALSE;
    $detail = NestedArray::getValue($document, ['errors', 0, 'detail'], $key_exists);
    $this->assertTrue($key_exists);
    $this->assertSame('The `migrationId` query parameter is required.', $detail);
  }

  /**
   * Tests that the controller can validate filters.
   *
   * @param string $filter
   *   The filter part of the URL to request.
   * @param string $expected_error_detail
   *   The detail member of the expected error object.
   *
   * @covers ::messagesCollection
   * @dataProvider invalidFilterProvider
   */
  public function testMigrationMessageFilterValidation($filter, $expected_error_detail) {
    $request = Request::create('/acquia-migrate-accelerate/messages?filter=' . urlencode($filter));
    try {
      $this->sut->messagesCollection($request);
    }
    catch (\Exception $e) {
      assert($e instanceof AcquiaMigrateHttpExceptionInterface);
      $response = $e->getHttpResponse();
    }
    $this->assertSame(400, $response->getStatusCode(), $response->getContent());
    $document = Json::decode($response->getContent());
    $key_exists = FALSE;
    $error = NestedArray::getValue($document, ['errors', 0], $key_exists);
    $this->assertTrue($key_exists);
    $this->assertSame([
      'code' => '400',
      'status' => 'Bad Request',
      'detail' => $expected_error_detail,
    ], $error);
  }

  /**
   * Provides invalid filter query parameters and expected error details.
   */
  public static function invalidFilterProvider() {
    return [
      'Invalid filter parameter format.' => [
        '42',
        "Invalid filter syntax. Expecting a filter in the form `filter=:op,field_name,value`. Invalid filter parameter received in the request URL's query string as: `filter=42`.",
      ],
      'Unrecognized field.' => [
        ':eq,unrecognized,42',
        "The `unrecognized` field is not a recognized, filterable field. Invalid filter parameter received in the request URL's query string as: `filter=:eq,unrecognized,42`.",
      ],
      'Unsupported operator.' => [
        ':neq,severity,42',
        "The `:neq` operator is not supported. Allowed operators are: `:eq`. Invalid filter parameter received in the request URL's query string as: `filter=:neq,severity,42`.",
      ],
      'Disallowed filter value.' => [
        ':eq,severity,42',
        "The `severity` field cannot be filtered by the requested value, `42`. Invalid filter parameter received in the request URL's query string as: `filter=:eq,severity,42`.",
      ],
    ];
  }

}
