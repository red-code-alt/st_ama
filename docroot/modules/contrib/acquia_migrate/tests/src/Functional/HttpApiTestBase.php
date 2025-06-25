<?php

namespace Drupal\Tests\acquia_migrate\Functional;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Url;
use Drupal\Tests\acquia_migrate\Traits\InitialImportAssertionTrait;
use Drupal\Tests\acquia_migrate\Traits\MigrateDatabaseFixtureTrait;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\jsonapi\Functional\JsonApiRequestTestTrait;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;

/**
 * @ingroup testing
 */
abstract class HttpApiTestBase extends BrowserTestBase {

  use MigrateDatabaseFixtureTrait;
  use JsonApiRequestTestTrait {
    request as jsonapiRequest;
  }
  use InitialImportAssertionTrait;

  /**
   * The base path for all HTTP API endpoints.
   *
   * @const string
   */
  const API_BASE_PATH = '/acquia-migrate-accelerate/api';

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
   * Defines paths for all HTTP API endpoints.
   *
   * @var array
   */
  protected static $endpointPaths = [
    'migrationCollection' => '/migrations',
    'migrationIndividual' => '/migrations/%s',
    'migrationStart' => '/migration/import',
    'migrationProcess' => '/migration/process/%s',
    'migrationMessageCollection' => '/messages',
    'migrationPreview' => '/migrations/%s/preview',
    'migrationMapping' => '/migrations/%s/mapping',
    'moduleInfo' => '/module-info',
    'preselectMigrations' => '/migrations',
  ];

  /**
   * Defines default route parameters for all HTTP API endpoints.
   *
   * @var array
   */
  protected static $endpointPathParameters = [
    'migrationCollection' => [],
    'migrationIndividual' => ['b11ec035a0ea55f7bf0af42f84083be8-Site configuration'],
    'migrationStart' => [],
    'migrationProcess' => ['1'],
    'migrationMessageCollection' => [],
    'migrationPreview' => ['dbdd6377389228728e6ab594c50ad011-User accounts'],
    'migrationMapping' => ['dbdd6377389228728e6ab594c50ad011-User accounts'],
    'moduleInfo' => [],
    'preselectMigrations' => [],
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->setupMigrationConnection();
  }

  /**
   * Prepends an API base URL to the given endpoint path.
   *
   * @param string $endpoint
   *   The endpoint name.
   * @param string[] $args
   *   (optional) An array of strings to substitute as URL params for the
   *   endpoint path.
   *
   * @return \Drupal\Core\Url
   *   A full HTTP API URL object.
   */
  protected function apiUrl($endpoint, array $args = NULL) {
    if (isset($args)) {
      $args = array_map('rawurlencode', $args);
    }
    $path = sprintf(static::$endpointPaths[$endpoint], ...($args ?? static::$endpointPathParameters[$endpoint]));
    return Url::fromUri($this->baseUrl . static::API_BASE_PATH . $path)->setAbsolute();
  }

  /**
   * {@inheritdoc}
   */
  protected function request($method, Url $url, array $request_options = []): ResponseInterface {
    // Add default headers.
    $request_options = NestedArray::mergeDeep([
      RequestOptions::HEADERS => ['Accept' => 'application/vnd.api+json'],
      RequestOptions::COOKIES => $this->getSessionCookies(),
    ], $request_options);
    if (!empty($request_options[RequestOptions::JSON])) {
      if (!isset($request_options[RequestOptions::HEADERS]['Content-Type'])) {
        $request_options[RequestOptions::HEADERS]['Content-Type'] = 'application/vnd.api+json';
      }
    }
    return $this->jsonapiRequest($method, $url, $request_options);
  }

  /**
   * Find the links in a JSON:API links object with the given link relation.
   *
   * @param string $rel
   *   The link relation to search for.
   * @param array $links
   *   The JSON:API links object, represented as an array.
   *
   * @return array
   *   The links in the links object with the given link relation.
   */
  protected static function findLinksWithRel(string $rel, array $links) {
    return array_filter($links, function (array $link, string $name) use ($rel) {
      return ($link['rel'] ?? $name) === $rel;
    }, ARRAY_FILTER_USE_BOTH);
  }

  /**
   * Gets the migration collection.
   *
   * @return array
   *   A JSON:API document.
   */
  protected function getMigrationCollection() : array {
    $response = $this->request('GET', $this->apiUrl('migrationCollection'), []);
    $this->assertSame(200, $response->getStatusCode());
    $this->assertSame('application/vnd.api+json', $response->getHeader('Content-Type')[0]);
    return Json::decode((string) $response->getBody());
  }

