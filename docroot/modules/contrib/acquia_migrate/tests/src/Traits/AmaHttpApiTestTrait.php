<?php

declare(strict_types = 1);

namespace Drupal\Tests\acquia_migrate\Traits;

use Drupal\acquia_migrate\EventSubscriber\ServerTimingHeaderForResponseSubscriber;
use Drupal\acquia_migrate\MigrationFingerprinter;
use Drupal\acquia_migrate\MigrationRepository;
use Drupal\acquia_migrate\Recommendations;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\Cache;
use Drupal\Core\DrupalKernelInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Trait for basic routines required for testing AMA's HTTP API in kernel tests.
 */
trait AmaHttpApiTestTrait {

  /**
   * Returns the collection resource array with the given label.
   *
   * @param string $label
   *   The label of the collection.
   * @param array|null $collection_data
   *   The collection data to search in. Fetches the most recent collection
   *   response if ommitted.
   *
   * @return array|null
   *   The collection resource array, or NULL if it cannot be found.
   */
  protected function getCollection(string $label, array $collection_data = NULL): ?array {
    $collection_data = $collection_data ?? $this->getMigrationCollection();
    return array_reduce(
      $collection_data['data'],
      function ($carry, array $resource) use ($label) {
        if (($resource['attributes']['label'] ?? NULL) === $label) {
          return $resource;
        }
        return $carry;
      },
      NULL
    );
  }

  /**
   * Sets the last DB fingerprint compute time back to the past.
   */
  protected function setBackComputeTime(): void {
    // Set last compute time to past.
    $compute_max_age = new \DateInterval(MigrationFingerprinter::COMPUTE_MAX_AGE);
    $now = new \DateTime();
    $compute_time_in_past = $now->sub($compute_max_age)->sub($compute_max_age);
    \Drupal::state()->set(MigrationFingerprinter::KEY_LAST_FINGERPRINT_COMPUTE_TIME, $compute_time_in_past->format(DATE_RFC3339));
  }

  /**
   * Updates the DB generated time.
   */
  protected function applyNewGeneratedInfoTime(): void {
    // Set the source DB generated metadata.
    \Drupal::state()->set(
      Recommendations::KEY_RECENT_INFO,
      [
        'generated' => (new \DateTime())->sub((new \DateInterval('PT1S')))->format(DATE_RFC3339),
      ]
    );
  }

  /**
   * Performs the preselection step.
   */
  protected function performMigrationPreselection(): void {
    // Grab the initial migrations collection.
    $collection_data = $this->getMigrationCollection();

    // Find the preselection link.
    $preselection_link = $this->findLinkWithTitle('Pre-select migrations for initial import', $collection_data);
    $this->assertNotEmpty($preselection_link, 'Preselection link cannot be found');

    // Find the first resource objects with a `skip` link.
    $skip_links = array_reduce(
      $collection_data['data'],
      function (array $carry, array $resource) {
        if ($slip_link = $this->findLinkWithTitle('Skip', $resource)) {
          $carry[] = $slip_link;
        }
        return $carry;
      },
      []
    );
    $this->assertNotEmpty($skip_links, 'No skip link is present');

    // Unskip all the migration by using the skip links.
    $response = $this->requestAndHandle(
      parse_url($preselection_link['href'])['path'],
      'POST',
      Json::encode([
        'atomic:operations' => array_reduce(
          $skip_links,
          function (array $carry, array $skip_link) {
            $data = $skip_link['params']['data'];
            $data['attributes']['skipped'] = FALSE;
            $carry[] = [
              'op' => 'update',
              'data' => ['attributes' => ['skipped' => FALSE]] + $skip_link['params']['data'],
            ];
            return $carry;
          },
          []
        ),
      ]),
    );
    $this->assertSame(204, $response->getStatusCode(), 'Preselection failed.');
    // "Rebuild" service definitions and caches.
    $this->container->get('kernel')->rebuildContainer();

    // Refetch the migrations collection, and find the all resource objects with
    // an `unskip` link.
    $collection_data = $this->getMigrationCollection();
    $unskip_links = array_reduce(
      $collection_data['data'],
      function (array $carry, array $resource) {
        if ($unskip_link = $this->findLinkWithTitle('Unskip', $resource)) {
          $carry[] = $unskip_link;
        }
        return $carry;
      },
      []
    );
    $this->assertEmpty($unskip_links ?? NULL);

    $this->assertNotEmpty($this->findLinkWithTitle('Initial import', $collection_data));
  }

  /**
   * Finds a link based on the title in the given JSON:API array.
   *
   * @param string $title
   *   The title of the link in the resource array.
   * @param array $collection_document
   *   The resource array.
   *
   * @return array|null
   *   The link resource as array, or NULL if it cannot be found.
   */
  protected function findLinkWithTitle(string $title, array $collection_document): ?array {
    return array_reduce(
      $collection_document['links'] ?? [],
      function ($carry, array $link) use ($title) {
        if (!$carry && preg_match('/' . $title . '/', $link['title'] ?? '')) {
          return $link;
        }
        return $carry;
      },
      NULL
    );
  }

