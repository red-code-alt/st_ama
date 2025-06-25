<?php

namespace Drupal\Tests\acquia_migrate\Functional;

use Drupal\acquia_migrate\Timers;
use Drupal\acquia_migrate\UriDefinitions;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Url;
use Drupal\Tests\acquia_migrate\Traits\ServerTimingAssertionTrait;
use GuzzleHttp\RequestOptions;

/**
 * Tests the Acquia Migrate HTTP APIs with the 'standard' profile.
 *
 * @group acquia_migrate
 * @group acquia_migrate__core
 * @group acquia_migrate__mysql
 */
class HttpApiStandardTest extends HttpApiTestBase {

  use ServerTimingAssertionTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['color'];

  /**
   * {@inheritdoc}
   */
  protected $profile = 'standard';

  /**
   * {@inheritdoc}
   *
   * BC: Ensure Bartik is the default theme.
   */
  protected $defaultTheme = 'bartik';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // BC: Ensure Stark and Seven themes are installed, and Seven is the admin
    // theme.
    $this->container->get('theme_installer')->install(['seven', 'stark']);
    $this->container->get('config.factory')
      ->getEditable('system.theme')
      ->set('admin', 'seven')
      ->save();
  }

  /**
   * Tests the migrations collection API endpoint.
   */
  public function testMigrationsCollection() {
    $this->drupalLogin($this->rootUser);
    $response = $this->request('POST', $this->apiUrl('preselectMigrations'), [
      RequestOptions::HEADERS => [
        'Accept' => 'application/vnd.api+json',
        'Content-Type' => 'application/vnd.api+json; ext="https://jsonapi.org/ext/atomic"',
      ],
      RequestOptions::JSON => [
        'atomic:operations' => [
          [
            'op' => 'update',
            'data' => [
              'type' => 'migration',
              'id' => '6aa6b4bd50a2b501f0c761ccf2a08227-Filter format configuration',
              'attributes' => [
                'skipped' => FALSE,
              ],
            ],
          ],
        ],
      ],
    ]);
    $this->assertSame(204, $response->getStatusCode());
    $this->assertServerTiming($response, Timers::COMPUTE_MIGRATIONS, [0, static::expectedServerTimingMigrationsDestDbQueryCount()], 'HIT');

    $response = $this->request('GET', $this->apiUrl('migrationCollection'), []);
    $this->assertSame(200, $response->getStatusCode());
    $this->assertSame('application/vnd.api+json', $response->getHeader('Content-Type')[0]);

    $expected_body = [
      'data' => $this->expectedMigrationCollectionData(),
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
          'href' => Url::fromRoute('acquia_migrate.api.stale_data')
            ->setAbsolute()
            ->toString(),
          'title' => 'Check for updates',
          'rel' => UriDefinitions::LINK_REL_STALE_DATA,
        ],
        'initial-import' => [
          'href' => Url::fromRoute('acquia_migrate.api.migration.import.initial')
            ->setAbsolute()
            ->toString(),
          'title' => 'Initial import',
          'rel' => UriDefinitions::LINK_REL_START_BATCH_PROCESS,
        ],
      ],
      'meta' => [
        'sourceSyncTime' => NULL,
        'controllingSession' => NULL,
      ],
    ];
    $this->assertSame($expected_body, Json::decode((string) $response->getBody()));
    $this->assertServerTiming($response, Timers::CACHE_MIGRATIONS, [3], 'HIT');
    $this->assertServerTiming($response, Timers::COUNT_ID_MAP, [static::expectedServerTimingCountIdMapCount()]);

    $this->assertInitialImport(FALSE, static::expectedInitialMigrationPluginTotalRowCount(), 0, 0);
  }

  /**
   * Gets the expected initial migration plugin row count.
   *
   * @return int
   *   The expected total initial migration row count.
   */
  public static function expectedInitialMigrationPluginTotalRowCount() : int {
    return 426;
  }

  /**
   * Gets the expected destination DB query counts when computing migrations.
   *
   * @return int
   *   The expected destination DB query count when computing migrations.
   */
  public static function expectedServerTimingMigrationsDestDbQueryCount() : int {
    return 734;
  }

  /**
   * Gets the expected initial migration plugin row count.
   *
   * @return int
   *   The expected number of ID map row count requests.
   */
  public static function expectedServerTimingCountIdMapCount() : int {
    return 807;
  }

  /**
   * Gets the expected migration collection data.
   *
   * @return array
   *   The expected migration collection data.
   */
  protected function expectedMigrationCollectionData() : array {
    return [
      $this->expectedResourceObjectForFilterFormat(),
      $this->expectedResourceObjectForSharedStructureForComment(),
      $this->expectedResourceObjectForSharedStructureForContentItems(),
      $this->expectedResourceObjectForSharedStructureForTerms(),
      $this->expectedResourceObjectForSharedStructureForMenus(),
      $this->expectedResourceObjectForCustomBlocks(),
      $this->expectedResourceObjectForUserAccounts(),
      $this->expectedResourceObjectForPublicFiles(),
      $this->expectedResourceObjectForTestLongName(),
      $this->expectedResourceObjectForTagsTaxonomyTerms(),
      $this->expectedResourceObjectForVocabLocalizedTaxonomyTerms(),
      $this->expectedResourceObjectForVocabTranslateTaxonomyTerms(),
      $this->expectedResourceObjectForVocabFixedTaxonomyTerms(),
      $this->expectedResourceObjectForArticle(),
      $this->expectedResourceObjectForSujetDeDiscussionTaxonomyTerms(),
      $this->expectedResourceObjectForBlogEntry(),
      $this->expectedResourceObjectForBookPage(),
      $this->expectedResourceObjectForEntityTranslationTest(),
      $this->expectedResourceObjectForForumTopic(),
      $this->expectedResourceObjectForBasicPage(),
      $this->expectedResourceObjectForPrivateFiles(),
      $this->expectedResourceObjectForTestVocabularyTaxonomyTerms(),
      $this->expectedResourceObjectForTestContentType(),
      $this->expectedResourceObjectForTestLongNameComments(),
      $this->expectedResourceObjectForArticleComments(),
      $this->expectedResourceObjectForBlogEntryComments(),
      $this->expectedResourceObjectForBookPageComments(),
      $this->expectedResourceObjectForEntityTranslationTestComments(),
      $this->expectedResourceObjectForForumTopicComments(),
      $this->expectedResourceObjectForBasicPageComments(),
      $this->expectedResourceObjectForTestContentTypeComments(),
      $this->expectedResourceObjectForOtherMenuLinks(),
      $this->expectedResourceObjectForShortcutLinks(),
      $this->expectedResourceObjectForVocabLocalized2TaxonomyTerms(),
      $this->expectedResourceObjectForLongVocabularyNameTaxonomyTerms(),
      $this->expectedResourceObjectForOtherUrlAliases(),
      $this->expectedResourceObjectForBlockPlacements(),
      $this->expectedResourceObjectForSiteConfiguration(),
    ];
  }

  /**
   * Returns the API url for starting the given migration.
   *
   * @param string $migration_id
   *   The ID of the migration.
   *
   * @return \Drupal\Core\GeneratedUrl|string
   *   The migration starter url.
   */
  protected function getMigrationStartHref(string $migration_id) {
    return $this->apiUrl('migrationStart')
      ->setOption('query', ['migrationId' => $migration_id])
      ->toString();
  }

  protected function expectedResourceObjectForFilterFormat() {
    return [
      'type' => 'migration',
      'id' => '6aa6b4bd50a2b501f0c761ccf2a08227-Filter format configuration',
      'attributes' => [
        'label' => 'Filter format configuration',
        'importedCount' => 0,
        'processedCount' => 0,
        'totalCount' => 5,
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
            ['type' => 'migrationPlugin', 'id' => 'd7_filter_format (0 of 5)'],
          ],
        ],
      ],
      'links' => [
        'self' => [
          'href' => $this->apiUrl('migrationIndividual', ['6aa6b4bd50a2b501f0c761ccf2a08227-Filter format configuration'])
            ->toString(),
        ],
        'import' => [
          'href' => $this->getMigrationStartHref('6aa6b4bd50a2b501f0c761ccf2a08227-Filter format configuration'),
          'title' => 'Import',
          'rel' => UriDefinitions::LINK_REL_START_BATCH_PROCESS,
        ],
        'skip' => [
          'href' => $this->apiUrl('migrationIndividual', ['6aa6b4bd50a2b501f0c761ccf2a08227-Filter format configuration'])
            ->toString(),
          'title' => 'Skip',
          'rel' => UriDefinitions::LINK_REL_UPDATE_RESOURCE,
          'params' => [
            'confirm' => "I'm sure, this does not need to be migrated, at least not for now.",
            'data' => [
              'type' => 'migration',
              'id' => '6aa6b4bd50a2b501f0c761ccf2a08227-Filter format configuration',
              'attributes' => [
                'skipped' => TRUE,
              ],
            ],
          ],
        ],
      ],
    ];
  }

  protected function expectedResourceObjectForUserAccounts() {
    return [
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
            ['type' => 'migrationPlugin', 'id' => 'd7_field:user'],
            ['type' => 'migrationPlugin', 'id' => 'd7_field_instance:user:user'],
            ['type' => 'migrationPlugin', 'id' => 'user_picture_field'],
            ['type' => 'migrationPlugin', 'id' => 'user_picture_field_instance'],
            ['type' => 'migrationPlugin', 'id' => 'user_picture_entity_display'],
            ['type' => 'migrationPlugin', 'id' => 'user_picture_entity_form_display'],
            ['type' => 'migrationPlugin', 'id' => 'd7_view_modes:user'],
            ['type' => 'migrationPlugin', 'id' => 'd7_field_formatter_settings:user:user'],
            ['type' => 'migrationPlugin', 'id' => 'd7_field_instance_widget_settings:user:user'],
            ['type' => 'migrationPlugin', 'id' => 'd7_user (0 of 3)'],
          ],
        ],
      ],
      'links' => [
        'self' => [
          'href' => $this->apiUrl('migrationIndividual', ['dbdd6377389228728e6ab594c50ad011-User accounts'])->toString(),
        ],
        'import' => [
          'href' => $this->getMigrationStartHref('dbdd6377389228728e6ab594c50ad011-User accounts'),
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
          'title' => 'Not all supporting configuration has been processed yet: User accounts (specifically: d7_user_role, d7_field:user, d7_field_instance:user:user, user_picture_field, user_picture_field_instance, user_picture_entity_display, user_picture_entity_form_display, d7_view_modes:user, d7_field_formatter_settings:user:user, d7_field_instance_widget_settings:user:user).',
          'rel' => UriDefinitions::LINK_REL_UNMET_REQUIREMENT,
        ],
        'field-mapping' => [
          'href' => $this->apiUrl('migrationMapping', ['dbdd6377389228728e6ab594c50ad011-User accounts'])->toString(),
          'title' => 'View mapping',
          'rel' => UriDefinitions::LINK_REL_MAPPING,
        ],
      ],
    ];
  }

  protected function expectedResourceObjectForShortcutLinks() {
    return [
      'type' => 'migration',
      'id' => '3ab80f0610851db02c999831c570189d-Shortcut links',
      'attributes' => [
        'label' => 'Shortcut links',
        'importedCount' => 0,
        'processedCount' => 0,
        'totalCount' => 4,
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
              'id' => 'dbdd6377389228728e6ab594c50ad011-User accounts',
              'meta' => [
                'dependencyReasons' => [
                  'd7_user',
                ],
              ],
            ],
          ],
        ],
        'consistsOf' => [
          'data' => [
            ['type' => 'migrationPlugin', 'id' => 'd7_shortcut_set'],
            ['type' => 'migrationPlugin', 'id' => 'd7_shortcut_set_users'],
            ['type' => 'migrationPlugin', 'id' => 'd7_shortcut (0 of 4)'],
          ],
        ],
      ],
      'links' => [
        'self' => [
          'href' => $this->apiUrl('migrationIndividual', ['3ab80f0610851db02c999831c570189d-Shortcut links'])
            ->toString(),
        ],
        'skip' => [
          'href' => $this->apiUrl('migrationIndividual', ['3ab80f0610851db02c999831c570189d-Shortcut links'])
            ->toString(),
          'title' => 'Skip',
          'rel' => UriDefinitions::LINK_REL_UPDATE_RESOURCE,
          'params' => [
            'confirm' => "I'm sure, this does not need to be migrated, at least not for now.",
            'data' => [
              'type' => 'migration',
              'id' => '3ab80f0610851db02c999831c570189d-Shortcut links',
              'attributes' => [
                'skipped' => TRUE,
              ],
            ],
          ],
        ],
        'preview-unmet-requirement:0' => [
          'href' => 'https://drupal.org/project/acquia_migrate#application-concept-no-unprocessed-supporting-configuration',
          'title' => 'Not all supporting configuration has been processed yet: Shortcut links (specifically: d7_shortcut_set).',
          'rel' => UriDefinitions::LINK_REL_UNMET_REQUIREMENT,
        ],
        'field-mapping' => [
          'href' => $this->apiUrl('migrationMapping', ['3ab80f0610851db02c999831c570189d-Shortcut links'])
            ->toString(),
          'title' => 'View mapping',
          'rel' => UriDefinitions::LINK_REL_MAPPING,
        ],
      ],
    ];
  }

  protected function expectedResourceObjectForPrivateFiles() {
    return [
      'type' => 'migration',
      'id' => 'fe01e238e97d6349f9a1d68cb889dea2-Private files',
      'attributes' => [
        'label' => 'Private files',
        'importedCount' => 0,
        'processedCount' => 0,
        'totalCount' => 1,
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
              'id' => 'dbdd6377389228728e6ab594c50ad011-User accounts',
              'meta' => [
                'dependencyReasons' => [
                  0 => 'd7_user',
                ],
              ],
            ],
          ],
        ],
        'consistsOf' => [
          'data' => [
            ['type' => 'migrationPlugin', 'id' => 'd7_file_private (0 of 1)'],
          ],
        ],
      ],
      'links' => [
        'self' => [
          'href' => $this->apiUrl('migrationIndividual', ['fe01e238e97d6349f9a1d68cb889dea2-Private files'])
            ->toString(),
        ],
        'skip' => [
          'href' => $this->apiUrl('migrationIndividual', ['fe01e238e97d6349f9a1d68cb889dea2-Private files'])
            ->toString(),
          'title' => 'Skip',
          'rel' => UriDefinitions::LINK_REL_UPDATE_RESOURCE,
          'params' => [
            'confirm' => "I'm sure, this does not need to be migrated, at least not for now.",
            'data' => [
              'type' => 'migration',
              'id' => 'fe01e238e97d6349f9a1d68cb889dea2-Private files',
              'attributes' => [
                'skipped' => TRUE,
              ],
            ],
          ],
        ],
        'preview-by-offset' => [
          'href' => $this->apiUrl('migrationPreview', ['fe01e238e97d6349f9a1d68cb889dea2-Private files'])
            ->toString() . '?byOffset=0',
          'title' => 'Preview first row',
          'rel' => UriDefinitions::LINK_REL_PREVIEW,
        ],
        'preview-by-url' => [
          'href' => $this->apiUrl('migrationPreview', ['fe01e238e97d6349f9a1d68cb889dea2-Private files'])
            ->toString(),
          'title' => 'Preview by URL',
          'rel' => UriDefinitions::LINK_REL_PREVIEW,
          'uri-template:href' => $this->apiUrl('migrationPreview', ['fe01e238e97d6349f9a1d68cb889dea2-Private files'])
            ->toString() . '{?byUrl}',
          'uri-template:suggestions' => [
            [
              'label' => 'By source site URL',
              'variable' => 'byUrl',
              'cardinality' => 1,
            ],
          ],
        ],
        'field-mapping' => [
          'href' => $this->apiUrl('migrationMapping', ['fe01e238e97d6349f9a1d68cb889dea2-Private files'])
            ->toString(),
          'title' => 'View mapping',
          'rel' => UriDefinitions::LINK_REL_MAPPING,
        ],
      ],
    ];
  }

  protected function expectedResourceObjectForSharedStructureForMenus() {
    return [
      'type' => 'migration',
      'id' => 'cfddcadb31b559c03c57b20372420c1f-Shared structure for menus',
      'attributes' => [
        'label' => 'Shared structure for menus',
        'importedCount' => 0,
        'processedCount' => 0,
        'totalCount' => 6,
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
            ['type' => 'migrationPlugin', 'id' => 'd7_menu (0 of 6)'],
          ],
        ],
      ],
      'links' => [
        'self' => [
          'href' => $this->apiUrl('migrationIndividual', ['cfddcadb31b559c03c57b20372420c1f-Shared structure for menus'])
            ->toString(),
        ],
        'import' => [
          'href' => $this->getMigrationStartHref('cfddcadb31b559c03c57b20372420c1f-Shared structure for menus'),
          'title' => 'Import',
          'rel' => UriDefinitions::LINK_REL_START_BATCH_PROCESS,
        ],
        'skip' => [
          'href' => $this->apiUrl('migrationIndividual', ['cfddcadb31b559c03c57b20372420c1f-Shared structure for menus'])
            ->toString(),
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
    ];
  }

  protected function expectedResourceObjectForPublicFiles() {
    return [
      'type' => 'migration',
      'id' => 'b2f4d7b22b91fc18fed49e862f658f3c-Public files',
      'attributes' => [
        'label' => 'Public files',
        'importedCount' => 0,
        'processedCount' => 0,
        'totalCount' => 2,
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
            ['type' => 'migrationPlugin', 'id' => 'd7_file (0 of 2)'],
          ],
        ],
      ],
      'links' => [
        'self' => [
          'href' => $this->apiUrl('migrationIndividual', ['b2f4d7b22b91fc18fed49e862f658f3c-Public files'])
            ->toString(),
        ],
        'import' => [
          'href' => $this->getMigrationStartHref('b2f4d7b22b91fc18fed49e862f658f3c-Public files'),
          'title' => 'Import',
          'rel' => UriDefinitions::LINK_REL_START_BATCH_PROCESS,
        ],
        'skip' => [
          'href' => $this->apiUrl('migrationIndividual', ['b2f4d7b22b91fc18fed49e862f658f3c-Public files'])
            ->toString(),
          'title' => 'Skip',
          'rel' => UriDefinitions::LINK_REL_UPDATE_RESOURCE,
          'params' => [
            'confirm' => "I'm sure, this does not need to be migrated, at least not for now.",
            'data' => [
              'type' => 'migration',
              'id' => 'b2f4d7b22b91fc18fed49e862f658f3c-Public files',
              'attributes' => [
                'skipped' => TRUE,
              ],
            ],
          ],
        ],
        'preview-by-offset' => [
          'href' => $this->apiUrl('migrationPreview', ['b2f4d7b22b91fc18fed49e862f658f3c-Public files'])
            ->toString() . '?byOffset=0',
          'title' => 'Preview first row',
          'rel' => UriDefinitions::LINK_REL_PREVIEW,
        ],
        'preview-by-url' => [
          'href' => $this->apiUrl('migrationPreview', ['b2f4d7b22b91fc18fed49e862f658f3c-Public files'])
            ->toString(),
          'title' => 'Preview by URL',
          'rel' => UriDefinitions::LINK_REL_PREVIEW,
          'uri-template:href' => $this->apiUrl('migrationPreview', ['b2f4d7b22b91fc18fed49e862f658f3c-Public files'])
            ->toString() . '{?byUrl}',
          'uri-template:suggestions' => [
            [
              'label' => 'By source site URL',
              'variable' => 'byUrl',
              'cardinality' => 1,
            ],
          ],
        ],
        'field-mapping' => [
          'href' => $this->apiUrl('migrationMapping', ['b2f4d7b22b91fc18fed49e862f658f3c-Public files'])
            ->toString(),
          'title' => 'View mapping',
          'rel' => UriDefinitions::LINK_REL_MAPPING,
        ],
      ],
    ];
  }

  protected function expectedResourceObjectForSharedStructureForContentItems() {
    return [
      'type' => 'migration',
      'id' => 'ef08b5721f28f83bc0f418fc5ae937a6-Shared structure for content items',
      'attributes' => [
        'label' => 'Shared structure for content items',
        'importedCount' => 0,
        'processedCount' => 0,
        'totalCount' => 55,
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
            ['type' => 'migrationPlugin', 'id' => 'd7_field:node (0 of 52)'],
            ['type' => 'migrationPlugin', 'id' => 'd7_view_modes:node (0 of 3)'],
          ],
        ],
      ],
      'links' => [
        'self' => [
          'href' => $this->apiUrl('migrationIndividual', ['ef08b5721f28f83bc0f418fc5ae937a6-Shared structure for content items'])
            ->toString(),
        ],
        'import' => [
          'href' => $this->getMigrationStartHref('ef08b5721f28f83bc0f418fc5ae937a6-Shared structure for content items'),
          'title' => 'Import',
          'rel' => UriDefinitions::LINK_REL_START_BATCH_PROCESS,
        ],
        'skip' => [
          'href' => $this->apiUrl('migrationIndividual', ['ef08b5721f28f83bc0f418fc5ae937a6-Shared structure for content items'])
            ->toString(),
          'title' => 'Skip',
          'rel' => UriDefinitions::LINK_REL_UPDATE_RESOURCE,
          'params' => [
            'confirm' => "I'm sure, this does not need to be migrated, at least not for now.",
            'data' => [
              'type' => 'migration',
              'id' => 'ef08b5721f28f83bc0f418fc5ae937a6-Shared structure for content items',
              'attributes' => [
                'skipped' => TRUE,
              ],
            ],
          ],
        ],
      ],
    ];
  }

  protected function expectedResourceObjectForArticle() {
    return [
      'type' => 'migration',
      'id' => '5e2f8ee473fdebc99fef4dc9e7ee3146-Article',
      'attributes' => [
        'label' => 'Article',
        'importedCount' => 0,
        'processedCount' => 0,
        'totalCount' => 18,
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
              'id' => 'ef08b5721f28f83bc0f418fc5ae937a6-Shared structure for content items',
              'meta' => [
                'dependencyReasons' => [
                  'd7_field:node',
                  'd7_view_modes:node',
                ],
              ],
            ],
            [
              'type' => 'migration',
              'id' => '6aa6b4bd50a2b501f0c761ccf2a08227-Filter format configuration',
              'meta' => [
                'dependencyReasons' => [
                  'd7_filter_format',
                ],
              ],
            ],
            [
              'type' => 'migration',
              'id' => 'dbdd6377389228728e6ab594c50ad011-User accounts',
              'meta' => [
                'dependencyReasons' => [
                  'd7_user',
                ],
              ],
            ],
            [
              'type' => 'migration',
              'id' => 'b2f4d7b22b91fc18fed49e862f658f3c-Public files',
              'meta' => [
                'dependencyReasons' => [
                  'd7_file',
                ],
              ],
            ],
            [
              'type' => 'migration',
              'id' => '8638466f6e83d1f03465a4864cbb0461-Tags taxonomy terms',
              'meta' => [
                'dependencyReasons' => [
                  'd7_taxonomy_term:tags',
                ],
              ],
            ],
            [
              'type' => 'migration',
              'id' => '3c182f618268cb03638f443136874649-VocabLocalized taxonomy terms',
              'meta' => [
                'dependencyReasons' => [
                  'd7_taxonomy_term:vocablocalized',
                ],
              ],
            ],
            [
              'type' => 'migration',
              'id' => '3380423a44155f5b4dcedbb2a4bde666-VocabTranslate taxonomy terms',
              'meta' => [
                'dependencyReasons' => [
                  'd7_taxonomy_term:vocabtranslate',
                ],
              ],
            ],
            [
              'type' => 'migration',
              'id' => '5ba10dfebe289e3297a250327472bc0a-VocabFixed taxonomy terms',
              'meta' => [
                'dependencyReasons' => [
                  'd7_taxonomy_term:vocabfixed',
                ],
              ],
            ],
            [
              'type' => 'migration',
              'id' => 'cfddcadb31b559c03c57b20372420c1f-Shared structure for menus',
              'meta' => [
                'dependencyReasons' => [
                  'd7_menu',
                ],
              ],
            ],
          ],
        ],
        'consistsOf' => [
          'data' => [
            ['type' => 'migrationPlugin', 'id' => 'd7_node_type:article'],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_field_instance:node:article',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_field_formatter_settings:node:article',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_field_instance_widget_settings:node:article',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_node_complete:article (0 of 9)',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_menu_links:node:article (0 of 5)',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_url_alias:node:article (0 of 4)',
            ],
          ],
        ],
      ],
      'links' => [
        'self' => [
          'href' => $this->apiUrl('migrationIndividual', ['5e2f8ee473fdebc99fef4dc9e7ee3146-Article'])
            ->toString(),
        ],
        'skip' => [
          'href' => $this->apiUrl('migrationIndividual', ['5e2f8ee473fdebc99fef4dc9e7ee3146-Article'])
            ->toString(),
          'title' => 'Skip',
          'rel' => UriDefinitions::LINK_REL_UPDATE_RESOURCE,
          'params' => [
            'confirm' => "I'm sure, this does not need to be migrated, at least not for now.",
            'data' => [
              'type' => 'migration',
              'id' => '5e2f8ee473fdebc99fef4dc9e7ee3146-Article',
              'attributes' => [
                'skipped' => TRUE,
              ],
            ],
          ],
        ],
        'preview-unmet-requirement:0' => [
          'href' => 'https://drupal.org/project/acquia_migrate#application-concept-no-unprocessed-supporting-configuration',
          'title' => 'Not all supporting configuration has been processed yet: Article (specifically: d7_node_type:article, d7_field_instance:node:article, d7_field_formatter_settings:node:article, d7_field_instance_widget_settings:node:article), Shared structure for content items (specifically: d7_field:node, d7_view_modes:node), Filter format configuration (specifically: d7_filter_format), Shared structure for menus (specifically: d7_menu).',
          'rel' => UriDefinitions::LINK_REL_UNMET_REQUIREMENT,
        ],
        'field-mapping' => [
          'href' => $this->apiUrl('migrationMapping', ['5e2f8ee473fdebc99fef4dc9e7ee3146-Article'])
            ->toString(),
          'title' => 'View mapping',
          'rel' => UriDefinitions::LINK_REL_MAPPING,
        ],
      ],
    ];
  }

  protected function expectedResourceObjectForBasicPage() {
    return [
      'type' => 'migration',
      'id' => '2a3005e7dcf5be98a1c14bb6a845a2ee-Basic page',
      'attributes' => [
        'label' => 'Basic page',
        'importedCount' => 0,
        'processedCount' => 0,
        'totalCount' => 1,
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
              'id' => 'ef08b5721f28f83bc0f418fc5ae937a6-Shared structure for content items',
              'meta' => [
                'dependencyReasons' => [
                  'd7_field:node',
                  'd7_view_modes:node',
                ],
              ],
            ],
            [
              'type' => 'migration',
              'id' => '6aa6b4bd50a2b501f0c761ccf2a08227-Filter format configuration',
              'meta' => [
                'dependencyReasons' => [
                  'd7_filter_format',
                ],
              ],
            ],
            [
              'type' => 'migration',
              'id' => 'dbdd6377389228728e6ab594c50ad011-User accounts',
              'meta' => [
                'dependencyReasons' => [
                  'd7_user',
                ],
              ],
            ],
          ],
        ],
        'consistsOf' => [
          'data' => [
            ['type' => 'migrationPlugin', 'id' => 'd7_node_type:page'],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_field_instance:node:page',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_field_formatter_settings:node:page',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_field_instance_widget_settings:node:page',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_node_complete:page (0 of 1)',
            ],
          ],
        ],
      ],
      'links' => [
        'self' => [
          'href' => $this->apiUrl('migrationIndividual', ['2a3005e7dcf5be98a1c14bb6a845a2ee-Basic page'])
            ->toString(),
        ],
        'skip' => [
          'href' => $this->apiUrl('migrationIndividual', ['2a3005e7dcf5be98a1c14bb6a845a2ee-Basic page'])
            ->toString(),
          'title' => 'Skip',
          'rel' => UriDefinitions::LINK_REL_UPDATE_RESOURCE,
          'params' => [
            'confirm' => "I'm sure, this does not need to be migrated, at least not for now.",
            'data' => [
              'type' => 'migration',
              'id' => '2a3005e7dcf5be98a1c14bb6a845a2ee-Basic page',
              'attributes' => [
                'skipped' => TRUE,
              ],
            ],
          ],
        ],
        'preview-unmet-requirement:0' => [
          'href' => 'https://drupal.org/project/acquia_migrate#application-concept-no-unprocessed-supporting-configuration',
          'title' => 'Not all supporting configuration has been processed yet: Basic page (specifically: d7_node_type:page, d7_field_instance:node:page, d7_field_formatter_settings:node:page, d7_field_instance_widget_settings:node:page), Shared structure for content items (specifically: d7_field:node, d7_view_modes:node), Filter format configuration (specifically: d7_filter_format).',
          'rel' => UriDefinitions::LINK_REL_UNMET_REQUIREMENT,
        ],
        'field-mapping' => [
          'href' => $this->apiUrl('migrationMapping', ['2a3005e7dcf5be98a1c14bb6a845a2ee-Basic page'])
            ->toString(),
          'title' => 'View mapping',
          'rel' => UriDefinitions::LINK_REL_MAPPING,
        ],
      ],
    ];
  }

  protected function expectedResourceObjectForBlogEntry() {
    return [
      'type' => 'migration',
      'id' => 'b1748df090fe2f6a22e0b68db85fcf76-Blog entry',
      'attributes' => [
        'label' => 'Blog entry',
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
          'data' => [
            [
              'type' => 'migration',
              'id' => 'ef08b5721f28f83bc0f418fc5ae937a6-Shared structure for content items',
              'meta' => [
                'dependencyReasons' => [
                  'd7_field:node',
                  'd7_view_modes:node',
                ],
              ],
            ],
            [
              'type' => 'migration',
              'id' => '6aa6b4bd50a2b501f0c761ccf2a08227-Filter format configuration',
              'meta' => [
                'dependencyReasons' => [
                  'd7_filter_format',
                ],
              ],
            ],
            [
              'type' => 'migration',
              'id' => 'dbdd6377389228728e6ab594c50ad011-User accounts',
              'meta' => [
                'dependencyReasons' => [
                  'd7_user',
                ],
              ],
            ],
            [
              'type' => 'migration',
              'id' => 'b2f4d7b22b91fc18fed49e862f658f3c-Public files',
              'meta' => [
                'dependencyReasons' => [
                  'd7_file',
                ],
              ],
            ],
            [
              'type' => 'migration',
              'id' => '8638466f6e83d1f03465a4864cbb0461-Tags taxonomy terms',
              'meta' => [
                'dependencyReasons' => [
                  'd7_taxonomy_term:tags',
                ],
              ],
            ],
            [
              'type' => 'migration',
              'id' => '77dbe7f5d22c6bebefc3475f5b9acba9-Sujet de discussion taxonomy terms',
              'meta' => [
                'dependencyReasons' => [
                  'd7_taxonomy_term:sujet_de_discussion',
                ],
              ],
            ],
          ],
        ],
        'consistsOf' => [
          'data' => [
            ['type' => 'migrationPlugin', 'id' => 'd7_node_type:blog'],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_field_instance:node:blog',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_field_formatter_settings:node:blog',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_field_instance_widget_settings:node:blog',
            ],
            ['type' => 'migrationPlugin', 'id' => 'd7_node_complete:blog (0 of 3)'],
          ],
        ],
      ],
      'links' => [
        'self' => [
          'href' => $this->apiUrl('migrationIndividual', ['b1748df090fe2f6a22e0b68db85fcf76-Blog entry'])
            ->toString(),
        ],
        'skip' => [
          'href' => $this->apiUrl('migrationIndividual', ['b1748df090fe2f6a22e0b68db85fcf76-Blog entry'])
            ->toString(),
          'title' => 'Skip',
          'rel' => UriDefinitions::LINK_REL_UPDATE_RESOURCE,
          'params' => [
            'confirm' => "I'm sure, this does not need to be migrated, at least not for now.",
            'data' => [
              'type' => 'migration',
              'id' => 'b1748df090fe2f6a22e0b68db85fcf76-Blog entry',
              'attributes' => [
                'skipped' => TRUE,
              ],
            ],
          ],
        ],
        'preview-unmet-requirement:0' => [
          'href' => 'https://drupal.org/project/acquia_migrate#application-concept-no-unprocessed-supporting-configuration',
          'title' => 'Not all supporting configuration has been processed yet: Blog entry (specifically: d7_node_type:blog, d7_field_instance:node:blog, d7_field_formatter_settings:node:blog, d7_field_instance_widget_settings:node:blog), Shared structure for content items (specifically: d7_field:node, d7_view_modes:node), Filter format configuration (specifically: d7_filter_format).',
          'rel' => UriDefinitions::LINK_REL_UNMET_REQUIREMENT,
        ],
        'field-mapping' => [
          'href' => $this->apiUrl('migrationMapping', ['b1748df090fe2f6a22e0b68db85fcf76-Blog entry'])
            ->toString(),
          'title' => 'View mapping',
          'rel' => UriDefinitions::LINK_REL_MAPPING,
        ],
      ],
    ];
  }

  protected function expectedResourceObjectForBookPage() {
    return [
      'type' => 'migration',
      'id' => '0611eb9dc2d6ffc1e2dfd16bda6e5cfc-Book page',
      'attributes' => [
        'label' => 'Book page',
        'importedCount' => 0,
        'processedCount' => 0,
        'totalCount' => 0,
        'completed' => FALSE,
        'stale' => FALSE,
        'skipped' => TRUE,
        'lastImported' => NULL,
        'activity' => 'idle',
      ],
      'relationships' => [
        'dependencies' => [
          'data' => [
            [
              'type' => 'migration',
              'id' => 'ef08b5721f28f83bc0f418fc5ae937a6-Shared structure for content items',
              'meta' => [
                'dependencyReasons' => [
                  'd7_field:node',
                  'd7_view_modes:node',
                ],
              ],
            ],
            [
              'type' => 'migration',
              'id' => '6aa6b4bd50a2b501f0c761ccf2a08227-Filter format configuration',
              'meta' => [
                'dependencyReasons' => [
                  'd7_filter_format',
                ],
              ],
            ],
            [
              'type' => 'migration',
              'id' => 'dbdd6377389228728e6ab594c50ad011-User accounts',
              'meta' => [
                'dependencyReasons' => [
                  'd7_user',
                ],
              ],
            ],
          ],
        ],
        'consistsOf' => [
          'data' => [
            ['type' => 'migrationPlugin', 'id' => 'd7_node_type:book'],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_field_instance:node:book',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_field_formatter_settings:node:book',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_field_instance_widget_settings:node:book',
            ],
            ['type' => 'migrationPlugin', 'id' => 'd7_node_complete:book (0 of 0)'],
          ],
        ],
      ],
      'links' => [
        'self' => [
          'href' => $this->apiUrl('migrationIndividual', ['0611eb9dc2d6ffc1e2dfd16bda6e5cfc-Book page'])
            ->toString(),
        ],
        'unskip' => [
          'href' => $this->apiUrl('migrationIndividual', ['0611eb9dc2d6ffc1e2dfd16bda6e5cfc-Book page'])
            ->toString(),
          'title' => 'Unskip',
          'rel' => UriDefinitions::LINK_REL_UPDATE_RESOURCE,
          'params' => [
            'confirm' => FALSE,
            'data' => [
              'type' => 'migration',
              'id' => '0611eb9dc2d6ffc1e2dfd16bda6e5cfc-Book page',
              'attributes' => [
                'skipped' => FALSE,
              ],
            ],
          ],
        ],
      ],
    ];
  }

  protected function expectedResourceObjectForEntityTranslationTest() {
    return [
      'type' => 'migration',
      'id' => '2f9174e10c9aa48ca9bffa5c223e5d98-Entity translation test',
      'attributes' => [
        'label' => 'Entity translation test',
        'importedCount' => 0,
        'processedCount' => 0,
        'totalCount' => 8,
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
              'id' => 'ef08b5721f28f83bc0f418fc5ae937a6-Shared structure for content items',
              'meta' => [
                'dependencyReasons' => [
                  'd7_field:node',
                  'd7_view_modes:node',
                ],
              ],
            ],
            [
              'type' => 'migration',
              'id' => 'dbdd6377389228728e6ab594c50ad011-User accounts',
              'meta' => [
                'dependencyReasons' => [
                  'd7_user',
                ],
              ],
            ],
          ],
        ],
        'consistsOf' => [
          'data' => [
            ['type' => 'migrationPlugin', 'id' => 'd7_node_type:et'],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_field_instance:node:et',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_field_formatter_settings:node:et',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_field_instance_widget_settings:node:et',
            ],
            ['type' => 'migrationPlugin', 'id' => 'd7_node_complete:et (0 of 8)'],
          ],
        ],
      ],
      'links' => [
        'self' => [
          'href' => $this->apiUrl('migrationIndividual', ['2f9174e10c9aa48ca9bffa5c223e5d98-Entity translation test'])
            ->toString(),
        ],
        'skip' => [
          'href' => $this->apiUrl('migrationIndividual', ['2f9174e10c9aa48ca9bffa5c223e5d98-Entity translation test'])
            ->toString(),
          'title' => 'Skip',
          'rel' => UriDefinitions::LINK_REL_UPDATE_RESOURCE,
          'params' => [
            'confirm' => "I'm sure, this does not need to be migrated, at least not for now.",
            'data' => [
              'type' => 'migration',
              'id' => '2f9174e10c9aa48ca9bffa5c223e5d98-Entity translation test',
              'attributes' => [
                'skipped' => TRUE,
              ],
            ],
          ],
        ],
        'preview-unmet-requirement:0' => [
          'href' => 'https://drupal.org/project/acquia_migrate#application-concept-no-unprocessed-supporting-configuration',
          'title' => 'Not all supporting configuration has been processed yet: Entity translation test (specifically: d7_node_type:et, d7_field_instance:node:et, d7_field_formatter_settings:node:et, d7_field_instance_widget_settings:node:et), Shared structure for content items (specifically: d7_field:node, d7_view_modes:node).',
          'rel' => UriDefinitions::LINK_REL_UNMET_REQUIREMENT,
        ],
        'field-mapping' => [
          'href' => $this->apiUrl('migrationMapping', ['2f9174e10c9aa48ca9bffa5c223e5d98-Entity translation test'])
            ->toString(),
          'title' => 'View mapping',
          'rel' => UriDefinitions::LINK_REL_MAPPING,
        ],
      ],
    ];
  }

  protected function expectedResourceObjectForForumTopic() {
    return [
      'type' => 'migration',
      'id' => 'cfe8acbd869cc2c42fd30b2ca2214103-Forum topic',
      'attributes' => [
        'label' => 'Forum topic',
        'importedCount' => 0,
        'processedCount' => 0,
        'totalCount' => 2,
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
              'id' => 'ef08b5721f28f83bc0f418fc5ae937a6-Shared structure for content items',
              'meta' => [
                'dependencyReasons' => [
                  'd7_field:node',
                  'd7_view_modes:node',
                ],
              ],
            ],
            [
              'type' => 'migration',
              'id' => '6aa6b4bd50a2b501f0c761ccf2a08227-Filter format configuration',
              'meta' => [
                'dependencyReasons' => [
                  'd7_filter_format',
                ],
              ],
            ],
            [
              'type' => 'migration',
              'id' => 'dbdd6377389228728e6ab594c50ad011-User accounts',
              'meta' => [
                'dependencyReasons' => [
                  'd7_user',
                ],
              ],
            ],
            [
              'type' => 'migration',
              'id' => '77dbe7f5d22c6bebefc3475f5b9acba9-Sujet de discussion taxonomy terms',
              'meta' => [
                'dependencyReasons' => [
                  'd7_taxonomy_term:sujet_de_discussion',
                ],
              ],
            ],
          ],
        ],
        'consistsOf' => [
          'data' => [
            ['type' => 'migrationPlugin', 'id' => 'd7_node_type:forum'],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_node_title_label:forum',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_field_instance:node:forum',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_field_formatter_settings:node:forum',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_field_instance_widget_settings:node:forum',
            ],
            ['type' => 'migrationPlugin', 'id' => 'd7_node_complete:forum (0 of 2)'],
          ],
        ],
      ],
      'links' => [
        'self' => [
          'href' => $this->apiUrl('migrationIndividual', ['cfe8acbd869cc2c42fd30b2ca2214103-Forum topic'])
            ->toString(),
        ],
        'skip' => [
          'href' => $this->apiUrl('migrationIndividual', ['cfe8acbd869cc2c42fd30b2ca2214103-Forum topic'])
            ->toString(),
          'title' => 'Skip',
          'rel' => UriDefinitions::LINK_REL_UPDATE_RESOURCE,
          'params' => [
            'confirm' => "I'm sure, this does not need to be migrated, at least not for now.",
            'data' => [
              'type' => 'migration',
              'id' => 'cfe8acbd869cc2c42fd30b2ca2214103-Forum topic',
              'attributes' => [
                'skipped' => TRUE,
              ],
            ],
          ],
        ],
        'preview-unmet-requirement:0' => [
          'href' => 'https://drupal.org/project/acquia_migrate#application-concept-no-unprocessed-supporting-configuration',
          'title' => 'Not all supporting configuration has been processed yet: Forum topic (specifically: d7_node_type:forum, d7_node_title_label:forum, d7_field_instance:node:forum, d7_field_formatter_settings:node:forum, d7_field_instance_widget_settings:node:forum), Shared structure for content items (specifically: d7_field:node, d7_view_modes:node), Filter format configuration (specifically: d7_filter_format).',
          'rel' => UriDefinitions::LINK_REL_UNMET_REQUIREMENT,
        ],
        'field-mapping' => [
          'href' => $this->apiUrl('migrationMapping', ['cfe8acbd869cc2c42fd30b2ca2214103-Forum topic'])
            ->toString(),
          'title' => 'View mapping',
          'rel' => UriDefinitions::LINK_REL_MAPPING,
        ],
      ],
    ];
  }

  protected function expectedResourceObjectForTestContentType() {
    return [
      'type' => 'migration',
      'id' => '435d8f3f26aa9bd74c92d0fa58bff120-Test content type',
      'attributes' => [
        'label' => 'Test content type',
        'importedCount' => 0,
        'processedCount' => 0,
        'totalCount' => 1,
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
              'id' => 'ef08b5721f28f83bc0f418fc5ae937a6-Shared structure for content items',
              'meta' => [
                'dependencyReasons' => [
                  'd7_field:node',
                  'd7_view_modes:node',
                ],
              ],
            ],
            [
              'type' => 'migration',
              'id' => '6aa6b4bd50a2b501f0c761ccf2a08227-Filter format configuration',
              'meta' => [
                'dependencyReasons' => [
                  'd7_filter_format',
                ],
              ],
            ],
            [
              'type' => 'migration',
              'id' => 'dbdd6377389228728e6ab594c50ad011-User accounts',
              'meta' => [
                'dependencyReasons' => [
                  'd7_user',
                ],
              ],
            ],
            [
              'type' => 'migration',
              'id' => 'b2f4d7b22b91fc18fed49e862f658f3c-Public files',
              'meta' => [
                'dependencyReasons' => [
                  'd7_file',
                ],
              ],
            ],
            [
              'type' => 'migration',
              'id' => 'fe01e238e97d6349f9a1d68cb889dea2-Private files',
              'meta' => [
                'dependencyReasons' => [
                  'd7_file_private',
                ],
              ],
            ],
            [
              'type' => 'migration',
              'id' => '8638466f6e83d1f03465a4864cbb0461-Tags taxonomy terms',
              'meta' => [
                'dependencyReasons' => [
                  'd7_taxonomy_term:tags',
                ],
              ],
            ],
            [
              'type' => 'migration',
              'id' => '858158392a46f33c9e9bbbb36e7abd1f-Test Vocabulary taxonomy terms',
              'meta' => [
                'dependencyReasons' => [
                  'd7_taxonomy_term:test_vocabulary',
                ],
              ],
            ],
          ],
        ],
        'consistsOf' => [
          'data' => [
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_node_type:test_content_type',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_field_instance:node:test_content_type',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_field_formatter_settings:node:test_content_type',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_field_instance_widget_settings:node:test_content_type',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_node_complete:test_content_type (0 of 1)',
            ],
          ],
        ],
      ],
      'links' => [
        'self' => [
          'href' => $this->apiUrl('migrationIndividual', ['435d8f3f26aa9bd74c92d0fa58bff120-Test content type'])
            ->toString(),
        ],
        'skip' => [
          'href' => $this->apiUrl('migrationIndividual', ['435d8f3f26aa9bd74c92d0fa58bff120-Test content type'])
            ->toString(),
          'title' => 'Skip',
          'rel' => UriDefinitions::LINK_REL_UPDATE_RESOURCE,
          'params' => [
            'confirm' => "I'm sure, this does not need to be migrated, at least not for now.",
            'data' => [
              'type' => 'migration',
              'id' => '435d8f3f26aa9bd74c92d0fa58bff120-Test content type',
              'attributes' => [
                'skipped' => TRUE,
              ],
            ],
          ],
        ],
        'preview-unmet-requirement:0' => [
          'href' => 'https://drupal.org/project/acquia_migrate#application-concept-no-unprocessed-supporting-configuration',
          'title' => 'Not all supporting configuration has been processed yet: Test content type (specifically: d7_node_type:test_content_type, d7_field_instance:node:test_content_type, d7_field_formatter_settings:node:test_content_type, d7_field_instance_widget_settings:node:test_content_type), Shared structure for content items (specifically: d7_field:node, d7_view_modes:node), Filter format configuration (specifically: d7_filter_format).',
          'rel' => UriDefinitions::LINK_REL_UNMET_REQUIREMENT,
        ],
        'field-mapping' => [
          'href' => $this->apiUrl('migrationMapping', ['435d8f3f26aa9bd74c92d0fa58bff120-Test content type'])
            ->toString(),
          'title' => 'View mapping',
          'rel' => UriDefinitions::LINK_REL_MAPPING,
        ],
      ],
    ];
  }

  protected function expectedResourceObjectForOtherMenuLinks() {
    return [
      'type' => 'migration',
      'id' => '93eff1f6239b3000e8fc9ed1ae9ece71-Other Menu links',
      'attributes' => [
        'label' => 'Other Menu links',
        'importedCount' => 0,
        'processedCount' => 0,
        'totalCount' => 8,
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
              'id' => 'cfddcadb31b559c03c57b20372420c1f-Shared structure for menus',
              'meta' => [
                'dependencyReasons' => [
                  'd7_menu',
                ],
              ],
            ],
          ],
        ],
        'consistsOf' => [
          'data' => [
            ['type' => 'migrationPlugin', 'id' => 'd7_menu_links:other (0 of 8)'],
          ],
        ],
      ],
      'links' => [
        'self' => [
          'href' => $this->apiUrl('migrationIndividual', ['93eff1f6239b3000e8fc9ed1ae9ece71-Other Menu links'])
            ->toString(),
        ],
        'skip' => [
          'href' => $this->apiUrl('migrationIndividual', ['93eff1f6239b3000e8fc9ed1ae9ece71-Other Menu links'])
            ->toString(),
          'title' => 'Skip',
          'rel' => UriDefinitions::LINK_REL_UPDATE_RESOURCE,
          'params' => [
            'confirm' => "I'm sure, this does not need to be migrated, at least not for now.",
            'data' => [
              'type' => 'migration',
              'id' => '93eff1f6239b3000e8fc9ed1ae9ece71-Other Menu links',
              'attributes' => [
                'skipped' => TRUE,
              ],
            ],
          ],
        ],
        'preview-unmet-requirement:0' => [
          'href' => 'https://drupal.org/project/acquia_migrate#application-concept-no-unprocessed-supporting-configuration',
          'title' => 'Not all supporting configuration has been processed yet: Shared structure for menus (specifically: d7_menu).',
          'rel' => UriDefinitions::LINK_REL_UNMET_REQUIREMENT,
        ],
        'field-mapping' => [
          'href' => $this->apiUrl('migrationMapping', ['93eff1f6239b3000e8fc9ed1ae9ece71-Other Menu links'])
            ->toString(),
          'title' => 'View mapping',
          'rel' => UriDefinitions::LINK_REL_MAPPING,
        ],
      ],
    ];
  }

  protected function expectedResourceObjectForSharedStructureForComment() {
    return [
      'type' => 'migration',
      'id' => '31f60fde4105c62db89a38339ad1bebc-Shared structure for comments',
      'attributes' => [
        'label' => 'Shared structure for comments',
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
            ['type' => 'migrationPlugin', 'id' => 'd7_field:comment (0 of 2)'],
            ['type' => 'migrationPlugin', 'id' => 'd7_view_modes:comment (0 of 1)'],
          ],
        ],
      ],
      'links' => [
        'self' => [
          'href' => $this->apiUrl('migrationIndividual', ['31f60fde4105c62db89a38339ad1bebc-Shared structure for comments'])
            ->toString(),
        ],
        'import' => [
          'href' => $this->getMigrationStartHref('31f60fde4105c62db89a38339ad1bebc-Shared structure for comments'),
          'title' => 'Import',
          'rel' => UriDefinitions::LINK_REL_START_BATCH_PROCESS,
        ],
        'skip' => [
          'href' => $this->apiUrl('migrationIndividual', ['31f60fde4105c62db89a38339ad1bebc-Shared structure for comments'])
            ->toString(),
          'title' => 'Skip',
          'rel' => UriDefinitions::LINK_REL_UPDATE_RESOURCE,
          'params' => [
            'confirm' => "I'm sure, this does not need to be migrated, at least not for now.",
            'data' => [
              'type' => 'migration',
              'id' => '31f60fde4105c62db89a38339ad1bebc-Shared structure for comments',
              'attributes' => [
                'skipped' => TRUE,
              ],
            ],
          ],
        ],
      ],
    ];
  }

  protected function expectedResourceObjectForSharedStructureForTerms() {
    return [
      'type' => 'migration',
      'id' => 'c9adf432befc3cd4b14c496e6c5ceb4c-Shared structure for taxonomy terms',
      'attributes' => [
        'label' => 'Shared structure for taxonomy terms',
        'importedCount' => 0,
        'processedCount' => 0,
        'totalCount' => 6,
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
            ['type' => 'migrationPlugin', 'id' => 'd7_field:taxonomy_term (0 of 5)'],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_view_modes:taxonomy_term (0 of 1)',
            ],
          ],
        ],
      ],
      'links' => [
        'self' => [
          'href' => $this->apiUrl('migrationIndividual', ['c9adf432befc3cd4b14c496e6c5ceb4c-Shared structure for taxonomy terms'])
            ->toString(),
        ],
        'import' => [
          'href' => $this->getMigrationStartHref('c9adf432befc3cd4b14c496e6c5ceb4c-Shared structure for taxonomy terms'),
          'title' => 'Import',
          'rel' => UriDefinitions::LINK_REL_START_BATCH_PROCESS,
        ],
        'skip' => [
          'href' => $this->apiUrl('migrationIndividual', ['c9adf432befc3cd4b14c496e6c5ceb4c-Shared structure for taxonomy terms'])
            ->toString(),
          'title' => 'Skip',
          'rel' => UriDefinitions::LINK_REL_UPDATE_RESOURCE,
          'params' => [
            'confirm' => "I'm sure, this does not need to be migrated, at least not for now.",
            'data' => [
              'type' => 'migration',
              'id' => 'c9adf432befc3cd4b14c496e6c5ceb4c-Shared structure for taxonomy terms',
              'attributes' => [
                'skipped' => TRUE,
              ],
            ],
          ],
        ],
      ],
    ];
  }

  protected function expectedResourceObjectForArticleComments() {
    return [
      'type' => 'migration',
      'id' => '774d80647db1b74d15203514b56ab7ec-Article comments',
      'attributes' => [
        'label' => 'Article comments',
        'importedCount' => 0,
        'processedCount' => 0,
        'totalCount' => 2,
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
              'id' => '5e2f8ee473fdebc99fef4dc9e7ee3146-Article',
              'meta' => [
                'dependencyReasons' => [
                  'd7_node_type:article',
                  'd7_node_complete:article',
                ],
              ],
            ],
            [
              'type' => 'migration',
              'id' => '31f60fde4105c62db89a38339ad1bebc-Shared structure for comments',
              'meta' => [
                'dependencyReasons' => [
                  'd7_field:comment',
                  'd7_view_modes:comment',
                ],
              ],
            ],
            [
              'type' => 'migration',
              'id' => '6aa6b4bd50a2b501f0c761ccf2a08227-Filter format configuration',
              'meta' => [
                'dependencyReasons' => [
                  'd7_filter_format',
                ],
              ],
            ],
          ],
        ],
        'consistsOf' => [
          'data' => [
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_comment_type:article',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_comment_field:article',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_comment_field_instance:article',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_comment_entity_display:article',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_comment_entity_form_display:article',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_comment_entity_form_display_subject:article',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_field_instance:comment:article',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_field_formatter_settings:comment:article',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_field_instance_widget_settings:comment:article',
            ],
            ['type' => 'migrationPlugin', 'id' => 'd7_comment:article (0 of 2)'],
          ],
        ],
      ],
      'links' => [
        'self' => [
          'href' => $this->apiUrl('migrationIndividual', ['774d80647db1b74d15203514b56ab7ec-Article comments'])
            ->toString(),
        ],
        'skip' => [
          'href' => $this->apiUrl('migrationIndividual', ['774d80647db1b74d15203514b56ab7ec-Article comments'])
            ->toString(),
          'title' => 'Skip',
          'rel' => UriDefinitions::LINK_REL_UPDATE_RESOURCE,
          'params' => [
            'confirm' => "I'm sure, this does not need to be migrated, at least not for now.",
            'data' => [
              'type' => 'migration',
              'id' => '774d80647db1b74d15203514b56ab7ec-Article comments',
              'attributes' => [
                'skipped' => TRUE,
              ],
            ],
          ],
        ],
        'preview-unmet-requirement:0' => [
          'href' => 'https://drupal.org/project/acquia_migrate#application-concept-no-unprocessed-supporting-configuration',
          'title' => 'Not all supporting configuration has been processed yet: Article comments (specifically: d7_comment_type:article, d7_comment_field:article, d7_comment_field_instance:article, d7_comment_entity_display:article, d7_comment_entity_form_display:article, d7_comment_entity_form_display_subject:article, d7_field_instance:comment:article, d7_field_formatter_settings:comment:article, d7_field_instance_widget_settings:comment:article), Article (specifically: d7_node_type:article), Shared structure for comments (specifically: d7_field:comment, d7_view_modes:comment), Filter format configuration (specifically: d7_filter_format).',
          'rel' => UriDefinitions::LINK_REL_UNMET_REQUIREMENT,
        ],
        'field-mapping' => [
          'href' => $this->apiUrl('migrationMapping', ['774d80647db1b74d15203514b56ab7ec-Article comments'])
            ->toString(),
          'title' => 'View mapping',
          'rel' => UriDefinitions::LINK_REL_MAPPING,
        ],
      ],
    ];
  }

  protected function expectedResourceObjectForBasicPageComments() {
    return [
      'type' => 'migration',
      'id' => 'c86b96c54128357e448e33e9d9c65dc0-Basic page comments',
      'attributes' => [
        'label' => 'Basic page comments',
        'importedCount' => 0,
        'processedCount' => 0,
        'totalCount' => 0,
        'completed' => FALSE,
        'stale' => FALSE,
        'skipped' => TRUE,
        'lastImported' => NULL,
        'activity' => 'idle',
      ],
      'relationships' => [
        'dependencies' => [
          'data' => [
            [
              'type' => 'migration',
              'id' => '2a3005e7dcf5be98a1c14bb6a845a2ee-Basic page',
              'meta' => [
                'dependencyReasons' => [
                  'd7_node_type:page',
                  'd7_node_complete:page',
                ],
              ],
            ],
            [
              'type' => 'migration',
              'id' => '31f60fde4105c62db89a38339ad1bebc-Shared structure for comments',
              'meta' => [
                'dependencyReasons' => [
                  'd7_field:comment',
                  'd7_view_modes:comment',
                ],
              ],
            ],
            [
              'type' => 'migration',
              'id' => '6aa6b4bd50a2b501f0c761ccf2a08227-Filter format configuration',
              'meta' => [
                'dependencyReasons' => [
                  'd7_filter_format',
                ],
              ],
            ],
          ],
        ],
        'consistsOf' => [
          'data' => [
            ['type' => 'migrationPlugin', 'id' => 'd7_comment_type:page'],
            ['type' => 'migrationPlugin', 'id' => 'd7_comment_field:page'],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_comment_field_instance:page',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_comment_entity_display:page',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_comment_entity_form_display:page',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_comment_entity_form_display_subject:page',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_field_instance:comment:page',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_field_formatter_settings:comment:page',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_field_instance_widget_settings:comment:page',
            ],
            ['type' => 'migrationPlugin', 'id' => 'd7_comment:page (0 of 0)'],
          ],
        ],
      ],
      'links' => [
        'self' => [
          'href' => $this->apiUrl('migrationIndividual', ['c86b96c54128357e448e33e9d9c65dc0-Basic page comments'])
            ->toString(),
        ],
        'unskip' => [
          'href' => $this->apiUrl('migrationIndividual', ['c86b96c54128357e448e33e9d9c65dc0-Basic page comments'])
            ->toString(),
          'title' => 'Unskip',
          'rel' => UriDefinitions::LINK_REL_UPDATE_RESOURCE,
          'params' => [
            'confirm' => FALSE,
            'data' => [
              'type' => 'migration',
              'id' => 'c86b96c54128357e448e33e9d9c65dc0-Basic page comments',
              'attributes' => [
                'skipped' => FALSE,
              ],
            ],
          ],
        ],
      ],
    ];
  }

  protected function expectedResourceObjectForBlogEntryComments() {
    return [
      'type' => 'migration',
      'id' => '4425a0301438f84fa2acb2b8ca35e4fb-Blog entry comments',
      'attributes' => [
        'label' => 'Blog entry comments',
        'importedCount' => 0,
        'processedCount' => 0,
        'totalCount' => 0,
        'completed' => FALSE,
        'stale' => FALSE,
        'skipped' => TRUE,
        'lastImported' => NULL,
        'activity' => 'idle',
      ],
      'relationships' => [
        'dependencies' => [
          'data' => [
            [
              'type' => 'migration',
              'id' => 'b1748df090fe2f6a22e0b68db85fcf76-Blog entry',
              'meta' => [
                'dependencyReasons' => [
                  'd7_node_type:blog',
                  'd7_node_complete:blog',
                ],
              ],
            ],
            [
              'type' => 'migration',
              'id' => '31f60fde4105c62db89a38339ad1bebc-Shared structure for comments',
              'meta' => [
                'dependencyReasons' => [
                  'd7_field:comment',
                  'd7_view_modes:comment',
                ],
              ],
            ],
            [
              'type' => 'migration',
              'id' => '6aa6b4bd50a2b501f0c761ccf2a08227-Filter format configuration',
              'meta' => [
                'dependencyReasons' => [
                  'd7_filter_format',
                ],
              ],
            ],
          ],
        ],
        'consistsOf' => [
          'data' => [
            ['type' => 'migrationPlugin', 'id' => 'd7_comment_type:blog'],
            ['type' => 'migrationPlugin', 'id' => 'd7_comment_field:blog'],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_comment_field_instance:blog',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_comment_entity_display:blog',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_comment_entity_form_display:blog',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_comment_entity_form_display_subject:blog',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_field_instance:comment:blog',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_field_formatter_settings:comment:blog',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_field_instance_widget_settings:comment:blog',
            ],
            ['type' => 'migrationPlugin', 'id' => 'd7_comment:blog (0 of 0)'],
          ],
        ],
      ],
      'links' => [
        'self' => [
          'href' => $this->apiUrl('migrationIndividual', ['4425a0301438f84fa2acb2b8ca35e4fb-Blog entry comments'])
            ->toString(),
        ],
        'unskip' => [
          'href' => $this->apiUrl('migrationIndividual', ['4425a0301438f84fa2acb2b8ca35e4fb-Blog entry comments'])
            ->toString(),
          'title' => 'Unskip',
          'rel' => UriDefinitions::LINK_REL_UPDATE_RESOURCE,
          'params' => [
            'confirm' => FALSE,
            'data' => [
              'type' => 'migration',
              'id' => '4425a0301438f84fa2acb2b8ca35e4fb-Blog entry comments',
              'attributes' => [
                'skipped' => FALSE,
              ],
            ],
          ],
        ],
      ],
    ];
  }

  protected function expectedResourceObjectForBookPageComments() {
    return [
      'type' => 'migration',
      'id' => 'a8189dc76b96bde5dd0d8c89b4523805-Book page comments',
      'attributes' => [
        'label' => 'Book page comments',
        'importedCount' => 0,
        'processedCount' => 0,
        'totalCount' => 0,
        'completed' => FALSE,
        'stale' => FALSE,
        'skipped' => TRUE,
        'lastImported' => NULL,
        'activity' => 'idle',
      ],
      'relationships' => [
        'dependencies' => [
          'data' => [
            [
              'type' => 'migration',
              'id' => '0611eb9dc2d6ffc1e2dfd16bda6e5cfc-Book page',
              'meta' => [
                'dependencyReasons' => [
                  'd7_node_type:book',
                  'd7_node_complete:book',
                ],
              ],
            ],
            [
              'type' => 'migration',
              'id' => '31f60fde4105c62db89a38339ad1bebc-Shared structure for comments',
              'meta' => [
                'dependencyReasons' => [
                  'd7_field:comment',
                  'd7_view_modes:comment',
                ],
              ],
            ],
            [
              'type' => 'migration',
              'id' => '6aa6b4bd50a2b501f0c761ccf2a08227-Filter format configuration',
              'meta' => [
                'dependencyReasons' => [
                  'd7_filter_format',
                ],
              ],
            ],
          ],
        ],
        'consistsOf' => [
          'data' => [
            ['type' => 'migrationPlugin', 'id' => 'd7_comment_type:book'],
            ['type' => 'migrationPlugin', 'id' => 'd7_comment_field:book'],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_comment_field_instance:book',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_comment_entity_display:book',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_comment_entity_form_display:book',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_comment_entity_form_display_subject:book',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_field_instance:comment:book',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_field_formatter_settings:comment:book',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_field_instance_widget_settings:comment:book',
            ],
            ['type' => 'migrationPlugin', 'id' => 'd7_comment:book (0 of 0)'],
          ],
        ],
      ],
      'links' => [
        'self' => [
          'href' => $this->apiUrl('migrationIndividual', ['a8189dc76b96bde5dd0d8c89b4523805-Book page comments'])
            ->toString(),
        ],
        'unskip' => [
          'href' => $this->apiUrl('migrationIndividual', ['a8189dc76b96bde5dd0d8c89b4523805-Book page comments'])
            ->toString(),
          'title' => 'Unskip',
          'rel' => UriDefinitions::LINK_REL_UPDATE_RESOURCE,
          'params' => [
            'confirm' => FALSE,
            'data' => [
              'type' => 'migration',
              'id' => 'a8189dc76b96bde5dd0d8c89b4523805-Book page comments',
              'attributes' => [
                'skipped' => FALSE,
              ],
            ],
          ],
        ],
      ],
    ];
  }

  protected function expectedResourceObjectForEntityTranslationTestComments() {
    return [
      'type' => 'migration',
      'id' => '463e795e0dd490fad731cba6a79ffea6-Entity translation test comments',
      'attributes' => [
        'label' => 'Entity translation test comments',
        'importedCount' => 0,
        'processedCount' => 0,
        'totalCount' => 0,
        'completed' => FALSE,
        'stale' => FALSE,
        'skipped' => TRUE,
        'lastImported' => NULL,
        'activity' => 'idle',
      ],
      'relationships' => [
        'dependencies' => [
          'data' => [
            [
              'type' => 'migration',
              'id' => '2f9174e10c9aa48ca9bffa5c223e5d98-Entity translation test',
              'meta' => [
                'dependencyReasons' => [
                  'd7_node_type:et',
                  'd7_node_complete:et',
                ],
              ],
            ],
            [
              'type' => 'migration',
              'id' => '31f60fde4105c62db89a38339ad1bebc-Shared structure for comments',
              'meta' => [
                'dependencyReasons' => [
                  'd7_field:comment',
                  'd7_view_modes:comment',
                ],
              ],
            ],
            [
              'type' => 'migration',
              'id' => '6aa6b4bd50a2b501f0c761ccf2a08227-Filter format configuration',
              'meta' => [
                'dependencyReasons' => [
                  'd7_filter_format',
                ],
              ],
            ],
          ],
        ],
        'consistsOf' => [
          'data' => [
            ['type' => 'migrationPlugin', 'id' => 'd7_comment_type:et'],
            ['type' => 'migrationPlugin', 'id' => 'd7_comment_field:et'],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_comment_field_instance:et',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_comment_entity_display:et',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_comment_entity_form_display:et',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_comment_entity_form_display_subject:et',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_field_instance:comment:et',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_field_formatter_settings:comment:et',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_field_instance_widget_settings:comment:et',
            ],
            ['type' => 'migrationPlugin', 'id' => 'd7_comment:et (0 of 0)'],
          ],
        ],
      ],
      'links' => [
        'self' => [
          'href' => $this->apiUrl('migrationIndividual', ['463e795e0dd490fad731cba6a79ffea6-Entity translation test comments'])
            ->toString(),
        ],
        'unskip' => [
          'href' => $this->apiUrl('migrationIndividual', ['463e795e0dd490fad731cba6a79ffea6-Entity translation test comments'])
            ->toString(),
          'title' => 'Unskip',
          'rel' => UriDefinitions::LINK_REL_UPDATE_RESOURCE,
          'params' => [
            'confirm' => FALSE,
            'data' => [
              'type' => 'migration',
              'id' => '463e795e0dd490fad731cba6a79ffea6-Entity translation test comments',
              'attributes' => [
                'skipped' => FALSE,
              ],
            ],
          ],
        ],
      ],
    ];
  }

  protected function expectedResourceObjectForForumTopicComments() {
    return [
      'type' => 'migration',
      'id' => '1e453cdeea48b6f9009831598378de09-Forum topic comments',
      'attributes' => [
        'label' => 'Forum topic comments',
        'importedCount' => 0,
        'processedCount' => 0,
        'totalCount' => 0,
        'completed' => FALSE,
        'stale' => FALSE,
        'skipped' => TRUE,
        'lastImported' => NULL,
        'activity' => 'idle',
      ],
      'relationships' => [
        'dependencies' => [
          'data' => [
            [
              'type' => 'migration',
              'id' => 'cfe8acbd869cc2c42fd30b2ca2214103-Forum topic',
              'meta' => [
                'dependencyReasons' => [
                  'd7_node_type:forum',
                  'd7_node_complete:forum',
                ],
              ],
            ],
            [
              'type' => 'migration',
              'id' => '31f60fde4105c62db89a38339ad1bebc-Shared structure for comments',
              'meta' => [
                'dependencyReasons' => [
                  'd7_field:comment',
                  'd7_view_modes:comment',
                ],
              ],
            ],
            [
              'type' => 'migration',
              'id' => '6aa6b4bd50a2b501f0c761ccf2a08227-Filter format configuration',
              'meta' => [
                'dependencyReasons' => [
                  'd7_filter_format',
                ],
              ],
            ],
          ],
        ],
        'consistsOf' => [
          'data' => [
            ['type' => 'migrationPlugin', 'id' => 'd7_comment_type:forum'],
            ['type' => 'migrationPlugin', 'id' => 'd7_comment_field:forum'],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_comment_field_instance:forum',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_comment_entity_display:forum',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_comment_entity_form_display:forum',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_comment_entity_form_display_subject:forum',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_field_instance:comment:forum',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_field_formatter_settings:comment:forum',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_field_instance_widget_settings:comment:forum',
            ],
            ['type' => 'migrationPlugin', 'id' => 'd7_comment:forum (0 of 0)'],
          ],
        ],
      ],
      'links' => [
        'self' => [
          'href' => $this->apiUrl('migrationIndividual', ['1e453cdeea48b6f9009831598378de09-Forum topic comments'])
            ->toString(),
        ],
        'unskip' => [
          'href' => $this->apiUrl('migrationIndividual', ['1e453cdeea48b6f9009831598378de09-Forum topic comments'])
            ->toString(),
          'title' => 'Unskip',
          'rel' => UriDefinitions::LINK_REL_UPDATE_RESOURCE,
          'params' => [
            'confirm' => FALSE,
            'data' => [
              'type' => 'migration',
              'id' => '1e453cdeea48b6f9009831598378de09-Forum topic comments',
              'attributes' => [
                'skipped' => FALSE,
              ],
            ],
          ],
        ],
      ],
    ];
  }

  protected function expectedResourceObjectForSujetDeDiscussionTaxonomyTerms() {
    return [
      'type' => 'migration',
      'id' => '77dbe7f5d22c6bebefc3475f5b9acba9-Sujet de discussion taxonomy terms',
      'attributes' => [
        'label' => 'Sujet de discussion taxonomy terms',
        'importedCount' => 0,
        'processedCount' => 0,
        'totalCount' => 5,
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
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_taxonomy_vocabulary:sujet_de_discussion',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_taxonomy_term:sujet_de_discussion (0 of 5)',
            ],
          ],
        ],
      ],
      'links' => [
        'self' => [
          'href' => $this->apiUrl('migrationIndividual', ['77dbe7f5d22c6bebefc3475f5b9acba9-Sujet de discussion taxonomy terms'])
            ->toString(),
        ],
        'import' => [
          'href' => $this->getMigrationStartHref('77dbe7f5d22c6bebefc3475f5b9acba9-Sujet de discussion taxonomy terms'),
          'title' => 'Import',
          'rel' => UriDefinitions::LINK_REL_START_BATCH_PROCESS,
        ],
        'skip' => [
          'href' => $this->apiUrl('migrationIndividual', ['77dbe7f5d22c6bebefc3475f5b9acba9-Sujet de discussion taxonomy terms'])
            ->toString(),
          'title' => 'Skip',
          'rel' => UriDefinitions::LINK_REL_UPDATE_RESOURCE,
          'params' => [
            'confirm' => "I'm sure, this does not need to be migrated, at least not for now.",
            'data' => [
              'type' => 'migration',
              'id' => '77dbe7f5d22c6bebefc3475f5b9acba9-Sujet de discussion taxonomy terms',
              'attributes' => [
                'skipped' => TRUE,
              ],
            ],
          ],
        ],
        'preview-unmet-requirement:0' => [
          'href' => 'https://drupal.org/project/acquia_migrate#application-concept-no-unprocessed-supporting-configuration',
          'title' => 'Not all supporting configuration has been processed yet: Sujet de discussion taxonomy terms (specifically: d7_taxonomy_vocabulary:sujet_de_discussion).',
          'rel' => UriDefinitions::LINK_REL_UNMET_REQUIREMENT,
        ],
        'field-mapping' => [
          'href' => $this->apiUrl('migrationMapping', ['77dbe7f5d22c6bebefc3475f5b9acba9-Sujet de discussion taxonomy terms'])
            ->toString(),
          'title' => 'View mapping',
          'rel' => UriDefinitions::LINK_REL_MAPPING,
        ],
      ],
    ];
  }

  protected function expectedResourceObjectForTagsTaxonomyTerms() {
    return [
      'type' => 'migration',
      'id' => '8638466f6e83d1f03465a4864cbb0461-Tags taxonomy terms',
      'attributes' => [
        'label' => 'Tags taxonomy terms',
        'importedCount' => 0,
        'processedCount' => 0,
        'totalCount' => 10,
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
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_taxonomy_vocabulary:tags',
            ],
            ['type' => 'migrationPlugin', 'id' => 'd7_taxonomy_term:tags (0 of 10)'],
          ],
        ],
      ],
      'links' => [
        'self' => [
          'href' => $this->apiUrl('migrationIndividual', ['8638466f6e83d1f03465a4864cbb0461-Tags taxonomy terms'])
            ->toString(),
        ],
        'import' => [
          'href' => $this->getMigrationStartHref('8638466f6e83d1f03465a4864cbb0461-Tags taxonomy terms'),
          'title' => 'Import',
          'rel' => UriDefinitions::LINK_REL_START_BATCH_PROCESS,
        ],
        'skip' => [
          'href' => $this->apiUrl('migrationIndividual', ['8638466f6e83d1f03465a4864cbb0461-Tags taxonomy terms'])
            ->toString(),
          'title' => 'Skip',
          'rel' => UriDefinitions::LINK_REL_UPDATE_RESOURCE,
          'params' => [
            'confirm' => "I'm sure, this does not need to be migrated, at least not for now.",
            'data' => [
              'type' => 'migration',
              'id' => '8638466f6e83d1f03465a4864cbb0461-Tags taxonomy terms',
              'attributes' => [
                'skipped' => TRUE,
              ],
            ],
          ],
        ],
        'preview-unmet-requirement:0' => [
          'href' => 'https://drupal.org/project/acquia_migrate#application-concept-no-unprocessed-supporting-configuration',
          'title' => 'Not all supporting configuration has been processed yet: Tags taxonomy terms (specifically: d7_taxonomy_vocabulary:tags).',
          'rel' => UriDefinitions::LINK_REL_UNMET_REQUIREMENT,
        ],
        'field-mapping' => [
          'href' => $this->apiUrl('migrationMapping', ['8638466f6e83d1f03465a4864cbb0461-Tags taxonomy terms'])
            ->toString(),
          'title' => 'View mapping',
          'rel' => UriDefinitions::LINK_REL_MAPPING,
        ],
      ],
    ];
  }

  protected function expectedResourceObjectForTestVocabularyTaxonomyTerms() {
    return [
      'type' => 'migration',
      'id' => '858158392a46f33c9e9bbbb36e7abd1f-Test Vocabulary taxonomy terms',
      'attributes' => [
        'label' => 'Test Vocabulary taxonomy terms',
        'importedCount' => 0,
        'processedCount' => 0,
        'totalCount' => 4,
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
              'id' => 'c9adf432befc3cd4b14c496e6c5ceb4c-Shared structure for taxonomy terms',
              'meta' => [
                'dependencyReasons' => [
                  'd7_field:taxonomy_term',
                  'd7_view_modes:taxonomy_term',
                ],
              ],
            ],
          ],
        ],
        'consistsOf' => [
          'data' => [
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_taxonomy_vocabulary:test_vocabulary',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_field_instance:taxonomy_term:test_vocabulary',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_field_formatter_settings:taxonomy_term:test_vocabulary',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_field_instance_widget_settings:taxonomy_term:test_vocabulary',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_taxonomy_term:test_vocabulary (0 of 3)',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_url_alias:taxonomy_term:test_vocabulary (0 of 1)',
            ],

          ],
        ],
      ],
      'links' => [
        'self' => [
          'href' => $this->apiUrl('migrationIndividual', ['858158392a46f33c9e9bbbb36e7abd1f-Test Vocabulary taxonomy terms'])
            ->toString(),
        ],
        'skip' => [
          'href' => $this->apiUrl('migrationIndividual', ['858158392a46f33c9e9bbbb36e7abd1f-Test Vocabulary taxonomy terms'])
            ->toString(),
          'title' => 'Skip',
          'rel' => UriDefinitions::LINK_REL_UPDATE_RESOURCE,
          'params' => [
            'confirm' => "I'm sure, this does not need to be migrated, at least not for now.",
            'data' => [
              'type' => 'migration',
              'id' => '858158392a46f33c9e9bbbb36e7abd1f-Test Vocabulary taxonomy terms',
              'attributes' => [
                'skipped' => TRUE,
              ],
            ],
          ],
        ],
        'preview-unmet-requirement:0' => [
          'href' => 'https://drupal.org/project/acquia_migrate#application-concept-no-unprocessed-supporting-configuration',
          'title' => 'Not all supporting configuration has been processed yet: Test Vocabulary taxonomy terms (specifically: d7_taxonomy_vocabulary:test_vocabulary, d7_field_instance:taxonomy_term:test_vocabulary, d7_field_formatter_settings:taxonomy_term:test_vocabulary, d7_field_instance_widget_settings:taxonomy_term:test_vocabulary), Shared structure for taxonomy terms (specifically: d7_field:taxonomy_term, d7_view_modes:taxonomy_term).',
          'rel' => UriDefinitions::LINK_REL_UNMET_REQUIREMENT,
        ],
        'field-mapping' => [
          'href' => $this->apiUrl('migrationMapping', ['858158392a46f33c9e9bbbb36e7abd1f-Test Vocabulary taxonomy terms'])
            ->toString(),
          'title' => 'View mapping',
          'rel' => UriDefinitions::LINK_REL_MAPPING,
        ],
      ],
    ];
  }

  protected function expectedResourceObjectForTestContentTypeComments() {
    return [
      'type' => 'migration',
      'id' => '98161296e243d077a946262f87f4f97c-Test content type comments',
      'attributes' => [
        'label' => 'Test content type comments',
        'importedCount' => 0,
        'processedCount' => 0,
        'totalCount' => 2,
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
              'id' => '435d8f3f26aa9bd74c92d0fa58bff120-Test content type',
              'meta' => [
                'dependencyReasons' => [
                  'd7_node_type:test_content_type',
                  'd7_node_complete:test_content_type',
                ],
              ],
            ],
            [
              'type' => 'migration',
              'id' => '31f60fde4105c62db89a38339ad1bebc-Shared structure for comments',
              'meta' => [
                'dependencyReasons' => [
                  'd7_field:comment',
                  'd7_view_modes:comment',
                ],
              ],
            ],
            [
              'type' => 'migration',
              'id' => '6aa6b4bd50a2b501f0c761ccf2a08227-Filter format configuration',
              'meta' => [
                'dependencyReasons' => [
                  'd7_filter_format',
                ],
              ],
            ],
          ],
        ],
        'consistsOf' => [
          'data' => [
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_comment_type:test_content_type',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_comment_field:test_content_type',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_comment_field_instance:test_content_type',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_comment_entity_display:test_content_type',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_comment_entity_form_display:test_content_type',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_comment_entity_form_display_subject:test_content_type',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_field_instance:comment:test_content_type',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_field_formatter_settings:comment:test_content_type',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_field_instance_widget_settings:comment:test_content_type',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_comment:test_content_type (0 of 2)',
            ],
          ],
        ],
      ],
      'links' => [
        'self' => [
          'href' => $this->apiUrl('migrationIndividual', ['98161296e243d077a946262f87f4f97c-Test content type comments'])
            ->toString(),
        ],
        'skip' => [
          'href' => $this->apiUrl('migrationIndividual', ['98161296e243d077a946262f87f4f97c-Test content type comments'])
            ->toString(),
          'title' => 'Skip',
          'rel' => UriDefinitions::LINK_REL_UPDATE_RESOURCE,
          'params' => [
            'confirm' => "I'm sure, this does not need to be migrated, at least not for now.",
            'data' => [
              'type' => 'migration',
              'id' => '98161296e243d077a946262f87f4f97c-Test content type comments',
              'attributes' => [
                'skipped' => TRUE,
              ],
            ],
          ],
        ],
        'preview-unmet-requirement:0' => [
          'href' => 'https://drupal.org/project/acquia_migrate#application-concept-no-unprocessed-supporting-configuration',
          'title' => 'Not all supporting configuration has been processed yet: Test content type comments (specifically: d7_comment_type:test_content_type, d7_comment_field:test_content_type, d7_comment_field_instance:test_content_type, d7_comment_entity_display:test_content_type, d7_comment_entity_form_display:test_content_type, d7_comment_entity_form_display_subject:test_content_type, d7_field_instance:comment:test_content_type, d7_field_formatter_settings:comment:test_content_type, d7_field_instance_widget_settings:comment:test_content_type), Test content type (specifically: d7_node_type:test_content_type), Shared structure for comments (specifically: d7_field:comment, d7_view_modes:comment), Filter format configuration (specifically: d7_filter_format).',
          'rel' => UriDefinitions::LINK_REL_UNMET_REQUIREMENT,
        ],
        'field-mapping' => [
          'href' => $this->apiUrl('migrationMapping', ['98161296e243d077a946262f87f4f97c-Test content type comments'])
            ->toString(),
          'title' => 'View mapping',
          'rel' => UriDefinitions::LINK_REL_MAPPING,
        ],
      ],
    ];
  }

  protected function expectedResourceObjectForVocabFixedTaxonomyTerms() {
    return [
      'type' => 'migration',
      'id' => '5ba10dfebe289e3297a250327472bc0a-VocabFixed taxonomy terms',
      'attributes' => [
        'label' => 'VocabFixed taxonomy terms',
        'importedCount' => 0,
        'processedCount' => 0,
        'totalCount' => 1,
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
              'id' => 'c9adf432befc3cd4b14c496e6c5ceb4c-Shared structure for taxonomy terms',
              'meta' => [
                'dependencyReasons' => [
                  'd7_field:taxonomy_term',
                  'd7_view_modes:taxonomy_term',
                ],
              ],
            ],
          ],
        ],
        'consistsOf' => [
          'data' => [
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_taxonomy_vocabulary:vocabfixed',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_field_instance:taxonomy_term:vocabfixed',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_field_formatter_settings:taxonomy_term:vocabfixed',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_field_instance_widget_settings:taxonomy_term:vocabfixed',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_taxonomy_term:vocabfixed (0 of 1)',
            ],
          ],
        ],
      ],
      'links' => [
        'self' => [
          'href' => $this->apiUrl('migrationIndividual', ['5ba10dfebe289e3297a250327472bc0a-VocabFixed taxonomy terms'])
            ->toString(),
        ],
        'skip' => [
          'href' => $this->apiUrl('migrationIndividual', ['5ba10dfebe289e3297a250327472bc0a-VocabFixed taxonomy terms'])
            ->toString(),
          'title' => 'Skip',
          'rel' => UriDefinitions::LINK_REL_UPDATE_RESOURCE,
          'params' => [
            'confirm' => "I'm sure, this does not need to be migrated, at least not for now.",
            'data' => [
              'type' => 'migration',
              'id' => '5ba10dfebe289e3297a250327472bc0a-VocabFixed taxonomy terms',
              'attributes' => [
                'skipped' => TRUE,
              ],
            ],
          ],
        ],
        'preview-unmet-requirement:0' => [
          'href' => 'https://drupal.org/project/acquia_migrate#application-concept-no-unprocessed-supporting-configuration',
          'title' => 'Not all supporting configuration has been processed yet: VocabFixed taxonomy terms (specifically: d7_taxonomy_vocabulary:vocabfixed, d7_field_instance:taxonomy_term:vocabfixed, d7_field_formatter_settings:taxonomy_term:vocabfixed, d7_field_instance_widget_settings:taxonomy_term:vocabfixed), Shared structure for taxonomy terms (specifically: d7_field:taxonomy_term, d7_view_modes:taxonomy_term).',
          'rel' => UriDefinitions::LINK_REL_UNMET_REQUIREMENT,
        ],
        'field-mapping' => [
          'href' => $this->apiUrl('migrationMapping', ['5ba10dfebe289e3297a250327472bc0a-VocabFixed taxonomy terms'])
            ->toString(),
          'title' => 'View mapping',
          'rel' => UriDefinitions::LINK_REL_MAPPING,
        ],
      ],
    ];
  }

  protected function expectedResourceObjectForVocabLocalizedTaxonomyTerms() {
    return [
      'type' => 'migration',
      'id' => '3c182f618268cb03638f443136874649-VocabLocalized taxonomy terms',
      'attributes' => [
        'label' => 'VocabLocalized taxonomy terms',
        'importedCount' => 0,
        'processedCount' => 0,
        'totalCount' => 2,
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
              'id' => 'c9adf432befc3cd4b14c496e6c5ceb4c-Shared structure for taxonomy terms',
              'meta' => [
                'dependencyReasons' => [
                  'd7_field:taxonomy_term',
                  'd7_view_modes:taxonomy_term',
                ],
              ],
            ],
          ],
        ],
        'consistsOf' => [
          'data' => [
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_taxonomy_vocabulary:vocablocalized',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_field_instance:taxonomy_term:vocablocalized',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_field_formatter_settings:taxonomy_term:vocablocalized',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_field_instance_widget_settings:taxonomy_term:vocablocalized',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_taxonomy_term:vocablocalized (0 of 2)',
            ],
          ],
        ],
      ],
      'links' => [
        'self' => [
          'href' => $this->apiUrl('migrationIndividual', ['3c182f618268cb03638f443136874649-VocabLocalized taxonomy terms'])
            ->toString(),
        ],
        'skip' => [
          'href' => $this->apiUrl('migrationIndividual', ['3c182f618268cb03638f443136874649-VocabLocalized taxonomy terms'])
            ->toString(),
          'title' => 'Skip',
          'rel' => UriDefinitions::LINK_REL_UPDATE_RESOURCE,
          'params' => [
            'confirm' => "I'm sure, this does not need to be migrated, at least not for now.",
            'data' => [
              'type' => 'migration',
              'id' => '3c182f618268cb03638f443136874649-VocabLocalized taxonomy terms',
              'attributes' => [
                'skipped' => TRUE,
              ],
            ],
          ],
        ],
        'preview-unmet-requirement:0' => [
          'href' => 'https://drupal.org/project/acquia_migrate#application-concept-no-unprocessed-supporting-configuration',
          'title' => 'Not all supporting configuration has been processed yet: VocabLocalized taxonomy terms (specifically: d7_taxonomy_vocabulary:vocablocalized, d7_field_instance:taxonomy_term:vocablocalized, d7_field_formatter_settings:taxonomy_term:vocablocalized, d7_field_instance_widget_settings:taxonomy_term:vocablocalized), Shared structure for taxonomy terms (specifically: d7_field:taxonomy_term, d7_view_modes:taxonomy_term).',
          'rel' => UriDefinitions::LINK_REL_UNMET_REQUIREMENT,
        ],
        'field-mapping' => [
          'href' => $this->apiUrl('migrationMapping', ['3c182f618268cb03638f443136874649-VocabLocalized taxonomy terms'])
            ->toString(),
          'title' => 'View mapping',
          'rel' => UriDefinitions::LINK_REL_MAPPING,
        ],
      ],
    ];
  }

  protected function expectedResourceObjectForVocabLocalized2TaxonomyTerms() {
    return [
      'type' => 'migration',
      'id' => 'a25d0673eae2055740c3de6aefb519a6-VocabLocalized2 taxonomy terms',
      'attributes' => [
        'label' => 'VocabLocalized2 taxonomy terms',
        'importedCount' => 0,
        'processedCount' => 0,
        'totalCount' => 1,
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
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_taxonomy_vocabulary:vocablocalized2',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_taxonomy_term:vocablocalized2 (0 of 1)',
            ],
          ],
        ],
      ],
      'links' => [
        'self' => [
          'href' => $this->apiUrl('migrationIndividual', ['a25d0673eae2055740c3de6aefb519a6-VocabLocalized2 taxonomy terms'])
            ->toString(),
        ],
        'import' => [
          'href' => $this->getMigrationStartHref('a25d0673eae2055740c3de6aefb519a6-VocabLocalized2 taxonomy terms'),
          'title' => 'Import',
          'rel' => UriDefinitions::LINK_REL_START_BATCH_PROCESS,
        ],
        'skip' => [
          'href' => $this->apiUrl('migrationIndividual', ['a25d0673eae2055740c3de6aefb519a6-VocabLocalized2 taxonomy terms'])
            ->toString(),
          'title' => 'Skip',
          'rel' => UriDefinitions::LINK_REL_UPDATE_RESOURCE,
          'params' => [
            'confirm' => "I'm sure, this does not need to be migrated, at least not for now.",
            'data' => [
              'type' => 'migration',
              'id' => 'a25d0673eae2055740c3de6aefb519a6-VocabLocalized2 taxonomy terms',
              'attributes' => [
                'skipped' => TRUE,
              ],
            ],
          ],
        ],
        'preview-unmet-requirement:0' => [
          'href' => 'https://drupal.org/project/acquia_migrate#application-concept-no-unprocessed-supporting-configuration',
          'title' => 'Not all supporting configuration has been processed yet: VocabLocalized2 taxonomy terms (specifically: d7_taxonomy_vocabulary:vocablocalized2).',
          'rel' => UriDefinitions::LINK_REL_UNMET_REQUIREMENT,
        ],
        'field-mapping' => [
          'href' => $this->apiUrl('migrationMapping', ['a25d0673eae2055740c3de6aefb519a6-VocabLocalized2 taxonomy terms'])
            ->toString(),
          'title' => 'View mapping',
          'rel' => UriDefinitions::LINK_REL_MAPPING,
        ],
      ],
    ];
  }

  protected function expectedResourceObjectForVocabTranslateTaxonomyTerms() {
    return [
      'type' => 'migration',
      'id' => '3380423a44155f5b4dcedbb2a4bde666-VocabTranslate taxonomy terms',
      'attributes' => [
        'label' => 'VocabTranslate taxonomy terms',
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
          'data' => [
            [
              'type' => 'migration',
              'id' => 'c9adf432befc3cd4b14c496e6c5ceb4c-Shared structure for taxonomy terms',
              'meta' => [
                'dependencyReasons' => [
                  'd7_field:taxonomy_term',
                  'd7_view_modes:taxonomy_term',
                ],
              ],
            ],
          ],
        ],
        'consistsOf' => [
          'data' => [
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_taxonomy_vocabulary:vocabtranslate',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_field_instance:taxonomy_term:vocabtranslate',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_field_formatter_settings:taxonomy_term:vocabtranslate',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_field_instance_widget_settings:taxonomy_term:vocabtranslate',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_taxonomy_term:vocabtranslate (0 of 3)',
            ],
          ],
        ],
      ],
      'links' => [
        'self' => [
          'href' => $this->apiUrl('migrationIndividual', ['3380423a44155f5b4dcedbb2a4bde666-VocabTranslate taxonomy terms'])
            ->toString(),
        ],
        'skip' => [
          'href' => $this->apiUrl('migrationIndividual', ['3380423a44155f5b4dcedbb2a4bde666-VocabTranslate taxonomy terms'])
            ->toString(),
          'title' => 'Skip',
          'rel' => UriDefinitions::LINK_REL_UPDATE_RESOURCE,
          'params' => [
            'confirm' => "I'm sure, this does not need to be migrated, at least not for now.",
            'data' => [
              'type' => 'migration',
              'id' => '3380423a44155f5b4dcedbb2a4bde666-VocabTranslate taxonomy terms',
              'attributes' => [
                'skipped' => TRUE,
              ],
            ],
          ],
        ],
        'preview-unmet-requirement:0' => [
          'href' => 'https://drupal.org/project/acquia_migrate#application-concept-no-unprocessed-supporting-configuration',
          'title' => 'Not all supporting configuration has been processed yet: VocabTranslate taxonomy terms (specifically: d7_taxonomy_vocabulary:vocabtranslate, d7_field_instance:taxonomy_term:vocabtranslate, d7_field_formatter_settings:taxonomy_term:vocabtranslate, d7_field_instance_widget_settings:taxonomy_term:vocabtranslate), Shared structure for taxonomy terms (specifically: d7_field:taxonomy_term, d7_view_modes:taxonomy_term).',
          'rel' => UriDefinitions::LINK_REL_UNMET_REQUIREMENT,
        ],
        'field-mapping' => [
          'href' => $this->apiUrl('migrationMapping', ['3380423a44155f5b4dcedbb2a4bde666-VocabTranslate taxonomy terms'])
            ->toString(),
          'title' => 'View mapping',
          'rel' => UriDefinitions::LINK_REL_MAPPING,
        ],
      ],
    ];
  }

  protected function expectedResourceObjectForLongVocabularyNameTaxonomyTerms() {
    return [
      'type' => 'migration',
      'id' => 'a4515e7140c7450d7901b093bdf97602-vocabulary name clearly different than machine name and much longer than thirty two characters taxonomy terms',
      'attributes' => [
        'label' => 'vocabulary name clearly different than machine name and much longer than thirty two characters taxonomy terms',
        'importedCount' => 0,
        'processedCount' => 0,
        'totalCount' => 0,
        'completed' => FALSE,
        'stale' => FALSE,
        'skipped' => TRUE,
        'lastImported' => NULL,
        'activity' => 'idle',
      ],
      'relationships' => [
        'dependencies' => [
          'data' => [],
        ],
        'consistsOf' => [
          'data' => [
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_taxonomy_vocabulary:vocabulary_name_much_longer_than_thirty_two_characters',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_taxonomy_term:vocabulary_name_much_longer_than_thirty_two_characters (0 of 0)',
            ],
          ],
        ],
      ],
      'links' => [
        'self' => [
          'href' => $this->apiUrl('migrationIndividual', ['a4515e7140c7450d7901b093bdf97602-vocabulary name clearly different than machine name and much longer than thirty two characters taxonomy terms'])
            ->toString(),
        ],
        'unskip' => [
          'href' => $this->apiUrl('migrationIndividual', ['a4515e7140c7450d7901b093bdf97602-vocabulary name clearly different than machine name and much longer than thirty two characters taxonomy terms'])
            ->toString(),
          'title' => 'Unskip',
          'rel' => UriDefinitions::LINK_REL_UPDATE_RESOURCE,
          'params' => [
            'confirm' => FALSE,
            'data' => [
              'type' => 'migration',
              'id' => 'a4515e7140c7450d7901b093bdf97602-vocabulary name clearly different than machine name and much longer than thirty two characters taxonomy terms',
              'attributes' => [
                'skipped' => FALSE,
              ],
            ],
          ],
        ],
      ],
    ];
  }

  protected function expectedResourceObjectForBlockPlacements() {
    return [
      'type' => 'migration',
      'id' => 'a4e83b176b99cd6740fc268cbe9ccf8e-Block placements',
      'attributes' => [
        'label' => 'Block placements',
        'importedCount' => 0,
        'processedCount' => 0,
        'totalCount' => 17,
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
              'id' => 'de1d54496a61faa96f1d5ca8164f0bd6-Custom blocks',
              'meta' => [
                'dependencyReasons' => [
                  'd7_custom_block',
                ],
              ],
            ],
            [
              'type' => 'migration',
              'id' => 'dbdd6377389228728e6ab594c50ad011-User accounts',
              'meta' => [
                'dependencyReasons' => [
                  'd7_user_role',
                ],
              ],
            ],
          ],
        ],
        'consistsOf' => [
          'data' => [
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_block:bartik:block_content (0 of 1)',
            ],
            ['type' => 'migrationPlugin', 'id' => 'd7_block:bartik:simple (0 of 6)'],
            ['type' => 'migrationPlugin', 'id' => 'd7_block:seven:simple (0 of 3)'],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_block:stark:block_content (0 of 1)',
            ],
            ['type' => 'migrationPlugin', 'id' => 'd7_block:stark:simple (0 of 6)'],
          ],
        ],
      ],
      'links' => [
        'self' => [
          'href' => $this->apiUrl('migrationIndividual', ['a4e83b176b99cd6740fc268cbe9ccf8e-Block placements'])
            ->toString(),
        ],
        'skip' => [
          'href' => $this->apiUrl('migrationIndividual', ['a4e83b176b99cd6740fc268cbe9ccf8e-Block placements'])
            ->toString(),
          'title' => 'Skip',
          'rel' => UriDefinitions::LINK_REL_UPDATE_RESOURCE,
          'params' => [
            'confirm' => "I'm sure, this does not need to be migrated, at least not for now.",
            'data' => [
              'type' => 'migration',
              'id' => 'a4e83b176b99cd6740fc268cbe9ccf8e-Block placements',
              'attributes' => [
                'skipped' => TRUE,
              ],
            ],
          ],
        ],
      ],
    ];
  }

  protected function expectedResourceObjectForCustomBlocks() {
    return [
      'type' => 'migration',
      'id' => 'de1d54496a61faa96f1d5ca8164f0bd6-Custom blocks',
      'attributes' => [
        'label' => 'Custom blocks',
        'importedCount' => 0,
        'processedCount' => 0,
        'totalCount' => 1,
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
              'id' => '6aa6b4bd50a2b501f0c761ccf2a08227-Filter format configuration',
              'meta' => [
                'dependencyReasons' => [
                  'd7_filter_format',
                ],
              ],
            ],
          ],
        ],
        'consistsOf' => [
          'data' => [
            ['type' => 'migrationPlugin', 'id' => 'block_content_type'],
            [
              'type' => 'migrationPlugin',
              'id' => 'block_content_body_field',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'block_content_entity_display',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'block_content_entity_form_display',
            ],
            ['type' => 'migrationPlugin', 'id' => 'd7_custom_block (0 of 1)'],
          ],
        ],
      ],
      'links' => [
        'self' => [
          'href' => $this->apiUrl('migrationIndividual', ['de1d54496a61faa96f1d5ca8164f0bd6-Custom blocks'])
            ->toString(),
        ],
        'skip' => [
          'href' => $this->apiUrl('migrationIndividual', ['de1d54496a61faa96f1d5ca8164f0bd6-Custom blocks'])
            ->toString(),
          'title' => 'Skip',
          'rel' => UriDefinitions::LINK_REL_UPDATE_RESOURCE,
          'params' => [
            'confirm' => "I'm sure, this does not need to be migrated, at least not for now.",
            'data' => [
              'type' => 'migration',
              'id' => 'de1d54496a61faa96f1d5ca8164f0bd6-Custom blocks',
              'attributes' => [
                'skipped' => TRUE,
              ],
            ],
          ],
        ],
        'preview-unmet-requirement:0' => [
          'href' => 'https://drupal.org/project/acquia_migrate#application-concept-no-unprocessed-supporting-configuration',
          'title' => 'Not all supporting configuration has been processed yet: Custom blocks (specifically: block_content_type, block_content_body_field, block_content_entity_display, block_content_entity_form_display), Filter format configuration (specifically: d7_filter_format).',
          'rel' => UriDefinitions::LINK_REL_UNMET_REQUIREMENT,
        ],
        'field-mapping' => [
          'href' => $this->apiUrl('migrationMapping', ['de1d54496a61faa96f1d5ca8164f0bd6-Custom blocks'])
            ->toString(),
          'title' => 'View mapping',
          'rel' => UriDefinitions::LINK_REL_MAPPING,
        ],
      ],
    ];
  }

  protected function expectedResourceObjectForOtherUrlAliases() {
    return [
      'type' => 'migration',
      'id' => '2bbf636c69b114fe285d55f86b658864-URL aliases (remaining)',
      'attributes' => [
        'label' => 'URL aliases (remaining)',
        'importedCount' => 0,
        'processedCount' => 0,
        'totalCount' => 1,
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
              'id' => '774d80647db1b74d15203514b56ab7ec-Article comments',
              'meta' => [
                'dependencyReasons' => [
                  'd7_comment:article',
                ],
              ],
            ],
            [
              'type' => 'migration',
              'id' => 'edc34ab047ae3fdd812a122f57d6e152-Test long name comments',
              'meta' => [
                'dependencyReasons' => [
                  'd7_comment:a_thirty_two_character_type_name',
                ],
              ],
            ],
            [
              'type' => 'migration',
              'id' => '4425a0301438f84fa2acb2b8ca35e4fb-Blog entry comments',
              'meta' => [
                'dependencyReasons' => [
                  'd7_comment:blog',
                ],
              ],
            ],
            [
              'type' => 'migration',
              'id' => 'a8189dc76b96bde5dd0d8c89b4523805-Book page comments',
              'meta' => [
                'dependencyReasons' => [
                  'd7_comment:book',
                ],
              ],
            ],
            [
              'type' => 'migration',
              'id' => '463e795e0dd490fad731cba6a79ffea6-Entity translation test comments',
              'meta' => [
                'dependencyReasons' => [
                  'd7_comment:et',
                ],
              ],
            ],
            [
              'type' => 'migration',
              'id' => '1e453cdeea48b6f9009831598378de09-Forum topic comments',
              'meta' => [
                'dependencyReasons' => [
                  'd7_comment:forum',
                ],
              ],
            ],
            [
              'type' => 'migration',
              'id' => 'c86b96c54128357e448e33e9d9c65dc0-Basic page comments',
              'meta' => [
                'dependencyReasons' => [
                  'd7_comment:page',
                ],
              ],
            ],
            [
              'type' => 'migration',
              'id' => '98161296e243d077a946262f87f4f97c-Test content type comments',
              'meta' => [
                'dependencyReasons' => [
                  'd7_comment:test_content_type',
                ],
              ],
            ],
            [
              'type' => 'migration',
              'id' => 'dbdd6377389228728e6ab594c50ad011-User accounts',
              'meta' => [
                'dependencyReasons' => [
                  'd7_user',
                ],
              ],
            ],
          ],
        ],
        'consistsOf' => [
          'data' => [
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_url_alias:other (0 of 1)',
            ],
          ],
        ],
      ],
      'links' => [
        'self' => [
          'href' => $this->apiUrl('migrationIndividual', ['2bbf636c69b114fe285d55f86b658864-URL aliases (remaining)'])
            ->toString(),
        ],
        'skip' => [
          'href' => $this->apiUrl('migrationIndividual', ['2bbf636c69b114fe285d55f86b658864-URL aliases (remaining)'])
            ->toString(),
          'title' => 'Skip',
          'rel' => UriDefinitions::LINK_REL_UPDATE_RESOURCE,
          'params' => [
            'confirm' => "I'm sure, this does not need to be migrated, at least not for now.",
            'data' => [
              'type' => 'migration',
              'id' => '2bbf636c69b114fe285d55f86b658864-URL aliases (remaining)',
              'attributes' => [
                'skipped' => TRUE,
              ],
            ],
          ],
        ],
        'preview-by-offset' => [
          'href' => $this->apiUrl('migrationPreview', ['2bbf636c69b114fe285d55f86b658864-URL aliases (remaining)'])
            ->toString() . '?byOffset=0',
          'title' => 'Preview first row',
          'rel' => UriDefinitions::LINK_REL_PREVIEW,
        ],
        'preview-by-url' => [
          'href' => $this->apiUrl('migrationPreview', ['2bbf636c69b114fe285d55f86b658864-URL aliases (remaining)'])
            ->toString(),
          'title' => 'Preview by URL',
          'rel' => UriDefinitions::LINK_REL_PREVIEW,
          'uri-template:href' => $this->apiUrl('migrationPreview', ['2bbf636c69b114fe285d55f86b658864-URL aliases (remaining)'])
            ->toString() . '{?byUrl}',
          'uri-template:suggestions' => [
            [
              'label' => 'By source site URL',
              'variable' => 'byUrl',
              'cardinality' => 1,
            ],
          ],
        ],
        'field-mapping' => [
          'href' => $this->apiUrl('migrationMapping', ['2bbf636c69b114fe285d55f86b658864-URL aliases (remaining)'])
            ->toString(),
          'title' => 'View mapping',
          'rel' => UriDefinitions::LINK_REL_MAPPING,
        ],
      ],
    ];
  }

  protected function expectedResourceObjectForSiteConfiguration() {
    return [
      'type' => 'migration',
      'id' => 'b11ec035a0ea55f7bf0af42f84083be8-Site configuration',
      'attributes' => [
        'label' => 'Site configuration',
        'importedCount' => 0,
        'processedCount' => 0,
        'totalCount' => 59,
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
            ['type' => 'migrationPlugin', 'id' => 'd7_color (0 of 4)'],
            ['type' => 'migrationPlugin', 'id' => 'd7_dblog_settings (0 of 1)'],
            ['type' => 'migrationPlugin', 'id' => 'd7_filter_settings (0 of 1)'],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_global_theme_settings (0 of 1)',
            ],
            ['type' => 'migrationPlugin', 'id' => 'd7_image_settings (0 of 1)'],
            ['type' => 'migrationPlugin', 'id' => 'd7_node_settings (0 of 1)'],
            ['type' => 'migrationPlugin', 'id' => 'd7_search_settings (0 of 1)'],
            ['type' => 'migrationPlugin', 'id' => 'd7_syslog_settings (0 of 1)'],
            ['type' => 'migrationPlugin', 'id' => 'd7_system_authorize (0 of 1)'],
            ['type' => 'migrationPlugin', 'id' => 'd7_system_cron (0 of 1)'],
            ['type' => 'migrationPlugin', 'id' => 'd7_system_date (0 of 1)'],
            ['type' => 'migrationPlugin', 'id' => 'd7_system_file (0 of 1)'],
            ['type' => 'migrationPlugin', 'id' => 'd7_system_mail (0 of 1)'],
            ['type' => 'migrationPlugin', 'id' => 'd7_system_performance (0 of 1)'],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_theme_settings:bartik (0 of 1)',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_theme_settings:seven (0 of 1)',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_theme_settings:stark (0 of 1)',
            ],
            ['type' => 'migrationPlugin', 'id' => 'd7_user_flood (0 of 1)'],
            ['type' => 'migrationPlugin', 'id' => 'd7_user_mail (0 of 1)'],
            ['type' => 'migrationPlugin', 'id' => 'd7_user_settings (0 of 1)'],
            ['type' => 'migrationPlugin', 'id' => 'file_settings (0 of 1)'],
            ['type' => 'migrationPlugin', 'id' => 'menu_settings (0 of 1)'],
            ['type' => 'migrationPlugin', 'id' => 'system_image (0 of 1)'],
            ['type' => 'migrationPlugin', 'id' => 'system_image_gd (0 of 1)'],
            ['type' => 'migrationPlugin', 'id' => 'system_logging (0 of 1)'],
            ['type' => 'migrationPlugin', 'id' => 'system_maintenance (0 of 1)'],
            ['type' => 'migrationPlugin', 'id' => 'system_rss (0 of 1)'],
            ['type' => 'migrationPlugin', 'id' => 'system_site (0 of 1)'],
            ['type' => 'migrationPlugin', 'id' => 'taxonomy_settings (0 of 1)'],
            ['type' => 'migrationPlugin', 'id' => 'text_settings (0 of 1)'],
            ['type' => 'migrationPlugin', 'id' => 'contact_category (0 of 1)'],
            ['type' => 'migrationPlugin', 'id' => 'd7_action (0 of 18)'],
            ['type' => 'migrationPlugin', 'id' => 'd7_image_styles (0 of 3)'],
            ['type' => 'migrationPlugin', 'id' => 'd7_search_page (0 of 2)'],
            ['type' => 'migrationPlugin', 'id' => 'd7_contact_settings (0 of 1)'],
          ],
        ],
      ],
      'links' => [
        'self' => [
          'href' => $this->apiUrl('migrationIndividual', ['b11ec035a0ea55f7bf0af42f84083be8-Site configuration'])
            ->toString(),
        ],
        'import' => [
          'href' => $this->getMigrationStartHref('b11ec035a0ea55f7bf0af42f84083be8-Site configuration'),
          'title' => 'Import',
          'rel' => UriDefinitions::LINK_REL_START_BATCH_PROCESS,
        ],
        'skip' => [
          'href' => $this->apiUrl('migrationIndividual', ['b11ec035a0ea55f7bf0af42f84083be8-Site configuration'])
            ->toString(),
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
    ];
  }

  protected function expectedResourceObjectForTestLongName() {
    return [
      'type' => 'migration',
      'id' => 'bf67a85d728c178492c70179be595c48-Test long name',
      'attributes' => [
        'label' => 'Test long name',
        'importedCount' => 0,
        'processedCount' => 0,
        'totalCount' => 0,
        'completed' => FALSE,
        'stale' => FALSE,
        'skipped' => TRUE,
        'lastImported' => NULL,
        'activity' => 'idle',
      ],
      'relationships' => [
        'dependencies' => [
          'data' => [
            [
              'type' => 'migration',
              'id' => 'ef08b5721f28f83bc0f418fc5ae937a6-Shared structure for content items',
              'meta' => [
                'dependencyReasons' => [
                  'd7_field:node',
                  'd7_view_modes:node',
                ],
              ],
            ],
            [
              'type' => 'migration',
              'id' => '6aa6b4bd50a2b501f0c761ccf2a08227-Filter format configuration',
              'meta' => [
                'dependencyReasons' => [
                  'd7_filter_format',
                ],
              ],
            ],
            [
              'type' => 'migration',
              'id' => 'dbdd6377389228728e6ab594c50ad011-User accounts',
              'meta' => [
                'dependencyReasons' => [
                  'd7_user',
                ],
              ],
            ],
          ],
        ],
        'consistsOf' => [
          'data' => [
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_node_type:a_thirty_two_character_type_name',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_field_instance:node:a_thirty_two_character_type_name',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_field_formatter_settings:node:a_thirty_two_character_type_name',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_field_instance_widget_settings:node:a_thirty_two_character_type_name',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_node_complete:a_thirty_two_character_type_name (0 of 0)',
            ],
          ],
        ],
      ],
      'links' => [
        'self' => [
          'href' => $this->apiUrl('migrationIndividual', ['bf67a85d728c178492c70179be595c48-Test long name'])
            ->toString(),
        ],
        'unskip' => [
          'href' => $this->apiUrl('migrationIndividual', ['bf67a85d728c178492c70179be595c48-Test long name'])
            ->toString(),
          'title' => 'Unskip',
          'rel' => UriDefinitions::LINK_REL_UPDATE_RESOURCE,
          'params' => [
            'confirm' => FALSE,
            'data' => [
              'type' => 'migration',
              'id' => 'bf67a85d728c178492c70179be595c48-Test long name',
              'attributes' => [
                'skipped' => FALSE,
              ],
            ],
          ],
        ],
      ],
    ];
  }

  protected function expectedResourceObjectForTestLongNameComments() {
    return [
      'type' => 'migration',
      'id' => 'edc34ab047ae3fdd812a122f57d6e152-Test long name comments',
      'attributes' => [
        'label' => 'Test long name comments',
        'importedCount' => 0,
        'processedCount' => 0,
        'totalCount' => 0,
        'completed' => FALSE,
        'stale' => FALSE,
        'skipped' => TRUE,
        'lastImported' => NULL,
        'activity' => 'idle',
      ],
      'relationships' => [
        'dependencies' => [
          'data' => [
            [
              'type' => 'migration',
              'id' => 'bf67a85d728c178492c70179be595c48-Test long name',
              'meta' => [
                'dependencyReasons' => [
                  'd7_node_type:a_thirty_two_character_type_name',
                  'd7_node_complete:a_thirty_two_character_type_name',
                ],
              ],
            ],
            [
              'type' => 'migration',
              'id' => '31f60fde4105c62db89a38339ad1bebc-Shared structure for comments',
              'meta' => [
                'dependencyReasons' => [
                  'd7_field:comment',
                  'd7_view_modes:comment',
                ],
              ],
            ],
            [
              'type' => 'migration',
              'id' => '6aa6b4bd50a2b501f0c761ccf2a08227-Filter format configuration',
              'meta' => [
                'dependencyReasons' => [
                  'd7_filter_format',
                ],
              ],
            ],
          ],
        ],
        'consistsOf' => [
          'data' => [
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_comment_type:a_thirty_two_character_type_name',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_comment_field:a_thirty_two_character_type_name',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_comment_field_instance:a_thirty_two_character_type_name',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_comment_entity_display:a_thirty_two_character_type_name',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_comment_entity_form_display:a_thirty_two_character_type_name',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_comment_entity_form_display_subject:a_thirty_two_character_type_name',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_field_instance:comment:a_thirty_two_character_type_name',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_field_formatter_settings:comment:a_thirty_two_character_type_name',
            ],
            [
              'type' => 'migrationPlugin',
              'id' => 'd7_field_instance_widget_settings:comment:a_thirty_two_character_type_name',
            ],
            ['type' => 'migrationPlugin', 'id' => 'd7_comment:a_thirty_two_character_type_name (0 of 0)'],
          ],
        ],
      ],
      'links' => [
        'self' => [
          'href' => $this->apiUrl('migrationIndividual', ['edc34ab047ae3fdd812a122f57d6e152-Test long name comments'])
            ->toString(),
        ],
        'unskip' => [
          'href' => $this->apiUrl('migrationIndividual', ['edc34ab047ae3fdd812a122f57d6e152-Test long name comments'])
            ->toString(),
          'title' => 'Unskip',
          'rel' => UriDefinitions::LINK_REL_UPDATE_RESOURCE,
          'params' => [
            'confirm' => FALSE,
            'data' => [
              'type' => 'migration',
              'id' => 'edc34ab047ae3fdd812a122f57d6e152-Test long name comments',
              'attributes' => [
                'skipped' => FALSE,
              ],
            ],
          ],
        ],
      ],
    ];
  }

}