  /**
   * Attempts to find and return the requested migration from the collection.
   *
   * @param string $migration_label
   *   The migration to find.
   * @param array $migration_collection_document
   *   A decoded migration collection response.
   *
   * @return array|null
   *   The requested migration resource object, if it exists.
   */
  protected static function getMigrationResourceObject(string $migration_label, array $migration_collection_document) : ?array {
    foreach ($migration_collection_document['data'] as $migration_resource_object) {
      if ($migration_resource_object['attributes']['label'] === $migration_label) {
        return $migration_resource_object;
      }
    }
    return NULL;
  }

  /**
   * Performs the preselection step.
   *
   * Assumes the HTTP client is already authenticated.
   */
  protected function performMigrationPreselection() {
    // Grab the initial migrations collection.
    $document = $this->getMigrationCollection();

    // Find the preselection link.
    $preselection_link = current(static::findLinksWithRel('https://drupal.org/project/acquia_migrate#link-rel-preselect-migrations', $document['links']));
    $this->assertNotFalse($preselection_link);
    $preselection_url = $this->apiUrl('preselectMigrations');
    $this->assertSame($preselection_url->toString(), $preselection_link['href']);

    // Find the first resource object with a `skip` link.
    foreach ($document['data'] as $resource_object) {
      $update_links = static::findLinksWithRel('https://drupal.org/project/acquia_migrate#link-rel-update-resource', $resource_object['links']);
      $skip_link = current(array_filter($update_links, function (array $link) {
        return ($link['title'] ?? NULL) === 'Skip';
      }));
      if ($skip_link) {
        break;
      }
    }
    assert(isset($skip_link));

    // Skip the found migration by using the preselection link.
    $response = $this->request('POST', $preselection_url, [
      RequestOptions::HEADERS => [
        'Content-Type' => 'application/vnd.api+json; ext="https://jsonapi.org/ext/atomic"',
      ],
      RequestOptions::JSON => [
        'atomic:operations' => [
          [
            'op' => 'update',
            'data' => $skip_link['params']['data'],
          ],
        ],
      ],
    ]);
    $this->assertSame(204, $response->getStatusCode());

    // Fetch the migrations collection.
    $document = $this->getMigrationCollection();

    // The preselected migration resource should have been skipped.
    $skipped_resource_identifier = array_intersect_key($skip_link['params']['data'], array_flip(['type', 'id']));
    $this->assertTrue(array_reduce($document['data'], function (bool $was_skipped, array $resource_object) use ($skipped_resource_identifier) {
      return $was_skipped ?: $resource_object['type'] === $skipped_resource_identifier['type'] && $skipped_resource_identifier['id'] && $resource_object['attributes']['skipped'] === TRUE;
    }, FALSE), 'The preselected migration resource should have been skipped.');

    // Now that it is certain that the preselection succeeded, unskip the
    // skipped migration so that it does not cause unexpected results later on.
    // It is easier to test when following the skip/unskip defaults.
    $this->request('PATCH', Url::fromUri($skip_link['href']), [
      RequestOptions::JSON => [
        'data' => NestedArray::mergeDeep($skipped_resource_identifier, [
          'attributes' => [
            'skipped' => FALSE,
          ],
        ]),
      ],
    ]);
  }

  /**
   * Performs the initial migration.
   *
   * Assumes the HTTP client is already authenticated.
   */
  protected function performInitialMigration() {
    // Check that there is an `initial-import` link in the migration collection.
    $document = $this->getMigrationCollection();
    $this->assertArrayHasKey('initial-import', $document['links']);

    // First, do the initial import.
    $response = $this->request('POST', Url::fromUri($document['links']['initial-import']['href']));
    $document = Json::decode((string) $response->getBody());
    $next_link = NestedArray::getValue($document, ['links', 'next']);
    for ($requests = 0; $next_link && $requests < 100; $requests++) {
      $response = $this->request('GET', Url::fromUri($next_link['href']));
      $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());
      $document = Json::decode((string) $response->getBody());
      $next_link = NestedArray::getValue($document, ['links', 'next']);
    }
    $this->assertLessThan(100, $requests, 'An infinite loop was probably encountered.');

    // Check that the `initial-import` link in the migration collection has
    // disappeared.
    $document = $this->getMigrationCollection();
    $this->assertArrayNotHasKey('initial-import', $document['links']);
  }

}
