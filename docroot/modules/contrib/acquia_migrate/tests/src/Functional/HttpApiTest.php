<?php

namespace Drupal\Tests\acquia_migrate\Functional;

use Drupal\acquia_migrate\Batch\MigrationBatchCoordinator;
use Drupal\acquia_migrate\Clusterer\Heuristics\Other;
use Drupal\acquia_migrate\Controller\HttpApi;
use Drupal\acquia_migrate\Migration;
use Drupal\acquia_migrate\Plugin\migrate\id_map\SqlWithCentralizedMessageStorage;
use Drupal\acquia_migrate\UriDefinitions;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Database\Database;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Url;
use Drupal\field\FieldStorageConfigInterface;
use Drupal\migrate\Plugin\MigrationInterface as MigrationPluginInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\TermInterface;
use Drupal\user\Entity\User;
use GuzzleHttp\RequestOptions;
use League\Uri\Contracts\UriException;
use League\Uri\UriTemplate;

/**
 * Tests the Acquia Migrate HTTP API.
 *
 * @group acquia_migrate
 * @group acquia_migrate__core
 * @coversDefaultClass \Drupal\acquia_migrate\Controller\HttpApi
 */
class HttpApiTest extends HttpApiTestBase {

  // @codingStandardsIgnoreStart
  /**
   * {@inheritdoc}
   *
   * @see https://www.drupal.org/project/drupal/issues/2890844
   * @see https://www.drupal.org/project/drupal/issues/2890690
   * @see https://www.drupal.org/project/drupal/issues/2891073
   *
   * @todo Remove this, and fix the problems in \Drupal\migrate\MigrateLookup.
   * The lookup returns [0 => ['type' => NULL]] which causes the target_bundles
   * to be invalid in \Drupal\field\Plugin\migrate\process\d7\FieldInstanceSettings::transform().
   * The real root cause: config schema violations which cause d7_node_type map
   * tables to have "NULL" destination IDs. This migrate message was stored:
   *   node.type.article:third_party_settings.menu_ui.available_menus missing schema, node.type.article:third_party_settings.menu_ui.parent missing schema (/Users/wim.leers/Work/d8/core/lib/Drupal/Core/Config/Development/ConfigSchemaChecker.php:94)
   *
   * Note that this also affects the number of expected migration messages in
   * testEntityValidationErrorMessagesCollectionDetail().
   */
  protected $strictConfigSchema = FALSE;
  // @codingStandardsIgnoreEnd

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    // core/modules/migrate_drupal/tests/fixtures/drupal7.php is multilingual.
    'language',
  ];

  /**
   * Tests behaviors common to all endpoints.
   *
   * @dataProvider providerForTestCommonEndpointBehaviors
   */
  public function testCommonEndpointBehaviors($endpoint) {
    // Some endpoints need the field module to be installed. In practice, every
    // Drupal site has that installed. It's odd to be explicitly installing this
    // module here, but since this is testing the JSON:API spec compliance, it
    // is warranted.
    // @todo this is only necessary because the d7_user migration implicitly depends on the d7_field_instance migration, which is a bug in core
    $this->assertTrue($this->container->get('module_installer')->install(['field']));
    // To test the URL for processing a migration, a new migration process must
    // first be created so that the endpoint does not 404. It's necessary to
    // authenticate to before creating a new migration process. The test logs
    // out immediately after getting the new URL so that unauthenticated
    // behaviors can be tested.
    $this->drupalLogin($this->rootUser);
    $endpoint_url = $endpoint === 'migrationProcess'
      ? $this->createMigrationProcess($this->getMigrationImportUrl('User accounts'))
      : $this->apiUrl($endpoint);
    $this->drupalLogout();

    $response = $this->request('GET', $endpoint_url, []);
    // If the response status is a 405, none of the remaining assertions apply,
    // ensure there is an Allow header and finish the test.
    if ($response->getStatusCode() === 405) {
      $this->assertTrue($response->hasHeader('Allow'));
      return;
    }
    // The response was not 405, ensure a 403 since the user is not
    // authenticated.
    $this->assertSame(403, $response->getStatusCode());

    // Unfortunately, without enabling JSON:API, it's impossible to make this
    // assertion work without duplicating complicated code.
    /*$this->assertSame('application/vnd.api+json', $response->getHeader('Content-Type')[0]);*/

    // Authenticate subsequent requests with permission to administer
    // migrations.
    $this->drupalLogin($this->rootUser);

    if ($endpoint === 'migrationProcess') {
      // The old migration process URL will no longer work because each batch is
      // associated with a single session. Therefore a new URL must be created.
      // The old batch must be terminated, and the active batch lock must be
      // released.
      // @see \Drupal\acquia_migrate\EventSubscriber\InstantaneousBatchInterruptor::interruptMigrateExecutable()
      $this->container->get('plugin.manager.migration')
        ->createInstance('d7_user')
        ->interruptMigration(MigrationPluginInterface::RESULT_STOPPED);
      $this->container->get('lock.persistent')->release(MigrationBatchCoordinator::ACTIVE_BATCH);
      $endpoint_url = $this->createMigrationProcess($this->getMigrationImportUrl('User accounts'));
    }
    elseif ($endpoint === 'migrationPreview') {
      $this->performMigrationPreselection();
      $this->performInitialMigration();
      $endpoint_url->setOption('query', ['byOffset' => 0]);
    }

    $response = $this->request('GET', $endpoint_url, []);
    $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());
    $this->assertArrayHasKey($response->getHeader('Content-Type')[0], array_flip([
      'application/vnd.api+json',
      'application/vnd.api+json; ext="https://drupal.org/project/acquia_migrate#jsonapi-extension-uri-template"',
    ]));
    // Test Accept media type parameter requirement.
    $response = $this->request('GET', $endpoint_url, [
      RequestOptions::HEADERS => [
        'Accept' => 'text/html, application/vnd.api+json; unsupported=parameter',
      ],
    ]);
    $this->assertSame(406, $response->getStatusCode());
    // Test Content-Type media type parameter requirement if the endpoint
    // supports POST, PATCH, or DELETE request.
    $response = $this->request('OPTIONS', $endpoint_url);
    $this->assertTrue($response->hasHeader('Allow'));
    $allowed_unsafe_methods = array_intersect(['POST', 'PATCH', 'DELETE'], array_map('trim', explode(',', $response->getHeader('Allow')[0])));
    if (!empty($allowed_unsafe_methods)) {
      $response = $this->request(current($allowed_unsafe_methods), $endpoint_url, [
        RequestOptions::HEADERS => [
          'Content-Type' => 'application/json',
        ],
      ]);
      $this->assertSame(415, $response->getStatusCode());
    }
    if ($endpoint === 'migrationPreview' || $endpoint == 'migrationMapping') {
      return;
    }
    // Test sparse fieldsets.
    $response = $this->request('GET', $endpoint_url->setOption('query', ['fields[migration]' => 'label']), []);
    $this->assertSame(200, $response->getStatusCode());
    $this->assertArrayHasKey($response->getHeader('Content-Type')[0], array_flip([
      'application/vnd.api+json',
      'application/vnd.api+json; ext="https://drupal.org/project/acquia_migrate#jsonapi-extension-uri-template"',
    ]));
    $document = Json::decode((string) $response->getBody());
    $data = isset($document['data']['type']) ? [$document['data']] : ($document['data'] ?? []);
    foreach ($data as $resource_object) {
      // For every `migration` resource object in the response, ensure that
      // there are no other fields than `label`.
      if ($resource_object['type'] !== 'migration') {
        continue;
      }
      $this->assertEmpty(array_diff_key($resource_object['attributes'], array_flip(['label'])));
      $this->assertArrayNotHasKey('relationships', $resource_object);
    }
    // Test that query parameters other than `fields` trigger a 400 Bad Request
    // unless there is a filtering affordance on the endpoint.
    $query_parameters = ['sort', 'include'];
    if (empty(array_filter(Json::decode((string) $response->getBody())['links'], function (array $link) {
      return $link['rel'] ?? '' === UriDefinitions::LINK_REL_QUERY;
    }))) {
      array_push($query_parameters, 'filter');
    }
    foreach ($query_parameters as $parameter) {
      $response = $this->request('GET', $this->apiUrl($endpoint)->setOption('query', [$parameter => $this->randomString()]), []);
      $this->assertSame(400, $response->getStatusCode());
      $this->assertSame('application/vnd.api+json', $response->getHeader('Content-Type')[0]);
      $this->assertSame([
        'errors' => [
          [
            'code' => '400',
            'status' => 'Bad Request',
            'detail' => 'The `' . $parameter . '` query parameter is not allowed.',
            'source' => [
              'parameter' => $parameter,
            ],
          ],
        ],
      ], Json::decode((string) $response->getBody()));
    }
  }

  /**
   * Provides test cases for testing common behavior.
   */
  public function providerForTestCommonEndpointBehaviors() {
    foreach (static::$endpointPaths as $name => $path) {
      yield $name => [$name];
    }
  }

  /**
   * Tests the migrations collection API endpoint.
   */
  public function testMigrationsCollection() {
    $this->drupalLogin($this->rootUser);
    $this->performMigrationPreselection();
    $document = $this->getMigrationCollection();
    $start_migration_href = function ($migration_id) {
      return $this->apiUrl('migrationStart')->setOption('query', ['migrationId' => $migration_id])->toString();
    };
    $this->assertSame([
      'data' => [
        [
          'type' => 'migration',
          'id' => 'b2e96197b823e728ee5a6be88da8f74b-Language settings',
          'attributes' => [
            'label' => 'Language settings',
            'importedCount' => 0,
            'processedCount' => 0,
            'totalCount' => 9,
            'completed' => FALSE,
            'stale' => FALSE,
            'skipped' => FALSE,
            'lastImported' => NULL,
            'activity' => 'idle',
          ],
          'relationships' => [
            'dependencies' => [
              'data' => [],
            ],
            'consistsOf' => [
              'data' => [
                ['type' => 'migrationPlugin', 'id' => 'language (0 of 3)'],
                ['type' => 'migrationPlugin', 'id' => 'd7_language_negotiation_settings (0 of 1)'],
                ['type' => 'migrationPlugin', 'id' => 'd7_language_types (0 of 1)'],
                ['type' => 'migrationPlugin', 'id' => 'default_language (0 of 1)'],
                ['type' => 'migrationPlugin', 'id' => 'language_prefixes_and_domains (0 of 3)'],
              ],
            ],
          ],
          'links' => [
            'self' => [
              'href' => $this->apiUrl('migrationIndividual', ['b2e96197b823e728ee5a6be88da8f74b-Language settings'])
                ->toString(),
            ],
            'import' => [
              'href' => $start_migration_href('b2e96197b823e728ee5a6be88da8f74b-Language settings'),
              'title' => 'Import',
              'rel' => UriDefinitions::LINK_REL_START_BATCH_PROCESS,
            ],
            'skip' => [
              'href' => $this->apiUrl('migrationIndividual', ['b2e96197b823e728ee5a6be88da8f74b-Language settings'])
                ->toString(),
              'title' => 'Skip',
              'rel' => UriDefinitions::LINK_REL_UPDATE_RESOURCE,
              'params' => [
                'confirm' => "I'm sure, this does not need to be migrated, at least not for now.",
                'data' => [
                  'type' => 'migration',
                  'id' => 'b2e96197b823e728ee5a6be88da8f74b-Language settings',
                  'attributes' => [
                    'skipped' => TRUE,
                  ],
                ],
              ],
            ],
          ],
        ],
        [
          'type' => 'migration',
          'id' => 'cfddcadb31b559c03c57b20372420c1f-Shared structure for menus',
          'attributes' => [
            'label' => 'Shared structure for menus',
            'importedCount' => 0,
            'processedCount' => 0,
            'totalCount' => 7,
            'completed' => FALSE,
            'stale' => FALSE,
            'skipped' => FALSE,
            'lastImported' => NULL,
            'activity' => 'idle',
          ],
          'relationships' => [
            'dependencies' => [
              'data' => [
                [
                  'type' => 'migration',
                  'id' => 'b2e96197b823e728ee5a6be88da8f74b-Language settings',
                  'meta' => [
                    'dependencyReasons' => [
                      'language',
                    ],
                  ],
                ],
              ],
            ],
            'consistsOf' => [
              'data' => [
                ['type' => 'migrationPlugin', 'id' => 'd7_menu (0 of 6)'],
                ['type' => 'migrationPlugin', 'id' => 'd7_language_content_menu_settings (0 of 1)'],
              ],
            ],
          ],
          'links' => [
            'self' => [
              'href' => $this->apiUrl('migrationIndividual', ['cfddcadb31b559c03c57b20372420c1f-Shared structure for menus'])->toString(),
            ],
            'skip' => [
              'href' => $this->apiUrl('migrationIndividual', ['cfddcadb31b559c03c57b20372420c1f-Shared structure for menus'])->toString(),
              'title' => 'Skip',
              'rel' => UriDefinitions::LINK_REL_UPDATE_RESOURCE,
              'params' => [
                'confirm' => "I'm sure, this does not need to be migrated, at least not for now.",
                'data' => [
                  'type' => 'migration',
                  'id' => 'cfddcadb31b559c03c57b20372420c1f-Shared structure for menus',
                  'attributes' => [
                    'skipped' => TRUE,
                  ],
                ],
              ],
            ],
          ],
        ],
        [
          'type' => 'migration',
          'id' => 'dbdd6377389228728e6ab594c50ad011-User accounts',
          'attributes' => [
            'label' => 'User accounts',
            'importedCount' => 0,
            'processedCount' => 0,
            'totalCount' => 3,
            'completed' => FALSE,
            'stale' => FALSE,
            'skipped' => FALSE,
            'lastImported' => NULL,
            'activity' => 'idle',
          ],
          'relationships' => [
            'dependencies' => [
              'data' => [],
            ],
            'consistsOf' => [
              'data' => [
                ['type' => 'migrationPlugin', 'id' => 'd7_user_role'],
                ['type' => 'migrationPlugin', 'id' => 'user_picture_entity_display'],
                ['type' => 'migrationPlugin', 'id' => 'user_picture_entity_form_display'],
                ['type' => 'migrationPlugin', 'id' => 'd7_user (0 of 3)'],
              ],
            ],
          ],
          'links' => [
            'self' => [
              'href' => $this->apiUrl('migrationIndividual', ['dbdd6377389228728e6ab594c50ad011-User accounts'])->toString(),
            ],
            'import' => [
              'href' => $start_migration_href('dbdd6377389228728e6ab594c50ad011-User accounts'),
              'title' => 'Import',
              'rel' => UriDefinitions::LINK_REL_START_BATCH_PROCESS,
            ],
            'skip' => [
              'href' => $this->apiUrl('migrationIndividual', ['dbdd6377389228728e6ab594c50ad011-User accounts'])->toString(),
              'title' => 'Skip',
              'rel' => UriDefinitions::LINK_REL_UPDATE_RESOURCE,
              'params' => [
                'confirm' => "I'm sure, this does not need to be migrated, at least not for now.",
                'data' => [
                  'type' => 'migration',
                  'id' => 'dbdd6377389228728e6ab594c50ad011-User accounts',
                  'attributes' => [
                    'skipped' => TRUE,
                  ],
                ],
              ],
            ],
            'preview-unmet-requirement:0' => [
              'href' => 'https://drupal.org/project/acquia_migrate#application-concept-no-unprocessed-supporting-configuration',
              'title' => 'Not all supporting configuration has been processed yet: User accounts (specifically: d7_user_role, user_picture_entity_display, user_picture_entity_form_display).',
              'rel' => UriDefinitions::LINK_REL_UNMET_REQUIREMENT,
            ],
            'field-mapping' => [
              'href' => $this->apiUrl('migrationMapping', ['dbdd6377389228728e6ab594c50ad011-User accounts'])->toString(),
              'title' => 'View mapping',
              'rel' => UriDefinitions::LINK_REL_MAPPING,
            ],
          ],
        ],
        [
          'type' => 'migration',
          'id' => 'b11ec035a0ea55f7bf0af42f84083be8-Site configuration',
          'attributes' => [
            'label' => 'Site configuration',
            'importedCount' => 0,
            'processedCount' => 0,
            'totalCount' => 39,
            'completed' => FALSE,
            'stale' => FALSE,
            'skipped' => FALSE,
            'lastImported' => NULL,
            'activity' => 'idle',
          ],
          'relationships' => [
            'dependencies' => [
              'data' => [],
            ],
            'consistsOf' => [
              'data' => [
                ['type' => 'migrationPlugin', 'id' => 'action_settings (0 of 1)'],
                ['type' => 'migrationPlugin', 'id' => 'd7_dblog_settings (0 of 1)'],
                ['type' => 'migrationPlugin', 'id' => 'd7_global_theme_settings (0 of 1)'],
                ['type' => 'migrationPlugin', 'id' => 'd7_syslog_settings (0 of 1)'],
                ['type' => 'migrationPlugin', 'id' => 'd7_system_authorize (0 of 1)'],
                ['type' => 'migrationPlugin', 'id' => 'd7_system_cron (0 of 1)'],
                ['type' => 'migrationPlugin', 'id' => 'd7_system_date (0 of 1)'],
                ['type' => 'migrationPlugin', 'id' => 'd7_system_file (0 of 1)'],
                ['type' => 'migrationPlugin', 'id' => 'd7_system_mail (0 of 1)'],
                ['type' => 'migrationPlugin', 'id' => 'd7_system_performance (0 of 1)'],
                ['type' => 'migrationPlugin', 'id' => 'd7_theme_settings:claro (0 of 1)'],
                ['type' => 'migrationPlugin', 'id' => 'd7_theme_settings:stark (0 of 1)'],
                ['type' => 'migrationPlugin', 'id' => 'd7_user_flood (0 of 1)'],
                ['type' => 'migrationPlugin', 'id' => 'd7_user_mail (0 of 1)'],
                ['type' => 'migrationPlugin', 'id' => 'd7_user_settings (0 of 1)'],
                ['type' => 'migrationPlugin', 'id' => 'system_image (0 of 1)'],
                ['type' => 'migrationPlugin', 'id' => 'system_image_gd (0 of 1)'],
                ['type' => 'migrationPlugin', 'id' => 'system_logging (0 of 1)'],
                ['type' => 'migrationPlugin', 'id' => 'system_maintenance (0 of 1)'],
                ['type' => 'migrationPlugin', 'id' => 'system_rss (0 of 1)'],
                ['type' => 'migrationPlugin', 'id' => 'system_site (0 of 1)'],
                ['type' => 'migrationPlugin', 'id' => 'd7_action (0 of 18)'],
              ],
            ],
          ],
          'links' => [
            'self' => [
              'href' => $this->apiUrl('migrationIndividual', ['b11ec035a0ea55f7bf0af42f84083be8-Site configuration'])->toString(),
            ],
            'import' => [
              'href' => $start_migration_href('b11ec035a0ea55f7bf0af42f84083be8-Site configuration'),
              'title' => 'Import',
              'rel' => UriDefinitions::LINK_REL_START_BATCH_PROCESS,
            ],
            'skip' => [
              'href' => $this->apiUrl('migrationIndividual', ['b11ec035a0ea55f7bf0af42f84083be8-Site configuration'])->toString(),
              'title' => 'Skip',
              'rel' => UriDefinitions::LINK_REL_UPDATE_RESOURCE,
              'params' => [
                'confirm' => "I'm sure, this does not need to be migrated, at least not for now.",
                'data' => [
                  'type' => 'migration',
                  'id' => 'b11ec035a0ea55f7bf0af42f84083be8-Site configuration',
                  'attributes' => [
                    'skipped' => TRUE,
                  ],
                ],
              ],
            ],
          ],
        ],
      ],
      'links' => [
        'self' => [
          'href' => $this->apiUrl('migrationCollection')->toString(),
        ],
        'bulk-update-migrations' => [
          'href' => $this->apiUrl('migrationCollection')->toString(),
          'title' => 'Update migrations in bulk',
          'rel' => 'https://drupal.org/project/acquia_migrate#link-rel-bulk-update-migrations',
          'type' => 'application/vnd.api+json; ext="https://jsonapi.org/ext/atomic"',
        ],
        'stale-data' => [
          'href' => Url::fromRoute('acquia_migrate.api.stale_data')->setAbsolute()->toString(),
          'title' => 'Check for updates',
          'rel' => UriDefinitions::LINK_REL_STALE_DATA,
        ],
        'initial-import' => [
          'href' => Url::fromRoute('acquia_migrate.api.migration.import.initial')->setAbsolute()->toString(),
          'title' => 'Initial import',
          'rel' => UriDefinitions::LINK_REL_START_BATCH_PROCESS,
        ],
      ],
      'meta' => [
        'sourceSyncTime' => NULL,
        'controllingSession' => NULL,
      ],
    ], $document);

    $this->assertInitialImport(FALSE, 21, 0, 0);

    return $document;
  }

  /**
   * @covers ::migrationMappingGet
   * @covers ::migrationMappingPatch
   * @covers ::migrationMappingDropSourceField
   * @covers ::migrationMappingRevertOverrides
   * @depends testMigrationsCollection
   */
  public function testMigrationMapping(array $document) {
    // Only fieldable entity types get previews.
    $this->assertTrue($this->container->get('module_installer')->install(['field']));

    $this->assertSame('User accounts', $document['data'][2]['attributes']['label']);
    $this->assertArrayHasKey('field-mapping', $document['data']['2']['links']);

    $this->drupalLogin($this->rootUser);

    // Migration mapping: follow provided link.
    $response = $this->request('GET', Url::fromUri($document['data']['2']['links']['field-mapping']['href']));
    $resource_object = Json::decode((string) $response->getBody())['data'];
    $this->assertSame('migrationMappingForContentEntityType', $resource_object['type']);
    $this->assertArrayHasKey('override', $resource_object['links']);

    // Override the mapping.
    $response = $this->request('PATCH', Url::fromUri($resource_object['links']['override']['href']), [
      RequestOptions::BODY => Json::encode(['data' => $resource_object['links']['override']['params']['data']]),
    ]);
    $this->assertSame(204, $response->getStatusCode());

    // `drop-source-field` link with URI template suggestions appears.
    $response = $this->request('GET', Url::fromUri($document['data']['2']['links']['field-mapping']['href']));
    $resource_object = Json::decode((string) $response->getBody())['data'];
    $this->assertSame('migrationMappingForContentEntityType', $resource_object['type']);
    $this->assertArrayNotHasKey('override', $resource_object['links']);
    $this->assertArrayHasKey('revert', $resource_object['links']);
    $this->assertArrayHasKey('drop-source-field', $resource_object['links']);
    $this->assertCount(18, $resource_object['links']['drop-source-field']['uri-template:suggestions']['options']);
    $this->assertSame('destinationFieldName', $resource_object['links']['drop-source-field']['uri-template:suggestions']['variable']);
    $this->assertSame([
      'label' => 'Password',
      'value' => 'pass',
    ], $resource_object['links']['drop-source-field']['uri-template:suggestions']['options'][2]);

    // Follow 'drop-source-field' link, use one of the suggestions.
    $uri_template = new UriTemplate($resource_object['links']['drop-source-field']['uri-template:href']);
    $uri_template_variables = [
      'destinationFieldName' => ['pass'],
    ];
    try {
      $expanded_url = (string) $uri_template->expand($uri_template_variables);
    }
    catch (UriException $e) {
      $this->fail($e->getMessage());
    }
    $response = $this->request('POST', Url::fromUri($expanded_url));
    $this->assertSame(204, $response->getStatusCode());

    // `revert-field-overrides` link with URI template suggestions appears; one
    // URI template suggestion for `drop-source-field` disappears.
    $response = $this->request('GET', Url::fromUri($document['data']['2']['links']['field-mapping']['href']));
    $resource_object = Json::decode((string) $response->getBody())['data'];
    $this->assertSame('migrationMappingForContentEntityType', $resource_object['type']);
    $this->assertArrayNotHasKey('override', $resource_object['links']);
    $this->assertArrayHasKey('revert', $resource_object['links']);
    $this->assertArrayHasKey('drop-source-field', $resource_object['links']);
    $this->assertCount(17, $resource_object['links']['drop-source-field']['uri-template:suggestions']['options']);
    $this->assertArrayHasKey('revert-field-overrides', $resource_object['links']);
    $this->assertSame('destinationFieldName', $resource_object['links']['revert-field-overrides']['uri-template:suggestions']['variable']);
    $this->assertCount(1, $resource_object['links']['revert-field-overrides']['uri-template:suggestions']['options']);
    $this->assertSame([
      // @todo \Drupal\acquia_migrate\MigrationMappingViewer::getSourceOnlyFields
      // is currently unable to determine the destination field name label for a
      // source-only field; making that possible would improve UX.
      'label' => 'pass',
      'value' => 'pass',
    ], $resource_object['links']['revert-field-overrides']['uri-template:suggestions']['options'][0]);

    // Follow 'drop-source-field' link, NOT using one of the suggestions, should
    // result in helpful error.
    $uri_template = new UriTemplate($resource_object['links']['drop-source-field']['uri-template:href']);
    $uri_template_variables = [
      'destinationFieldName' => ['likes_llamas'],
    ];
    try {
      $expanded_url = (string) $uri_template->expand($uri_template_variables);
    }
    catch (UriException $e) {
      $this->fail($e->getMessage());
    }
    $response = $this->request('POST', Url::fromUri($expanded_url));
    $this->assertSame(400, $response->getStatusCode());
    $error_document = Json::decode((string) $response->getBody());
    $this->assertSame('The `likes_llamas` destination field name is absent in the original migration plugin definition.', $error_document['errors'][0]['detail']);

    // Revert the mapping overrides.
    $response = $this->request('PATCH', Url::fromUri($resource_object['links']['revert']['href']), [
      RequestOptions::BODY => Json::encode(['data' => $resource_object['links']['revert']['params']['data']]),
    ]);
    $this->assertSame(204, $response->getStatusCode());

    // The `revert`, `drop-source-field` and `revert-field-overrides` links
    // disappear.
    $response = $this->request('GET', Url::fromUri($document['data']['2']['links']['field-mapping']['href']));
    $resource_object = Json::decode((string) $response->getBody())['data'];
    $this->assertSame('migrationMappingForContentEntityType', $resource_object['type']);
    $this->assertArrayHasKey('override', $resource_object['links']);
    $this->assertArrayNotHasKey('revert', $resource_object['links']);
    $this->assertArrayNotHasKey('drop-source-field', $resource_object['links']);
    $this->assertArrayNotHasKey('revert-field-overrides', $resource_object['links']);

    // After data has been migrated, the 'override' link becomes a no-op.
    $this->doProcessMigrationAction($this->getMigrationImportUrl('User accounts'));
    $response = $this->request('GET', Url::fromUri($document['data']['2']['links']['field-mapping']['href']));
    $resource_object = Json::decode((string) $response->getBody())['data'];
    $this->assertArrayHasKey('override', $resource_object['links']);
    $this->assertSame('https://drupal.org/project/acquia_migrate#application-concept-no-imported-data', $resource_object['links']['override']['href']);
    $this->assertSame('Overriding a field mapping is not allowed when data has already been imported. Only after rolling back already migrated data you can override the mapping.', $resource_object['links']['override']['title']);
  }

  /**
   * @covers ::migrationRowPreview
   * @covers \Drupal\acquia_migrate\MigrationPreviewer::getRowToPreview
   * @covers \Drupal\acquia_migrate\MigrationPreviewer::migrationRowToMigrationPreviewResourceObject
   * @depends testMigrationsCollection
   */
  public function testMigrationPreviewRow(array $document) {
    // @see \Drupal\acquia_migrate\Plugin\migrate\destination\AcquiaMigrateUser::import()
    $user_one_name = User::load(1)->getAccountName();

    // Only fieldable entity types get previews.
    $this->assertTrue($this->container->get('module_installer')->install(['field']));

    $this->assertSame('User accounts', $document['data']['2']['attributes']['label']);
    $this->assertArrayHasKey('preview-unmet-requirement:0', $document['data']['2']['links']);
    $this->assertArrayNotHasKey('preview-by-offset', $document['data']['2']['links']);
    $this->assertArrayNotHasKey('preview-by-url', $document['data']['2']['links']);

    $this->drupalLogin($this->rootUser);

    // Mimic the application behavior: automatically perform the initial
    // migration, then update the dashboard: we should be ready for previews.
    $this->performMigrationPreselection();
    $this->performInitialMigration();
    $document = $this->getMigrationCollection();

    $this->assertSame('User accounts', $document['data']['2']['attributes']['label']);
    $this->assertArrayNotHasKey('preview-unmet-requirement:0', $document['data']['2']['links']);
    $this->assertArrayHasKey('preview-by-offset', $document['data']['2']['links']);
    $this->assertArrayHasKey('preview-by-url', $document['data']['2']['links']);

    // Preview by offset: follow provided link.
    $response = $this->request('GET', Url::fromUri($document['data']['2']['links']['preview-by-offset']['href']));
    $resource_object = Json::decode((string) $response->getBody())['data'];
    $this->assertSame('migrationPreview', $resource_object['type']);
    $this->assertSame('ephemeral', $resource_object['id']);
    $this->assertSame(['raw', 'html'], array_keys($resource_object['attributes']));
    $this->assertSame([
      'sourceFieldName',
      'destinationFieldName',
      'sourceValue',
      'destinationValue',
      'sourceValueSimplified',
      'destinationValueSimplified',
    ], array_keys($resource_object['attributes']['raw'][0]));
    $this->assertSame($user_one_name, $resource_object['attributes']['raw'][1]['destinationValueSimplified']);
    $this->assertSame(['sourceMigration'], array_keys($resource_object['relationships']));
    $this->assertSame('dbdd6377389228728e6ab594c50ad011-User accounts', $resource_object['relationships']['sourceMigration']['data']['id']);
    $this->assertSame(['next'], array_keys($resource_object['links']));
    $this->assertSame('Preview next row', $resource_object['links']['next']['title']);

    // Preview by source URL.
    $uri_template = new UriTemplate($document['data']['2']['links']['preview-by-url']['uri-template:href']);
    $uri_template_variables = [
      'byUrl' => ['/user/3'],
    ];
    try {
      $filtered_url = (string) $uri_template->expand($uri_template_variables);
    }
    catch (UriException $e) {
      $this->fail($e->getMessage());
    }
    $response = $this->request('GET', Url::fromUri($filtered_url));
    $resource_object = Json::decode((string) $response->getBody())['data'];
    $this->assertSame('Bob', $resource_object['attributes']['raw'][1]['destinationValueSimplified']);
    $this->assertArrayNotHasKey('links', $resource_object);

    // After importing the User accounts migration, previews should still be
    // possible.
    $this->doProcessMigrationAction($this->getMigrationImportUrl('User accounts'));
    $response = $this->request('GET', Url::fromUri($document['data']['2']['links']['self']['href']));
    $resource_object = Json::decode((string) $response->getBody())['data'];
    $this->assertArrayHasKey('preview-by-offset', $resource_object['links']);
    $this->assertArrayHasKey('preview-by-url', $resource_object['links']);
  }

  /**
   * Tests the endpoint which skips content types before initial import.
   */
  public function testMigrationPreselection() {
    $this->drupalLogin($this->rootUser);

    $this->performMigrationPreselection();

    // Fetch the migrations collection.
    $response = $this->request('GET', $this->apiUrl('migrationCollection'), []);
    $this->assertSame(200, $response->getStatusCode());
    $this->assertSame('application/vnd.api+json', $response->getHeader('Content-Type')[0]);
    $document = Json::decode((string) $response->getBody());

    // The preselection link should not be available after making an initial
    // preselection.
    $preselection_link = current(static::findLinksWithRel('https://drupal.org/project/acquia_migrate#link-rel-preselect-migrations', $document['links']));
    $this->assertFalse($preselection_link, 'The preselection link should not be available after making an initial preselection.');

    // The initial-import link should be available after making an initial
    // preselection.
    $initial_import_link = current(static::findLinksWithRel('https://drupal.org/project/acquia_migrate#link-rel-start-batch-process', $document['links']));
    $this->assertIsArray($initial_import_link, 'The initial-import link should be available after making an initial preselection.');
    $this->assertSame('Initial import', $initial_import_link['title']);
  }

  /**
   * Tests the endpoint which begins the process of running a migration.
   */
  public function testMigrationProcessingFlow() {
    // There are fields on the "user" entity type that require some additional
    // modules to be installed if we want to avoid migration errors.
    $this->assertTrue($this->container->get('module_installer')->install(['file', 'node', 'image'], TRUE));

    // Prior to starting the migration processing flow, ensure that user 'Bob'
    // does not exist. This will be used later to validation that the migration
    // being processed (d7_users, see ::getMigrationStartUrl()) actually
    // imported new users. Bob is a user provided by the core Drupal 7 DB
    // fixture.
    $bob_count_premigration = \Drupal::entityQuery('user')
      ->condition('mail', 'bob@local.host')
      ->count()
      ->execute();
    $this->assertSame(0, intval($bob_count_premigration));

    $this->drupalLogin($this->rootUser);

    // No migration has run yet, so there cannot be migration messages nor
    // metadata about the last import.
    $response = $this->request('GET', $this->apiUrl('migrationIndividual', ['dbdd6377389228728e6ab594c50ad011-User accounts']), []);
    $this->assertSame(200, $response->getStatusCode());
    $this->assertSame('application/vnd.api+json', $response->getHeader('Content-Type')[0]);
    $resource_object = Json::decode((string) $response->getBody())['data'];
    $this->assertSame(NULL, $resource_object['attributes']['lastImported']);
    $this->assertArrayNotHasKey('migration-messages', $resource_object['links']);

    // No migration messages prior to running the  migration.
    $response = $this->request('GET', $this->apiUrl('migrationMessageCollection'), []);
    $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());
    $this->assertSame('application/vnd.api+json; ext="https://drupal.org/project/acquia_migrate#jsonapi-extension-uri-template"', $response->getHeader('Content-Type')[0]);
    $document = Json::decode((string) $response->getBody());
    $this->assertEmpty($document['data']);

    // Perform the initial migration, but erase whatever messages were generated
    // by it: that'snot what we're testing here.
    $this->performMigrationPreselection();
    $this->performInitialMigration();
    \Drupal::database()->delete(SqlWithCentralizedMessageStorage::CENTRALIZED_MESSAGE_TABLE)->execute();

    // A POST is sent with an empty request body.
    $response = $this->request('POST', $this->getMigrationImportUrl('User accounts'));
    // A successful request results in 303, the client should GET the Location
    // URL.
    $this->assertSame(303, $response->getStatusCode(), (string) $response->getBody());
    $this->assertSame('application/vnd.api+json', $response->getHeader('Content-Type')[0]);
    $this->assertTrue($response->hasHeader('Location'));
    // The installation/setup process creates some number of batches. Instead of
    // asserting an exact URL, this tests against a regex of the expected URL
    // to allow for different batch IDs.
    $batch_href = $this->apiUrl('migrationProcess', ['__DIGITS__'])->toString();
    $location_href = Url::fromUri($response->getHeader('Location')[0])->toString();
    $href_regex = str_replace('__DIGITS__', '\d+', preg_quote($batch_href, '/'));
    $this->assertRegExp("/$href_regex/", $location_href, $href_regex);
    // The Location URL is also available as a `next` link in the response
    // document.
    $document = Json::decode((string) $response->getBody());
    $key_exists = FALSE;
    $next_link = NestedArray::getValue($document, ['links', 'next'], $key_exists);
    $this->assertTrue($key_exists);
    // The Location and `next` link must always be the same.
    $this->assertSame($next_link['href'], $response->getHeader('Location')[0]);
    // Following the location URL should result in the batch being processed.
    // While the response contains a `next` link, the batch is not complete and
    // the client should be able to follow those `next` links until the batch
    // is done.
    for ($requests = 0; $next_link && $requests < 100; $requests++) {
      $response = $this->request('GET', Url::fromUri($next_link['href']));
      $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());
      $document = Json::decode((string) $response->getBody());
      $next_link = NestedArray::getValue($document, ['links', 'next']);
    }
    $this->assertLessThan(100, $requests, 'An infinite loop was probably encountered.');

    // The migration processing is nominally over. If it succeeded, a new user
    // with the email bob@local.host should have been created.
    $bob_count_postmigration = \Drupal::entityQuery('user')
      ->condition('mail', 'bob@local.host')
      ->count()
      ->execute();
    $this->assertNotSame($bob_count_premigration, $bob_count_postmigration);

    // Only validation error migration messages after running the  migration.
    $response = $this->request('GET', $this->apiUrl('migrationMessageCollection'), []);
    $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());
    $this->assertSame('application/vnd.api+json; ext="https://drupal.org/project/acquia_migrate#jsonapi-extension-uri-template"', $response->getHeader('Content-Type')[0]);
    $messages_document = Json::decode((string) $response->getBody());
    $this->assertNotEmpty($messages_document['data']);
    $this->assertTrue(array_reduce($messages_document['data'], function (bool $carry, array $resource_object) {
      return $carry && $resource_object['attributes']['messageCategory'] === HttpApi::MESSAGE_CATEGORY_ENTITY_VALIDATION;
    }, TRUE));

    // There are migration messages, so there should be links to those error
    // messages in the migrations collection.
    $document = $this->getMigrationCollection();
    $this->assertArrayHasKey('migration-messages', $document['links']);
    $this->assertRegExp('/' . preg_quote('Total errors: ' . count($messages_document['data']), '/') . '/', $document['links']['migration-messages']['title']);

    // The user accounts migration has run, so verify a link to generated
    // migratiopn messages exists and that metadata about the last import is
    // present.
    $response = $this->request('GET', $this->apiUrl('migrationIndividual', ['dbdd6377389228728e6ab594c50ad011-User accounts']), []);
    $this->assertSame(200, $response->getStatusCode());
    $this->assertSame('application/vnd.api+json', $response->getHeader('Content-Type')[0]);
    $resource_object = Json::decode((string) $response->getBody())['data'];
    $this->assertNotSame(NULL, $resource_object['attributes']['lastImported']);
    $this->assertArrayHasKey('startTime', $resource_object['attributes']['lastImported']);
    $this->assertStringMatchesFormat('%d-%d-%dT%d:%d:%d+%d:%d', $resource_object['attributes']['lastImported']['startTime']);
    $this->assertArrayHasKey('endTime', $resource_object['attributes']['lastImported']);
    $this->assertStringMatchesFormat('%d-%d-%dT%d:%d:%d+%d:%d', $resource_object['attributes']['lastImported']['endTime']);
    $this->assertArrayHasKey('duration', $resource_object['attributes']['lastImported']);
    $this->assertGreaterThanOrEqual(0, $resource_object['attributes']['lastImported']['duration']);
    $this->assertArrayHasKey('migration-messages', $resource_object['links']);
    $this->assertSame('3', $resource_object['links']['migration-messages']['title']);

    // Since there are migration messages, there should also be links on
    // individual migrations to the messages collection pre-filtered by
    // migration ID and no link when there are no messages. To validate that,
    // a count of messages aggregated by migration ID should be generated.
    $message_counts = array_reduce($messages_document['data'], function (array $counts, array $message) {
      $source_migration_id = $message['relationships']['sourceMigration']['data']['id'];
      $counts[$source_migration_id] = !empty($counts[$source_migration_id])
        ? $counts[$source_migration_id] + 1
        : 1;
      return $counts;
    }, []);
    // Using the aggregated counts, validate that the links are presented or not
    // presented based on existence of messages for each migration.
    array_walk($document['data'], function ($migration) use ($message_counts) {
      if (!empty($message_counts[$migration['id']])) {
        $this->assertArrayHasKey('migration-messages', $migration['links']);
        $this->assertSame("{$message_counts[$migration['id']]}", $migration['links']['migration-messages']['title']);
      }
      else {
        $this->assertArrayNotHasKey('migration-messages', $migration['links']);
      }
    });
  }

  /**
   * Tests basic rollback functionality.
   */
  public function testMigrationRollback() {
    $this->assertTrue($this->container->get('module_installer')->install(['taxonomy'], TRUE));

    $term_count_query = \Drupal::entityQuery('taxonomy_term')->count();
    $term_count_pre_import = intval($term_count_query->execute());
    $this->assertSame(0, $term_count_pre_import);

    $this->drupalLogin($this->rootUser);

    $this->performMigrationPreselection();

    $this->doProcessMigrationAction($this->getMigrationImportUrl('Language settings'));
    $this->doProcessMigrationAction($this->getMigrationImportUrl('Shared structure for taxonomy terms'));
    $this->doProcessMigrationAction($this->getMigrationImportUrl('Tags taxonomy terms'));

    $term_count_post_import = intval($term_count_query->execute());
    $this->assertGreaterThan($term_count_pre_import, $term_count_post_import);

    $response = $this->request('GET', $this->apiUrl('migrationIndividual', ['8638466f6e83d1f03465a4864cbb0461-Tags taxonomy terms']), []);
    $resource_object = Json::decode((string) $response->getBody())['data'];
    $this->assertSame('array', gettype($resource_object['attributes']['lastImported']));

    $this->doProcessMigrationAction($this->getMigrationRollbackUrl('Tags taxonomy terms'));

    $term_count_post_rollback = intval($term_count_query->execute());
    $this->assertSame($term_count_pre_import, intval($term_count_post_rollback));

    $response = $this->request('GET', $this->apiUrl('migrationIndividual', ['8638466f6e83d1f03465a4864cbb0461-Tags taxonomy terms']), []);
    $resource_object = Json::decode((string) $response->getBody())['data'];
    $this->assertSame(NULL, $resource_object['attributes']['lastImported']);
  }

  /**
   * Tests basic rollback functionality.
   */
  public function testMigrationRollbackAndImport() {
    $this->assertTrue($this->container->get('module_installer')->install(['taxonomy'], TRUE));

    $term_count_query = \Drupal::entityQuery('taxonomy_term')->count();
    $term_count_pre_import = intval($term_count_query->execute());
    $this->assertSame(0, $term_count_pre_import);

    $this->drupalLogin($this->rootUser);

    $this->performMigrationPreselection();

    $this->doProcessMigrationAction($this->getMigrationImportUrl('Language settings'));
    $this->doProcessMigrationAction($this->getMigrationImportUrl('Shared structure for taxonomy terms'));
    $this->doProcessMigrationAction($this->getMigrationImportUrl('Tags taxonomy terms'));

    $term_count_post_import = intval($term_count_query->execute());
    $this->assertGreaterThan($term_count_pre_import, $term_count_post_import);

    $response = $this->request('GET', $this->apiUrl('migrationIndividual', ['8638466f6e83d1f03465a4864cbb0461-Tags taxonomy terms']), []);
    $resource_object = Json::decode((string) $response->getBody())['data'];
    $this->assertSame('array', gettype($resource_object['attributes']['lastImported']));
    $old_import_start_time = $resource_object['attributes']['lastImported']['startTime'];

    // Update the source database so that all taxonomy term names are the same.
    $connection = Database::getConnection('default', 'migrate_test');
    $connection->update('taxonomy_term_data')->fields([
      'name' => 'Little foot',
    ])->execute();

    sleep(2);
    $this->doProcessMigrationAction($this->getMigrationRollbackAndImportUrl('Tags taxonomy terms'));

    $term_count_post_rollback_import = intval($term_count_query->execute());
    $this->assertSame($term_count_post_import, intval($term_count_post_rollback_import));

    $terms = Term::loadMultiple(\Drupal::entityQuery('taxonomy_term')->execute());
    foreach ($terms as $term) {
      $this->assertSame('Little foot', $term->label());
    }

    $response = $this->request('GET', $this->apiUrl('migrationIndividual', ['8638466f6e83d1f03465a4864cbb0461-Tags taxonomy terms']), []);
    $resource_object = Json::decode((string) $response->getBody())['data'];
    $this->assertSame('array', gettype($resource_object['attributes']['lastImported']));
    $new_import_start_time = $resource_object['attributes']['lastImported']['startTime'];
    $this->assertNotEquals($old_import_start_time, $new_import_start_time);
  }

  /**
   * @covers ::messagesCollection
   */
  public function testMessagesCollection() {
    // Install file module. It provides migrations for which messages are
    // generated (until source files are moved to the default destination
    // directory 'sites/default/files target).
    $this->assertTrue($this->container->get('module_installer')->install(['file']));

    $this->drupalLogin($this->rootUser);

    // No migration messages prior to running the  migration.
    $response = $this->request('GET', $this->apiUrl('migrationMessageCollection'), []);
    $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());
    $this->assertSame('application/vnd.api+json; ext="https://drupal.org/project/acquia_migrate#jsonapi-extension-uri-template"', $response->getHeader('Content-Type')[0]);
    $document = Json::decode((string) $response->getBody());
    $this->assertEmpty($document['data']);

    $this->doProcessMigrationAction($this->getMigrationImportUrl('Public files'));

    // New migration messages after running the migration.
    $response = $this->request('GET', $this->apiUrl('migrationMessageCollection'), [
      RequestOptions::QUERY => [
        'fields' => [
          'migrationMessage' => 'sourceMigration,sourceMigrationPlugin,messageCategory,severity,message,solution',
        ],
      ],
    ]);
    $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());
    $this->assertSame('application/vnd.api+json; ext="https://drupal.org/project/acquia_migrate#jsonapi-extension-uri-template"', $response->getHeader('Content-Type')[0]);
    $document = Json::decode((string) $response->getBody());
    $this->assertCount(2, $document['data']);

    // Using the previous response, test filtering affordances. First, ensure
    // that the previous response had "Error" level messages.
    $this->assertNotEmpty(array_filter($document['data'], function ($resource_object) {
      return ($resource_object['attributes']['severity'] ?? NULL) === (string) RfcLogLevel::ERROR;
    }));
    // Check for a filtering link.
    $this->assertArrayHasKey('query', $document['links']);
    $filter_link = $document['links']['query'];
    $this->assertSame('https://drupal.org/project/acquia_migrate#link-rel-query', $filter_link['rel']);
    $this->assertArrayHasKey('uri-template:href', $filter_link);
    $uri_template = new UriTemplate($filter_link['uri-template:href']);
    // The response has a filter affordance, so look for a filter suggestion to
    // filter by message type.
    $severity_suggestion = array_values(array_filter($filter_link['uri-template:suggestions'] ?? [], function ($suggestion) {
      return $suggestion['label'] === 'Severity';
    }));
    $this->assertCount(1, $severity_suggestion);
    // A message type filter suggest was found; look for an option to filter
    // message down to only "Debug" level messages.
    $severity_option_debug = array_values(array_filter($severity_suggestion[0]['options'] ?? [], function ($option) {
      return $option['label'] === 'Debug';
    }));
    $this->assertCount(1, $severity_option_debug);
    // A filter option was found. Create a set of variables and then expand the
    // URI template to get a filtered URL.
    //
    // The following results in something like this:
    // @code
    // [
    //   'filter' => ':eq,severity,4',
    // ]
    // @endcode
    $uri_template_variables = [
      "{$severity_suggestion[0]['variable']}" => [
        implode(',', [
          $severity_suggestion[0]['operator'],
          $severity_suggestion[0]['field'],
          $severity_option_debug[0]['value'],
        ]),
      ],
    ];
    try {
      $filtered_url = (string) $uri_template->expand($uri_template_variables);
    }
    catch (UriException $e) {
      $this->fail($e->getMessage());
    }
    // Issue a request then ensure that the response collection no longer
    // contains any "Error" level messages that we earlier asserted were in the
    // collection.
    $response = $this->request('GET', Url::fromUri($filtered_url));
    $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());
    $this->assertSame('application/vnd.api+json; ext="https://drupal.org/project/acquia_migrate#jsonapi-extension-uri-template"', $response->getHeader('Content-Type')[0]);
    $filtered_document = Json::decode((string) $response->getBody());
    $this->assertEmpty(array_filter($filtered_document['data'], function ($resource_object) {
      return ($resource_object['attributes']['severity'] ?? NULL) === (string) RfcLogLevel::ERROR;
    }));

    // Test pagination by querying for single result on first page and checking
    // if related links exists or not.
    $first_page_response = $this->request('GET', $this->apiUrl('migrationMessageCollection')->setOption('query', [
      'page' => ['offset' => 0, 'limit' => 1],
    ]), []);
    $first_page_document = Json::decode((string) $first_page_response->getBody());
    $this->assertCount(1, $first_page_document['data']);
    $this->assertArrayHasKey('self', $first_page_document['links']);
    $this->assertArrayHasKey('next', $first_page_document['links']);
    $this->assertArrayHasKey('last', $first_page_document['links']);
    $this->assertArrayNotHasKey('first', $first_page_document['links']);
    $this->assertArrayNotHasKey('prev', $first_page_document['links']);
    // Test pagination by querying for single result on last page and checking
    // if related links exists or not.
    $last_page_response = $this->request('GET', $this->apiUrl('migrationMessageCollection')->setOption('query', [
      'page' => ['offset' => 1, 'limit' => 1],
    ]), []);
    $last_page_document = Json::decode((string) $last_page_response->getBody());
    $this->assertCount(1, $last_page_document['data']);
    $this->assertArrayHasKey('self', $last_page_document['links']);
    $this->assertArrayHasKey('first', $last_page_document['links']);
    $this->assertArrayHasKey('last', $last_page_document['links']);
    $this->assertArrayHasKey('prev', $last_page_document['links']);
    $this->assertArrayNotHasKey('next', $last_page_document['links']);

    return $document;
  }

  /**
   * @covers ::moduleInformation
   */
  public function testModuleInformation() {
    $this->drupalLogin($this->rootUser);
    // Install one of the recommended modules.
    $this->assertTrue($this->container->get('module_installer')->install(['telephone']));

    // No info prior to setting the acquia_migrate.initial_info state key.
    $url = $this->apiUrl('moduleInfo');
    $response = $this->request('GET', $url, []);
    $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());
    $this->assertSame('application/vnd.api+json', $response->getHeader('Content-Type')[0]);
    $document = Json::decode((string) $response->getBody());
    $this->assertEmpty($document['data']);

    $initial_info = [
      'sourceModules' => [
        [
          'name' => 'phone',
          'humanName' => 'Phone',
          'version' => '7.x-1.0-beta1',
        ],
        [
          'name' => 'overlay',
          'humanName' => 'Overlay',
          'version' => '7.x-1.0-beta1',
        ],
        [
          'name' => 'markdown',
          'humanName' => 'Markdown',
          'version' => '7.x-1.0-beta1',
        ],
        [
          'name' => 'unrecognized_module',
          'humanName' => 'Unrecognized Module',
          'version' => '7.x-1.0-beta1',
        ],
        [
          'name' => 'honeypot',
          'humanName' => 'Honeypot',
          'version' => '7.x-1.26',
        ],
      ],
      'recommendations' => [
        [
          'type' => 'packageRecommendation',
          'id' => 'drupal/core:9.0.0',
          'attributes' => [
            'requirePackage' => [
              'name' => 'drupal/core',
              'versionConstraint' => '9.0.0',
            ],
            'installModules' => [
              'telephone',
            ],
            'vetted' => FALSE,
          ],
          'relationships' => [
            'recommendedFor' => [
              'data' => [
                [
                  'type' => 'module',
                  'id' => 'phone',
                ],
              ],
            ],
          ],
        ],
        [
          'type' => 'abandonmentRecommendation',
          'id' => 'abandon:overlay',
          'attributes' => [
            'note' => 'The new Drupal 8 toolbar now includes a contextually aware \'back to site\' button that serves the same use-case as overlay by allowing site users to return from the admin context to the page that they were on.',
            'vetted' => TRUE,
          ],
          'relationships' => [
            'recommendedFor' => [
              'data' => [
                [
                  'type' => 'module',
                  'id' => 'overlay',
                ],
              ],
            ],
          ],
        ],
        [
          'type' => 'packageRecommendation',
          'id' => 'drupal/markdown:^2.0-rc1',
          'attributes' => [
            'requirePackage' => [
              'name' => 'drupal/markdown',
              'versionConstraint' => '^2.0-rc1',
            ],
            'installModules' => [
              'markdown',
            ],
            'vetted' => TRUE,
          ],
          'relationships' => [
            'recommendedFor' => [
              'data' => [
                [
                  'type' => 'sourceModule',
                  'id' => 'markdown',
                ],
              ],
            ],
          ],
        ],
        [
          'type' => 'packageRecommendation',
          'id' => 'league/commonmark:1.4.3',
          'attributes' => [
            'requirePackage' => [
              'name' => 'league/commonmark',
              'versionConstraint' => '1.4.3',
            ],
            'installModules' => [],
            'vetted' => TRUE,
          ],
          'relationships' => [
            'recommendedFor' => [
              'data' => [
                [
                  'type' => 'sourceModule',
                  'id' => 'markdown',
                ],
              ],
            ],
          ],
        ],
        [
          'type' => 'packageRecommendation',
          'id' => 'drupal/honeypot:2.0.1',
          'attributes' => [
            'requirePackage' => [
              'name' => 'drupal/honeypot',
              'versionConstraint' => '2.0.1',
            ],
            'installModules' => [],
            'vetted' => FALSE,
          ],
          'relationships' => [
            'recommendedFor' => [
              'data' => [
                [
                  'type' => 'module',
                  'id' => 'honeypot',
                ],
              ],
            ],
          ],
        ],
      ],
    ];

    \Drupal::state()->set('acquia_migrate.initial_info', $initial_info);

    // Module information derived from the initial info data.
    $response = $this->request('GET', $url, []);
    $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());
    $this->assertSame('application/vnd.api+json', $response->getHeader('Content-Type')[0]);
    $document = Json::decode((string) $response->getBody());
    $this->assertSame([
      'data' => [
        [
          'type' => 'sourceModule',
          'id' => 'phone',
          'attributes' => [
            'humanName' => 'Phone',
            'version' => '7.x-1.0-beta1',
            'recognitionState' => 'Found',
          ],
        ],
        [
          'type' => 'sourceModule',
          'id' => 'overlay',
          'attributes' => [
            'humanName' => 'Overlay',
            'version' => '7.x-1.0-beta1',
            'recognitionState' => 'Found',
          ],
        ],
        [
          'type' => 'sourceModule',
          'id' => 'markdown',
          'attributes' => [
            'humanName' => 'Markdown',
            'version' => '7.x-1.0-beta1',
            'recognitionState' => 'Found',
          ],
        ],
        [
          'type' => 'sourceModule',
          'id' => 'unrecognized_module',
          'attributes' => [
            'humanName' => 'Unrecognized Module',
            'version' => '7.x-1.0-beta1',
            'recognitionState' => 'Unknown',
          ],
        ],
        [
          'type' => 'sourceModule',
          'id' => 'honeypot',
          'attributes' => [
            'humanName' => 'Honeypot',
            'version' => '7.x-1.26',
            'recognitionState' => 'Found',
          ],
        ],
        [
          'type' => 'packageRecommendation',
          'id' => 'phone:drupal/core:9.0.0',
          'attributes' => [
            'modules' => [
              [
                'displayName' => 'Telephone',
                'machineName' => 'telephone',
                'version' => \Drupal::VERSION,
                'availableToInstall' => TRUE,
                'installed' => TRUE,
              ],
            ],
            'requirePackage' => [
              'packageName' => 'drupal/core',
              'versionConstraint' => '9.0.0',
            ],
            'vetted' => FALSE,
          ],
          'relationships' => [
            'recommendedFor' => [
              'data' => [
                [
                  'type' => 'sourceModule',
                  'id' => 'phone',
                ],
              ],
            ],
          ],
        ],
        [
          'type' => 'abandonmentRecommendation',
          'id' => 'overlay:abandon:overlay',
          'attributes' => [
            'note' => 'The new Drupal 8 toolbar now includes a contextually aware \'back to site\' button that serves the same use-case as overlay by allowing site users to return from the admin context to the page that they were on.',
            'vetted' => TRUE,
          ],
          'relationships' => [
            'recommendedFor' => [
              'data' => [
                [
                  'type' => 'sourceModule',
                  'id' => 'overlay',
                ],
              ],
            ],
          ],
        ],
        [
          'type' => 'packageRecommendation',
          'id' => 'markdown:drupal/markdown:^2.0-rc1',
          'attributes' => [
            'modules' => [
              [
                'displayName' => 'markdown',
                'machineName' => 'markdown',
                'version' => NULL,
                'availableToInstall' => FALSE,
                'installed' => FALSE,
              ],
            ],
            'requirePackage' => [
              'packageName' => 'drupal/markdown',
              'versionConstraint' => '^2.0-rc1',
            ],
            'vetted' => TRUE,
          ],
          'relationships' => [
            'recommendedFor' => [
              'data' => [
                [
                  'type' => 'sourceModule',
                  'id' => 'markdown',
                ],
              ],
            ],
          ],
          'links' => [
            'about' => [
              'href' => 'https://www.drupal.org/project/markdown',
              'type' => 'text/html',
            ],
          ],
        ],
        [
          'type' => 'packageRecommendation',
          'id' => 'markdown:league/commonmark:1.4.3',
          'attributes' => [
            'requirePackage' => [
              'packageName' => 'league/commonmark',
              'versionConstraint' => '1.4.3',
            ],
            'vetted' => TRUE,
          ],
          'relationships' => [
            'recommendedFor' => [
              'data' => [
                [
                  'type' => 'sourceModule',
                  'id' => 'markdown',
                ],
              ],
            ],
          ],
        ],
        [
          'type' => 'packageRecommendation',
          'id' => 'honeypot:drupal/honeypot:2.0.1',
          'attributes' => [
            'modules' => [
              [
                'displayName' => 'honeypot',
                'machineName' => 'honeypot',
                'version' => NULL,
                'availableToInstall' => FALSE,
                'installed' => FALSE,
              ],
            ],
            'requirePackage' => [
              'packageName' => 'drupal/honeypot',
              'versionConstraint' => '2.0.1',
            ],
            'vetted' => FALSE,
          ],
          'relationships' => [
            'recommendedFor' => [
              'data' => [
                [
                  'type' => 'sourceModule',
                  'id' => 'honeypot',
                ],
              ],
            ],
          ],
          'links' => [
            'about' => [
              'href' => 'https://www.drupal.org/project/honeypot',
              'type' => 'text/html',
            ],
          ],
        ],
      ],
      'links' => [
        'self' => [
          'href' => $url->toString(),
        ],
      ],
    ], $document);
  }

  /**
   * @covers ::migrationPatch
   */
  public function testMigrationFlagging() {
    $this->drupalLogin($this->rootUser);

    // No migration messages prior to running the  migration.
    $document = $this->getMigrationCollection();
    $this->assertNotEmpty($document['data']);

    // Follow all
    // https://drupal.org/project/acquia_migrate#link-rel-update-resource links
    // for the first migration.
    $first_migration_resource_object = $document['data'][0];

    $update_resource_links = array_filter($first_migration_resource_object['links'], function (array $link_object) {
      return isset($link_object['rel']) && $link_object['rel'] === 'https://drupal.org/project/acquia_migrate#link-rel-update-resource';
    });
    foreach ($update_resource_links as $link_object) {
      $response = $this->request('PATCH', Url::fromUri($link_object['href']), [
        RequestOptions::BODY => Json::encode(['data' => $link_object['params']['data']]),
      ]);
      $this->assertSame(204, $response->getStatusCode());
    }

    // Verify they changed the attributes on the migration resource object.
    $before = $first_migration_resource_object['attributes'];
    $response = $this->request('GET', Url::fromUri($first_migration_resource_object['links']['self']['href']));
    $after = Json::decode((string) $response->getBody())['data']['attributes'];
    $this->assertNotEquals($before, $after);

    // Verify that only boolean values are accepted.
    $response = $this->request('PATCH', Url::fromUri($link_object['href']), [
      RequestOptions::BODY => Json::encode(['data' => ['attributes' => ['skipped' => 42]] + $link_object['params']['data']]),
    ]);
    $this->assertSame(403, $response->getStatusCode());
    $document = Json::decode((string) $response->getBody());
    $this->assertSame('Only boolean values are allowed for the `skipped` attribute.', $document['errors'][0]['detail']);

    // Verify that only `completed` and `skipped` can be PATCHed.
    $response = $this->request('PATCH', Url::fromUri($link_object['href']), [
      RequestOptions::BODY => Json::encode(['data' => ['attributes' => ['label' => 'The Great Llama Migration']] + $link_object['params']['data']]),
    ]);
    $this->assertSame(403, $response->getStatusCode());
    $document = Json::decode((string) $response->getBody());
    $this->assertSame('Only the `activity`, `completed` and `skipped` fields can be updated.', $document['errors'][0]['detail']);
  }

  /**
   * Exercises the migration refresh workflow.
   *
   * @covers ::staleData
   */
  public function testRefresh() {
    $test_migration_label = 'Tags taxonomy terms';
    $this->assertTrue($this->container->get('module_installer')->install(['taxonomy', 'node', 'text'], TRUE));
    $this->drupalLogin($this->rootUser);
    $this->performMigrationPreselection();
    $this->performInitialMigration();

    // Forces a stale-data link to appear by modifying the source database's
    // `variable` table, which acts as a canary in the coal mine.
    $trigger_stale_data_link = function () {
      $this->sourceDatabase->upsert('variable')
        ->fields(['name', 'value'])
        ->values(['cron_last', serialize(\Drupal::time()->getRequestTime() - 42)])
        ->key('name')
        ->execute();
    };

    // No stale migrations prior to import.
    $document = $this->getMigrationCollection();
    foreach ($document['data'] as $resource_object) {
      ['links' => $links] = $resource_object;
      $this->assertArrayNotHasKey('refresh', $links);
    }

    // Process an import.
    $this->doProcessMigrationAction($this->getMigrationImportUrl($test_migration_label));

    // Confirm that tags were imported.
    $this->drupalGet('/taxonomy/term/9');
    $this->assertSession()->responseContains('Benjamin Sisko');
    $this->drupalGet('/taxonomy/term/10');
    $this->assertSession()->responseContains('Kira Nerys');
    $this->drupalGet('/taxonomy/term/11');
    $this->assertSession()->responseContains('Dax');

    // Confirm that the 'field_integer' and 'field_training' field storages were
    // imported.
    $etm = \Drupal::entityTypeManager();
    $field_storage_storage = $etm->getStorage('field_storage_config');
    $field_training = $field_storage_storage->load('taxonomy_term.field_training');
    $field_integer = $field_storage_storage->load('taxonomy_term.field_integer');
    $field_sector = $field_storage_storage->load('taxonomy_term.field_sector');
    $this->assertInstanceOf(FieldStorageConfigInterface::class, $field_training);
    $this->assertInstanceOf(FieldStorageConfigInterface::class, $field_integer);
    $this->assertInstanceOf(FieldStorageConfigInterface::class, $field_sector);
    $this->assertEquals(1, $field_integer->getCardinality());
    $this->assertEquals(1, $field_training->getCardinality());

    // Change term 10's label.
    $term_10 = $etm->getStorage('taxonomy_term')->load(10);
    $this->assertInstanceOf(TermInterface::class, $term_10);
    $term_10_updated_label = 'Kira Nerys – updated label';
    $term_10->setName($term_10_updated_label)->save();
    $this->assertEquals($term_10_updated_label, $term_10->getName());

    // Change cardinality of 'field_training'.
    $field_training->setCardinality(-1)->save();
    $this->assertEquals(-1, $field_training->getCardinality());

    // Check for updates.
    $stale_data_link = current(array_filter($document['links'] ?? [], function (array $link) {
      return ($link['rel'] ?? NULL) === UriDefinitions::LINK_REL_STALE_DATA;
    }));
    $this->assertIsArray($stale_data_link, var_export($document['links'], TRUE));
    $response = $this->request('GET', Url::fromUri($stale_data_link['href']), []);
    $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());
    $this->assertSame('application/vnd.api+json', $response->getHeader('Content-Type')[0]);
    $document = Json::decode((string) $response->getBody());
    $this->assertEmpty($document['data'], 'There should be no stale data because there has not yet been an update to any source tables used by the taxonomy term migration.');

    // Now that the test has checked for updates, make sure the stale-data link
    // is gone.
    $document = $this->getMigrationCollection();
    $this->assertEmpty(array_filter($document['links'] ?? [], function (array $link) {
      return ($link['rel'] ?? NULL) === UriDefinitions::LINK_REL_STALE_DATA;
    }), 'There should not be a stale-data link because the max interval between re-checks has not elapsed.');

    // Modify the source data so that it becomes "stale".
    $this->sourceDatabase->update('taxonomy_term_data')
      ->fields(['name' => 'Emissary of the Prophets'])
      ->condition('tid', 9)
      ->execute();

    // Remove a taxonomy term.
    $this->sourceDatabase->delete('taxonomy_term_data')
      ->condition('tid', 11)
      ->execute();

    // Change cardinality of field_integer to 3.
    $this->sourceDatabase->update('field_config')
      ->condition('field_name', 'field_integer')
      ->fields(['cardinality' => 3])
      ->execute();

    // Delete the sector field.
    $this->sourceDatabase->update('field_config')
      ->condition('field_name', 'field_sector')
      ->fields(['deleted' => 1])
      ->execute();

    // Trigger the appearance of the stale-data link.
    $trigger_stale_data_link();
    $document = $this->getMigrationCollection();
    $stale_data_link = current(array_filter($document['links'] ?? [], function (array $link) {
      return ($link['rel'] ?? NULL) === UriDefinitions::LINK_REL_STALE_DATA;
    }));
    $this->assertIsArray($stale_data_link, 'There should be a stale-data link because the variable table was modified.');

    // Validate that the taxonomy term migration is detected as stale.
    $response = $this->request('GET', Url::fromUri($stale_data_link['href']), []);
    $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());
    $this->assertSame('application/vnd.api+json', $response->getHeader('Content-Type')[0]);
    $document = Json::decode((string) $response->getBody());
    $this->assertNotEmpty($document['data'], 'There should be stale data because a source table used by the taxonomy term migration was updated.');

    // Refresh the taxonomy term migration. This also confirms that the
    // `refresh` link should appears on the migration collection because the
    // migration is stale.
    $this->doProcessMigrationAction($this->getMigrationActionUrl('Shared structure for taxonomy terms', 'refresh'));
    $this->doProcessMigrationAction($this->getMigrationActionUrl($test_migration_label, 'refresh'));

    // Once again, check for updates and then confirm that the refreshed
    // migration is no longer detected as stale.
    $response = $this->request('GET', Url::fromUri($stale_data_link['href']), []);
    $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());
    $this->assertSame('application/vnd.api+json', $response->getHeader('Content-Type')[0]);
    $document = Json::decode((string) $response->getBody());
    $this->assertEmpty(array_filter($document['data'], function (array $resource_identifier) use ($test_migration_label) {
      return $resource_identifier['attributes']['label'] === $test_migration_label;
    }), 'There should be no stale data because the taxonomy term migration was refreshed.');

    // Confirm that the tags were refreshed by verifying the updated term name
    // and that a removed tag was purged.
    $this->drupalGet($this->baseUrl . '/taxonomy/term/9');
    $this->assertSession()->responseContains('Emissary of the Prophets');
    $this->drupalGet($this->baseUrl . '/taxonomy/term/11');
    $this->assertSession()->responseContains('Page not found');
    // Also confirm that the unchanged term 10 wasn't updated.
    // @todo Uncomment in AMA-143.
    // @codingStandardsIgnoreStart
    // $this->drupalGet($this->baseUrl . '/taxonomy/term/10');
    // $this->assertSession()->responseContains($term_10_updated_label);
    // @codingStandardsIgnoreEnd

    // Check field storage updates.
    $field_training = $field_storage_storage->load('taxonomy_term.field_training');
    $field_integer = $field_storage_storage->load('taxonomy_term.field_integer');
    $field_sector = $field_storage_storage->load('taxonomy_term.field_sector');
    $this->assertInstanceOf(FieldStorageConfigInterface::class, $field_training);
    $this->assertInstanceOf(FieldStorageConfigInterface::class, $field_integer);
    // @todo Uncomment in AMA-143.
    // $this->assertEquals(-1, $field_training->getCardinality());
    $this->assertEquals(3, $field_integer->getCardinality());
    $this->assertNull($field_sector);
  }

  /**
   * Data provider for testMessagesCollectionDetail.
   */
  public function providerMessagesCollectionDetail() {
    return [
      'error for missing file 1' => [
        '1',
        [
          'type' => 'migrationMessage',
          'id' => '1',
          'attributes' => [
            'severity' => '3',
            'message' => "d7_file:uri:file_copy: File '/sites/default/files/cube.jpeg' does not exist",
            'solution' => 'There is a hardcoded files directory in the file path. This is wrong in the Drupal 7 source database too, please fix it there, refresh the source database and rollback & import this migration.',
            'messageCategory' => HttpApi::MESSAGE_CATEGORY_OTHER,
          ],
          'relationships' => [
            'sourceMigration' => [
              'data' => [
                'type' => 'migration',
                'id' => 'b2f4d7b22b91fc18fed49e862f658f3c-Public files',
                'meta' => [
                  'label' => 'Public files',
                ],
              ],
            ],
            'sourceMigrationPlugin' => [
              'data' => [
                'type' => 'migrationPlugin',
                'id' => 'd7_file',
              ],
            ],
          ],
          'links' => [
            'source' => [
              'href' => '#not-yet-implemented',
              'meta' => [
                'source-identifiers' => [
                  'fid' => '1',
                ],
              ],
            ],
            'severity' => [
              'href' => '/',
              'rel' => UriDefinitions::LINK_REL_SYSLOG_SEVERITY,
              'title' => 'error',
            ],
          ],
        ],
      ],
      'error for missing file 2' => [
        '2',
        [
          'type' => 'migrationMessage',
          'id' => '2',
          'attributes' => [
            'severity' => '3',
            'message' => 'd7_file:uri:file_copy: File \'/sites/default/files/ds9.txt\' does not exist',
            'solution' => 'There is a hardcoded files directory in the file path. This is wrong in the Drupal 7 source database too, please fix it there, refresh the source database and rollback & import this migration.',
            'messageCategory' => HttpApi::MESSAGE_CATEGORY_OTHER,
          ],
          'relationships' => [
            'sourceMigration' => [
              'data' => [
                'type' => 'migration',
                'id' => 'b2f4d7b22b91fc18fed49e862f658f3c-Public files',
                'meta' => [
                  'label' => 'Public files',
                ],
              ],
            ],
            'sourceMigrationPlugin' => [
              'data' => [
                'type' => 'migrationPlugin',
                'id' => 'd7_file',
              ],
            ],
          ],
          'links' => [
            'source' => [
              'href' => '#not-yet-implemented',
              'meta' => [
                'source-identifiers' => [
                  'fid' => '2',
                ],
              ],
            ],
            'severity' => [
              'href' => '/',
              'rel' => UriDefinitions::LINK_REL_SYSLOG_SEVERITY,
              'title' => 'error',
            ],
          ],
        ],
      ],
    ];
  }

  /**
   * Tests a few real-world messages in detail.
   *
   * @param string $id
   *   The migrationMessage resource ID.
   * @param array $expected_resource_object
   *   The expected migrationMessage resource object.
   * @param array $document
   *   The migrationMessages collection document.
   *
   * @depends testMessagesCollection
   * @dataProvider providerMessagesCollectionDetail
   */
  public function testMessagesCollectionDetail(string $id, array $expected_resource_object, array $document) {
    $index = array_search($id, array_column($document['data'], 'id'));
    $this->assertEquals($expected_resource_object, $document['data'][$index]);
  }

  /**
   * Tests that "Other" cluster is empty (=== missing).
   */
  public function testOtherClusterNotInCollection() {
    $this->drupalLogin($this->rootUser);

    $missing_migration_label = Other::cluster();

    $this->expectException(\LogicException::class);
    $this->expectExceptionMessage("A migration with the migration label 'Other' could not be found in the migration collection.");
    // Try to process the missing "Other" migration.
    $this->request('GET', $this->createMigrationProcess($this->getMigrationImportUrl($missing_migration_label)), []);
  }

  /**
   * @covers ::migrationStart
   * @covers ::migrationProcess
   * @covers \Drupal\acquia_migrate\Migration::isImportable()
   */
  public function testImportCriteriaForArticle() : void {
    $this->assertTrue($this->container->get('module_installer')->install(['node', 'taxonomy']));

    // Log in, preselect, perform the initial migration: the things any AMA user
    // would do.
    $this->drupalLogin($this->rootUser);
    $this->performMigrationPreselection();
    $this->performInitialMigration();

    $get_import_ratio = function (array $migration_resource_object) : float {
      return $migration_resource_object['attributes']['importedCount'] / $migration_resource_object['attributes']['totalCount'];
    };

    // The initial import of 'Shared structure for content items' fails to
    // import many fields because not all necessary field type-providing modules
    // are installed!
    $document = $this->getMigrationCollection();
    $migration_resource_object = self::getMigrationResourceObject('Shared structure for content items', $document);
    $this->assertLessThanOrEqual(Migration::MINIMUM_IMPORT_RATIO, $get_import_ratio($migration_resource_object));

    // Install additional modules providing field types, this should allow us to
    // get above the minimum import ratio.
    // TRICKY: these are safe to install later, unlike e.g. the "Taxonomy
    // module because none of them introduce _new_ migrations!
    $this->assertTrue($this->container->get('module_installer')->install([
      'link',
      'options',
      'telephone',
      'datetime',
    ], TRUE));

    // Now that the extra modules are installed, rollback and import the
    // 'Shared structure for content items' migration, then verify that we now
    // meet or exceed the minimum import ratio.
    $this->doProcessMigrationAction($this->getMigrationRollbackAndImportUrl('Shared structure for content items'));
    $document = $this->getMigrationCollection();
    $migration_resource_object = self::getMigrationResourceObject('Shared structure for content items', $document);
    $this->assertGreaterThanOrEqual(Migration::MINIMUM_IMPORT_RATIO, $get_import_ratio($migration_resource_object));

    // Verify that all dependencies of "Article" that should have been imported
    // by the initial import were in fact imported.
    $article_initial_import_dependencies = [
      'Language settings',
      'Filter format configuration',
      'Shared structure for content items',
    ];
    $document = $this->getMigrationCollection();
    foreach ($article_initial_import_dependencies as $label) {
      $migration_resource_object = self::getMigrationResourceObject($label, $document);
      $this->assertGreaterThanOrEqual(Migration::MINIMUM_IMPORT_RATIO, $get_import_ratio($migration_resource_object));
    }

    // Yet we should not yet be able to import articles!
    try {
      $this->getMigrationImportUrl('Article');
      $this->fail('The import link for articles was found, prematurely!');
    }
    catch (\LogicException $e) {
      $this->assertSame("A migration with the migration label 'Article' and the 'import' action could not be found in the migration collection.", $e->getMessage());
    }

    // Determine all remaining article dependencies.
    // (To defend against upstream changes in Drupal core, we compute this
    // instead of hardcoding it.)
    $article_deps = array_map(function (array $value) {
      return Migration::labelForId(NestedArray::getValue($value, ['id']));
    }, self::getMigrationResourceObject('Article', $document)['relationships']['dependencies']['data']);
    $remaining_dependencies = array_diff($article_deps, $article_initial_import_dependencies);

    // Import all remaining dependencies, because they are still at 0%.
    foreach ($remaining_dependencies as $article_dependency) {
      $migration_resource_object = self::getMigrationResourceObject($article_dependency, $document);
      $this->assertSame(0.0, $get_import_ratio($migration_resource_object), sprintf('Initial import count for %s is not zero.', $article_dependency));
      $this->doProcessMigrationAction($this->getMigrationImportUrl($article_dependency));
    }

    // Update the dashboard and verify that they're now all meeting the minimum
    // import ratio.
    $document = $this->getMigrationCollection();
    foreach (array_diff($article_deps, $article_initial_import_dependencies) as $article_dependency) {
      $migration_resource_object = self::getMigrationResourceObject($article_dependency, $document);
      $import_ratio = $get_import_ratio($migration_resource_object);
      $this->assertGreaterThanOrEqual(Migration::MINIMUM_IMPORT_RATIO, $import_ratio, sprintf('Final import count for %s is %f, which is not %f.', $article_dependency, $import_ratio, Migration::MINIMUM_IMPORT_RATIO));
      // While we only need them to meet the minimum import ratio, we as AMA
      // maintainers would like to be  notified when one of the dependencies no
      // longer reaches 100%.
      if ($import_ratio < 1) {
        $this->addWarning(sprintf('The import ratio for %s is not 1.0, but %f. We should investigate 🕵️‍♂️', $article_dependency, $import_ratio));
      }
    }

    // Now we should be able to import articles.
    try {
      $this->getMigrationImportUrl('Article');
      $this->pass('The import link for articles was found.');
    }
    catch (\LogicException $e) {
      $this->fail($e->getMessage());
    }

    // So let's.
    $this->doProcessMigrationAction($this->getMigrationImportUrl('Article'));

    // Update the dashboard and verify that all articles were imported.
    $document = $this->getMigrationCollection();
    $migration_resource_object = self::getMigrationResourceObject('Article', $document);
    $this->assertSame(1.0, $get_import_ratio($migration_resource_object));
  }

  /**
   * @covers ::messagesCollection
   * @covers \Drupal\acquia_migrate\EventSubscriber\PostEntitySaveValidator
   */
  public function testEntityValidationErrorMessages() {
    $this->assertTrue($this->container->get('module_installer')->install(['field']));

    // Log in as root user because we have to migrate user accounts, which will
    // automatically delete the user we'd create for testing. Only the root user
    // is not affected by this, so use it.
    $this->drupalLogin($this->rootUser);

    // Perform the preselection step.
    $this->performMigrationPreselection();

    // Perform the initial migration, but erase whatever messages were generated
    // by it: that's not what we're testing here.
    $this->performInitialMigration();
    // @codingStandardsIgnoreLine
    // var_dump(\Drupal::database()->select(SqlWithCentralizedMessageStorage::CENTRALIZED_MESSAGE_TABLE, 'messages')->fields('messages')->execute()->fetchAll()); return;
    \Drupal::database()->delete(SqlWithCentralizedMessageStorage::CENTRALIZED_MESSAGE_TABLE)->execute();

    // No migration messages prior to running the  migration.
    $response = $this->request('GET', $this->apiUrl('migrationMessageCollection'), []);
    $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());
    $this->assertSame('application/vnd.api+json; ext="https://drupal.org/project/acquia_migrate#jsonapi-extension-uri-template"', $response->getHeader('Content-Type')[0]);
    $document = Json::decode((string) $response->getBody());
    $this->assertEmpty($document['data']);

    $this->doProcessMigrationAction($this->getMigrationImportUrl('User accounts'));

    // Multiple migration messages after running the migration.
    $response = $this->request('GET', $this->apiUrl('migrationMessageCollection'), [
      RequestOptions::QUERY => [
        'fields' => [
          'migrationMessage' => 'sourceMigration,sourceMigrationPlugin,messageCategory,severity,message,solution',
        ],
      ],
    ]);
    $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());
    $this->assertSame('application/vnd.api+json; ext="https://drupal.org/project/acquia_migrate#jsonapi-extension-uri-template"', $response->getHeader('Content-Type')[0]);
    $document = Json::decode((string) $response->getBody());

    // Assert not just the expected count, but the actual messages, to simplify
    // debugging regressions, upstream changes, et cetera. Specifically compare
    // the messages and not IDs, because messages are less likely to change.
    $expected_messages = array_map(function (array $expected) {
      return $expected[1]['attributes']['message'];
    }, $this->providerEntityValidationErrorMessagesCollectionDetail());
    $actual_messages = array_map(function (array $resource_object) {
      return $resource_object['attributes']['message'];
    }, $document['data']);
    $actual_message_originating_migration_plugins = array_map(function (array $resource_object) {
      return $resource_object['relationships']['sourceMigrationPlugin']['data']['id'];
    }, $document['data']);
    $actual_message_originating_source_ids = array_map(function (array $resource_object) {
      return Json::encode($resource_object['links']['source']['meta']['source-identifiers']);
    }, $document['data']);
    if (!empty(array_diff($actual_messages, $expected_messages))) {
      $details = [];
      foreach (array_keys(array_diff($actual_messages, $expected_messages)) as $index) {
        $details[] = sprintf(
          "[thrown by %s, source row %s] %s",
          $actual_message_originating_migration_plugins[$index],
          $actual_message_originating_source_ids[$index],
          $actual_messages[$index]
        );
      }
      $this->fail(sprintf("Unexpected migration messages:\n\t%s", implode("\n\t", $details)));
    }
    $this->assertCount(count($expected_messages), $document['data']);

    return $document;
  }

  /**
   * Runs the migration with the given label.
   *
   * @param \Drupal\Core\Url $start_url
   *   A URL to start processing a migration action (e.g. an import).
   */
  protected function doProcessMigrationAction(Url $start_url) {
    $response = $this->request('GET', $this->createMigrationProcess($start_url), []);
    $document = Json::decode((string) $response->getBody());
    while (isset($document['links']['next'])) {
      $response = $this->request('GET', Url::fromUri($document['links']['next']['href']));
      $document = Json::decode((string) $response->getBody());
    }
  }

  public function providerEntityValidationErrorMessagesCollectionDetail() {
    return [
      'validation error for required field_integer field on user 1' => [
        '8',
        [
          'type' => 'migrationMessage',
          'id' => '8',
          'attributes' => [
            'severity' => '3',
            'message' => '[user: 1]: field_integer=This value should not be null.',
            'solution' => 'A new field was added to this entity type after entities had already been created. This new field was marked as required, but the entities that already existed were not updated with values for this new required field. This is wrong in the Drupal 7 source database too, please fix it there, refresh the source database and rollback & import this migration!',
            'messageCategory' => HttpApi::MESSAGE_CATEGORY_ENTITY_VALIDATION,
          ],
          'relationships' => [
            'sourceMigration' => [
              'data' => [
                'type' => 'migration',
                'id' => 'dbdd6377389228728e6ab594c50ad011-User accounts',
                'meta' => [
                  'label' => 'User accounts',
                ],
              ],
            ],
            'sourceMigrationPlugin' => [
              'data' => [
                'type' => 'migrationPlugin',
                'id' => 'd7_user',
              ],
            ],
          ],
          'links' => [
            'source' => [
              'href' => '#not-yet-implemented',
              'meta' => [
                'source-identifiers' => [
                  'uid' => '1',
                ],
              ],
            ],
            'severity' => [
              'href' => '/',
              'rel' => UriDefinitions::LINK_REL_SYSLOG_SEVERITY,
              'title' => 'error',
            ],
          ],
        ],
      ],
      'validation error for field_integer field on user 2' => [
        '9',
        [
          'type' => 'migrationMessage',
          'id' => '9',
          'attributes' => [
            'severity' => '3',
            'message' => '[user: 2]: field_integer.0.value=Integer: the value may be no greater than 25.',
            'solution' => NULL,
            'messageCategory' => HttpApi::MESSAGE_CATEGORY_ENTITY_VALIDATION,
          ],
          'relationships' => [
            'sourceMigration' => [
              'data' => [
                'type' => 'migration',
                'id' => 'dbdd6377389228728e6ab594c50ad011-User accounts',
                'meta' => [
                  'label' => 'User accounts',
                ],
              ],
            ],
            'sourceMigrationPlugin' => [
              'data' => [
                'type' => 'migrationPlugin',
                'id' => 'd7_user',
              ],
            ],
          ],
          'links' => [
            'source' => [
              'href' => '#not-yet-implemented',
              'meta' => [
                'source-identifiers' => [
                  'uid' => '2',
                ],
              ],
            ],
            'severity' => [
              'href' => '/',
              'rel' => UriDefinitions::LINK_REL_SYSLOG_SEVERITY,
              'title' => 'error',
            ],
          ],
        ],
      ],
      'validation error for field_integer field on user 3' => [
        '10',
        [
          'type' => 'migrationMessage',
          'id' => '10',
          'attributes' => [
            'severity' => '3',
            'message' => '[user: 3]: field_integer=This value should not be null.',
            'solution' => 'A new field was added to this entity type after entities had already been created. This new field was marked as required, but the entities that already existed were not updated with values for this new required field. This is wrong in the Drupal 7 source database too, please fix it there, refresh the source database and rollback & import this migration!',
            'messageCategory' => HttpApi::MESSAGE_CATEGORY_ENTITY_VALIDATION,
          ],
          'relationships' => [
            'sourceMigration' => [
              'data' => [
                'type' => 'migration',
                'id' => 'dbdd6377389228728e6ab594c50ad011-User accounts',
                'meta' => [
                  'label' => 'User accounts',
                ],
              ],
            ],
            'sourceMigrationPlugin' => [
              'data' => [
                'type' => 'migrationPlugin',
                'id' => 'd7_user',
              ],
            ],
          ],
          'links' => [
            'source' => [
              'href' => '#not-yet-implemented',
              'meta' => [
                'source-identifiers' => [
                  'uid' => '3',
                ],
              ],
            ],
            'severity' => [
              'href' => '/',
              'rel' => UriDefinitions::LINK_REL_SYSLOG_SEVERITY,
              'title' => 'error',
            ],
          ],
        ],
      ],
    ];
  }

  /**
   * @depends testEntityValidationErrorMessages
   * @dataProvider providerEntityValidationErrorMessagesCollectionDetail
   */
  public function testEntityValidationErrorMessagesCollectionDetail(string $id, array $expected_resource_object, array $document) {
    return $this->testMessagesCollectionDetail($id, $expected_resource_object, $document);
  }

  /**
   * Creates a new migration batch process and returns a URL for processing it.
   *
   * The caller must authenticate before calling this method.
   *
   * @param \Drupal\Core\Url $create_url
   *   A URL with which a migration process can be created.
   *
   * @return \Drupal\Core\Url
   *   The URL which will respond with status information and process the batch.
   */
  protected function createMigrationProcess(Url $create_url) {
    $response = $this->request('POST', $create_url);
    // A successful request results in 303, the client should GET the Location
    // URL.
    $this->assertSame(303, $response->getStatusCode(), (string) $response->getBody());
    $this->assertTrue($response->hasHeader('Location'));
    return Url::fromUri($response->getHeader('Location')[0]);
  }

  /**
   * Gets a URL that can be used to start a migration import batch process.
   *
   * The caller must authenticate before calling this method.
   *
   * @param string $migration_label
   *   The label of the migration to start.
   *
   * @return \Drupal\Core\Url
   *   A URL that can be used to start a migration import batch process.
   */
  protected function getMigrationImportUrl($migration_label) {
    return $this->getMigrationActionUrl($migration_label, 'import');
  }

  /**
   * Gets a URL that can be used to start a migration rollback batch process.
   *
   * The caller must authenticate before calling this method.
   *
   * @param string $migration_label
   *   The label of the migration to start.
   *
   * @return \Drupal\Core\Url
   *   A URL that can be used to start a migration import batch process.
   */
  protected function getMigrationRollbackUrl($migration_label) {
    return $this->getMigrationActionUrl($migration_label, 'rollback');
  }

  /**
   * Gets a URL that can be used to start a migration rollback & import process.
   *
   * The caller must authenticate before calling this method.
   *
   * @param string $migration_label
   *   The label of the migration to start.
   *
   * @return \Drupal\Core\Url
   *   A URL that can be used to start a migration import batch process.
   */
  protected function getMigrationRollbackAndImportUrl($migration_label) {
    return $this->getMigrationActionUrl($migration_label, 'rollback-and-import');
  }

  /**
   * Gets a URL that can be used to start a migration batch process.
   *
   * The caller must authenticate before calling this method.
   *
   * @param string $migration_label
   *   The label of the migration to start.
   * @param string $action
   *   A migration action. Either 'import', 'rollback', 'rollback-and-import' or
   *   'refresh'.
   *
   * @return \Drupal\Core\Url
   *   A URL that can be used to start a migration batch process.
   *
   * @throws \LogicException
   *   If the specified migration is missing.
   */
  protected function getMigrationActionUrl($migration_label, $action) {
    // @codingStandardsIgnoreStart
    // Coding standards can be re-enabled for this next line once https://github.com/acquia/coding-standards-php/pull/8 is merged.
    assert(in_array($action, ['import', 'rollback', 'rollback-and-import', 'refresh'], TRUE));
    // @codingStandardsIgnoreEnd
    // Make a request to the migration collection endpoint. It will have the
    // URLs to use to start each migration.
    $document = $this->getMigrationCollection();
    $migration_resource_object = self::getMigrationResourceObject($migration_label, $document);
    if (!$migration_resource_object) {
      throw new \LogicException("A migration with the migration label '$migration_label' could not be found in the migration collection.");
    }
    // Extract the link to use to start a migration if possible.
    $key_exists = FALSE;
    $start_link = NestedArray::getValue($migration_resource_object, ['links', $action], $key_exists);
    if (!$key_exists) {
      throw new \LogicException("A migration with the migration label '$migration_label' and the '$action' action could not be found in the migration collection.");
    }
    // It should have this link relation. It is important for clients to check
    // for this value to ensure that they do not break in the future if the API
    // changes (because the link relation will change to indicate a backwards-
    // incompatibility).
    $this->assertSame('https://drupal.org/project/acquia_migrate#link-rel-start-batch-process', $start_link['rel']);
    return Url::fromUri($start_link['href']);
  }

}