  /**
   * Performs the initial migration or the reimport configuration task.
   *
   * @param bool $reimport
   *   Whether a reimport should be performed or not. Defaults to FALSE.
   */
  protected function performInitialMigration(bool $reimport = FALSE): void {
    $label = $reimport
      ? 'Re-importing supporting configuration .*'
      : 'Initial import';
    // Check that there is an `initial-import` link in the migration collection.
    $document = $this->getMigrationCollection();
    $import_link = $this->findLinkWithTitle($label, $document);
    $this->assertNotEmpty($import_link);

    $this->executeBatch($import_link);

    // Check that the `initial-import` link in the migration collection has
    // disappeared.
    $document = $this->getMigrationCollection();
    $this->assertEmpty($this->findLinkWithTitle($label, $document));
  }

  /**
   * Executes a batch task.
   *
   * @param array $batch_link
   *   A link array fetched from a resource.
   * @param int $max_loop
   *   Maximum number of attempts.
   */
  protected function executeBatch(array $batch_link, int $max_loop = 30): void {
    // First, start the migrations.
    $response = $this->requestAndHandle(static::getRelativeUrl($batch_link), 'POST');
    $this->assertEquals(303, $response->getStatusCode(), (string) $response->getContent());
    $document = Json::decode((string) $response->getContent());
    $next_link = NestedArray::getValue($document, ['links', 'next']);

    $this->assertNotEmpty($next_link);

    for ($requests = 0; $next_link && $requests < $max_loop; $requests++) {
      try {
        $response = $this->requestAndHandle(static::getRelativeUrl($next_link));
      }
      catch (\Exception $e) {
        $this->fail($e->getMessage());
      }

      $this->assertSame(200, $response->getStatusCode(), (string) $response->getContent());
      $document = Json::decode((string) $response->getContent());
      $next_link = NestedArray::getValue($document, ['links', 'next']);
    }
    $this->assertNull($next_link, 'An infinite loop was probably encountered.');
  }

  /**
   * Returns an up-to-date migration collection JSON:API doc as an array.
   *
   * @return array
   *   An up-to-date migration collection JSON:API doc as array.
   */
  protected function getMigrationCollection(): array {
    // Reset migration repository.
    $repository = $this->container->get('acquia_migrate.migration_repository');
    assert($repository instanceof MigrationRepository);
    $repository->dropStaticCache();
    Cache::invalidateTags(['migration_plugins']);
    static::dropQueryLog();

    $response = $this->requestAndHandle('/acquia-migrate-accelerate/api/migrations');
    $this->assertEquals(200, $response->getStatusCode(), (string) $response->getContent());
    static::dropQueryLog();
    return Json::decode($response->getContent());
  }

  /**
   * Drops the query log.
   */
  protected static function dropQueryLog(): void {
    ServerTimingHeaderForResponseSubscriber::dropQueryLog();
  }

  /**
   * Creates a request.
   *
   * @param string $path
   *   Drupal-relative path of the request.
   * @param string $method
   *   The method. Defaults to 'GET'.
   * @param string $content
   *   The content of the request.
   *
   * @return \Symfony\Component\HttpFoundation\Request
   *   A Symfony request.
   */
  protected function request(string $path, $method = 'GET', string $content = NULL): Request {
    $request = Request::create(
      $path,
      $method,
      [], [], [], [],
      $content
    );
    $request->headers->set('Accept', 'application/vnd.api+json');
    if ($content) {
      $request->headers->set('Content-Type', 'application/vnd.api+json; ext="https://jsonapi.org/ext/atomic"');
    }
    else {
      $request->headers->set('Content-Type', 'application/vnd.api+json');
    }

    return $request;
  }

  /**
   * Creates a request and asks the actual kernel to handle it.
   *
   * @param string $path
   *   Drupal-relative path of the request.
   * @param string $method
   *   The method. Defaults to 'GET'.
   * @param string $content
   *   The content of the request.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response sent by the kernel.
   */
  protected function requestAndHandle(string $path, string $method = 'GET', string $content = NULL): Response {
    $request = $this->request($path, $method, $content);
    $kernel = $this->container->get('kernel');
    assert($kernel instanceof DrupalKernelInterface);
    static $session;
    if (!isset($session)) {
      $session = new Session();
      $session->setId($this->randomString());
    }
    $request->setSession($session);
    return $kernel->handle($request);
  }

  /**
   * Creates a relative URL from the provided JSON:API link.
   *
   * @param array $link
   *   A JSON:API link as array.
   *
   * @return string
   *   Relative URL.
   */
  protected static function getRelativeUrl(array $link): string {
    $parsed = parse_url($link['href']);
    $query = !empty($parsed['query']) ? '?' . $parsed['query'] : '';
    return $parsed['path'] . $query;
  }

}
