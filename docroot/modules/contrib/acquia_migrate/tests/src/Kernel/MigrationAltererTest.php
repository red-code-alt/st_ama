<?php

namespace Drupal\Tests\acquia_migrate\Kernel;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\field\Plugin\migrate\D7FieldConfigurationMigrationDeriver;
use Drupal\node\Plugin\migrate\D6NodeDeriver;
use Drupal\node\Plugin\migrate\D7NodeDeriver;
use Drupal\system\Plugin\migrate\D7ThemeDeriver;
use Drupal\taxonomy\Plugin\migrate\D7TaxonomyTermDeriver;
use Drupal\Tests\migrate\Kernel\MigrateTestBase;

/**
 * Tests MigrationAlterer service.
 *
 * @coversDefaultClass \Drupal\acquia_migrate\MigrationAlterer
 *
 * @group acquia_migrate
 * @group acquia_migrate__core
 */
class MigrationAltererTest extends MigrateTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'acquia_migrate',
    'block_content',
    'migrate',
    'migrate_drupal',
    'node',
    'comment',
    'field',
    'taxonomy',
    'user',
  ];

  /**
   * The migration alterer.
   *
   * @var \Drupal\acquia_migrate\MigrationAlterer
   */
  protected $acquiaMigrationAlterer;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->acquiaMigrationAlterer = $this->container->get('acquia_migrate.migration_alterer');
  }

  /**
   * Tests the refine migration label functionality with a migration set.
   */
  public function testMigrationAltererRefineMigrationLabel() {
    ['migrations' => $migrations, 'expectations' => $expectations] = $this->refineMigrationLabelTestCases();
    $this->acquiaMigrationAlterer->refineMigrationsLabels($migrations);

    $this->assertEquals($migrations, $expectations);

    foreach (array_keys($migrations) as $migration_id) {
      $this->assertStringsAreEqual($migrations[$migration_id]['label'], $expectations[$migration_id]['label']);
    }
  }

  /**
   * Tests the rollbackable display conversion functionality.
   */
  public function testPersistFieldStorageConfigs() {
    ['migrations' => $migrations] = $this->persistFieldStorageConfigsTestCases();
    $field_storage_migrations = array_reduce($migrations, function ($carry, array $migration) {
      $destination_plugin = $migration['destination']['plugin'] ?? NULL;
      if ($destination_plugin === 'entity:field_storage_config') {
        $carry[] = $migration;
      };

      return $carry;
    });

    // Assertion on the test cases: make sure that we have at least one field
    // storage migration.
    $this->assertTrue(count($field_storage_migrations) > 0);
    // Assert that field storage migrations have "persist_with_no_fields" set to
    // TRUE.
    $this->acquiaMigrationAlterer->persistFieldStorageConfigs($field_storage_migrations);
    foreach ($field_storage_migrations as $field_storage_migration) {
      $this->assertSame('constants/persist_with_no_fields', $field_storage_migration['process']['persist_with_no_fields']);
      $this->assertTrue($field_storage_migration['source']['constants']['persist_with_no_fields']);
    }
  }

  /**
   * @covers ::addChangeTracking
   */
  public function testChangeTracking() {
    $test_cases = $this->getTestCasesAndExpectations();

    $migrations = array_map(function ($test_case) {
      return $test_case['migration_data'];
    }, $test_cases);
    $expectations = array_map(function ($test_case) {
      return $test_case['expected_change_tracking'] ?? NULL;
    }, $test_cases);

    foreach ($migrations as $id => $migration) {
      if (!isset($migration['source'])) {
        continue;
      }
      $this->assertArrayNotHasKey('high_water_property', $migration['source']);
      $this->assertArrayNotHasKey('track_changes', $migration['source']);
    }

    $this->acquiaMigrationAlterer->addChangeTracking($migrations);

    foreach ($migrations as $id => $migration) {
      switch ($expectations[$id]) {
        case 'high_water_property':
          $this->assertArrayHasKey('high_water_property', $migration['source']);
          $this->assertSame(['name' => 'changed'], $migration['source']['high_water_property']);
          $this->assertArrayNotHasKey('track_changes', $migration['source']);
          break;

        case 'track_changes':
          $this->assertArrayNotHasKey('high_water_property', $migration['source']);
          $this->assertArrayHasKey('track_changes', $migration['source']);
          break;

        case FALSE:
          // Only Drupal 7 migrations get either of the above set.
          break;

        default:
          throw new \InvalidArgumentException();
      }
    }
  }

  /**
   * Test cases and expectations.
   *
   * @return array
   *   Common test data and expectations for all of our alterer test.
   */
  protected function getTestCasesAndExpectations() {
    return [
      // D6 node migration – no change.
      'd6_node_complete:article' => [
        'migration_data' => [
          'label' => new TranslatableMarkup('@label (@type)', [
            '@label' => 'Node',
            '@type' => 'Article',
          ]),
          'migration_tags' => ['Drupal 6', 'Content'],
          'deriver' => D6NodeDeriver::class,
          'source' => ['plugin' => 'd6_node_complete'],
          'process' => ['changed' => 'changed'],
          'destination' => ['plugin' => 'entity_complete:node'],
        ],
        'expected_change_tracking' => FALSE,
        'expected_label' => new TranslatableMarkup('@label (@type)', [
          '@label' => 'Node',
          '@type' => 'Article',
        ]),
      ],
      // Non-derived migration – no change.
      'd7_node_article' => [
        'migration_data' => [
          'label' => new TranslatableMarkup('@label (@type)', [
            '@label' => 'Node',
            '@type' => 'Article',
          ]),
          'migration_tags' => ['Drupal 7', 'Content'],
          'source' => ['plugin' => 'd7_node'],
          'process' => ['changed' => 'changed'],
          'destination' => ['plugin' => 'entity:node'],
        ],
        'expected_change_tracking' => 'high_water_property',
        'expected_label' => new TranslatableMarkup('@label (@type)', [
          '@label' => 'Node',
          '@type' => 'Article',
        ]),
      ],
      // Invalid migration (no target entity type id) – no change expected.
      'd7_invalid' => [
        'migration_data' => [
          'label' => 'Invalid migration without target entity type id',
          'migration_tags' => ['Drupal 7'],
          'deriver' => D7NodeDeriver::class,
          'destination' => ['plugin' => 'entity'],
        ],
        'expected_change_tracking' => 'track_changes',
        'expected_label' => 'Invalid migration without target entity type id',
      ],
      // Entity revision destination - no change expected.
      'd7_node_revision:basic_page' => [
        'migration_data' => [
          'label' => new TranslatableMarkup('@label (@type)', [
            '@label' => 'Node',
            '@type' => 'Page',
          ]),
          'migration_tags' => ['Drupal 7', 'Content'],
          'deriver' => D7NodeDeriver::class,
          'source' => ['plugin' => 'd7_node_revision'],
          'process' => ['changed' => 'changed'],
          'destination' => ['plugin' => 'entity_revision:node'],
        ],
        'expected_change_tracking' => 'high_water_property',
        'expected_label' => new TranslatableMarkup('@label (@type)', [
          '@label' => 'Node',
          '@type' => 'Page',
        ]),
      ],
      // User migration – no change.
      'd7_user' => [
        'migration_data' => [
          'label' => 'User accounts',
          'migration_tags' => ['Drupal 7', 'Content'],
          'source' => ['plugin' => 'd7_user'],
          'destination' => ['plugin' => 'entity:user'],
        ],
        'expected_change_tracking' => 'track_changes',
        'expected_label' => 'User accounts',
      ],
      // Recipe node migration.
      'd7_node_complete:recipe' => [
        'migration_data' => [
          'id' => 'not_bean',
          'label' => new TranslatableMarkup('@label (@type)', [
            '@label' => 'Node complete',
            '@type' => 'Recipe',
          ]),
          'migration_tags' => ['Drupal 7', 'Content'],
          'deriver' => D7NodeDeriver::class,
          'source' => ['plugin' => 'd7_node_complete'],
          'process' => ['changed' => 'changed'],
          'destination' => ['plugin' => 'entity_complete:node'],
        ],
        'expected_change_tracking' => 'high_water_property',
        'expected_label' => new TranslatableMarkup('@category', [
          '@category' => 'Recipe',
        ], [
          'context' => 'derived entity migration label',
        ]),
      ],
      // Ingredient terms migration.
      'd7_taxonomy_term:ingredients' => [
        'migration_data' => [
          'id' => 'not_bean',
          'label' => new TranslatableMarkup('@label (@type)', [
            '@label' => 'Taxonomy terms',
            '@type' => 'Ingredients',
          ]),
          'migration_tags' => ['Drupal 7', 'Content'],
          'deriver' => D7TaxonomyTermDeriver::class,
          'source' => ['plugin' => 'd7_taxonomy_term'],
          'destination' => ['plugin' => 'entity:taxonomy_term'],
        ],
        'expected_change_tracking' => 'track_changes',
        'expected_label' => new TranslatableMarkup('@category @entity-type-plural', [
          '@category' => 'Ingredients',
          '@entity-type-plural' => new TranslatableMarkup('taxonomy terms'),
        ], [
          'context' => 'derived entity migration label',
        ]),
      ],
      // Entity migration whose source uses a subquery.
      'd7_comment:article' => [
        'migration_data' => [
          'id' => 'not_bean',
          'label' => new TranslatableMarkup('@label (@type)', [
            '@label' => 'Comment',
            '@type' => 'Article',
          ]),
          'migration_tags' => ['Drupal 7', 'Content'],
          'source' => [
            'plugin' => 'd7_comment',
            'entity_type' => 'node',
            'node_type' => 'article',
          ],
          'process' => ['changed' => 'changed'],
          'destination' => ['plugin' => 'entity:comment'],
        ],
        'expected_change_tracking' => 'high_water_property',
        'expected_label' => new TranslatableMarkup('@label (@type)', [
          '@label' => 'Comment',
          '@type' => 'Article',
        ]),
      ],
      // A taxonomy term migration with customized label.
      'd7_taxonomy_term:custom_term' => [
        'migration_data' => [
          'id' => 'not_bean',
          'label' => new TranslatableMarkup('@label (translatable text prefix @type)', [
            '@label' => 'Taxonomy terms',
            '@type' => 'Customized term',
          ]),
          'migration_tags' => ['Drupal 7', 'Content'],
          'deriver' => D7TaxonomyTermDeriver::class,
          'source' => ['plugin' => 'd7_taxonomy_term'],
          'destination' => ['plugin' => 'entity:taxonomy_term'],
        ],
        'expected_change_tracking' => 'track_changes',
        'expected_label' => new TranslatableMarkup('@category @entity-type-plural', [
          '@category' => new TranslatableMarkup('translatable text prefix @type', [
            '@type' => 'Customized term',
          ], [
            'context' => 'general entity migration category',
          ]),
          '@entity-type-plural' => new TranslatableMarkup('taxonomy terms'),
        ], [
          'context' => 'derived entity migration label',
        ]),
      ],
      // D7 field instance migration – no change.
      'd7_field:node' => [
        'migration_data' => [
          'label' => new TranslatableMarkup('@label (@type)', [
            '@label' => 'Field configuration',
            '@type' => 'node',
          ]),
          'migration_tags' => ['Drupal 7', 'Configuration'],
          'deriver' => D7FieldConfigurationMigrationDeriver::class,
          'source' => ['plugin' => 'd7_field'],
          'destination' => ['plugin' => 'entity:field_storage_config'],
        ],
        'expected_change_tracking' => 'track_changes',
        'expected_label' => new TranslatableMarkup('@label (@type)', [
          '@label' => 'Field configuration',
          '@type' => 'node',
        ]),
      ],
      // Migration with an unexpected migration label – no change.
      'd7_node_complete:blog' => [
        'migration_data' => [
          'label' => ['Blog'],
          'migration_tags' => ['Drupal 7', 'Content'],
          'deriver' => D7NodeDeriver::class,
          'source' => ['plugin' => 'd7_node_complete'],
          'process' => ['changed' => 'changed'],
          'destination' => ['plugin' => 'entity_complete:node'],
        ],
        'expected_change_tracking' => 'high_water_property',
        'expected_label' => ['Blog'],
      ],
      // Simple config migration - no change for migration label.
      'action_settings' => [
        'migration_data' => [
          'label' => 'Action configuration',
          'migration_tags' => ['Drupal 6', 'Drupal 7', 'Configuration'],
          'source' => [
            'plugin' => 'variable',
            'variables' => ['actions_max_stack'],
            'source_module' => 'system',
          ],
          'process' => ['recursion_limit' => 'actions_max_stack'],
          'destination' => [
            'plugin' => 'config',
            'config_name' => 'action.settings',
          ],
        ],
        'expected_change_tracking' => 'track_changes',
        'expected_label' => 'Action configuration',
      ],
      // Field formatter settings migration.
      'd7_field_formatter_settings:node:recipe' => [
        'migration_data' => [
          'label' => 'Field formatter configuration',
          'migration_tags' => ['Drupal 7', 'Configuration'],
          'source' => [
            'plugin' => 'd7_field_instance_per_view_mode',
            'constants' => ['third_party_settings' => []],
          ],
          'process' => [],
          'destination' => ['plugin' => 'component_entity_display'],
        ],
        'expected_change_tracking' => 'track_changes',
        'expected_label' => 'Field formatter configuration',
      ],
      // Field widget settings migration.
      'd7_field_instance_widget_settings:node:recipe' => [
        'migration_data' => [
          'label' => 'Field instance widget configuration',
          'migration_tags' => ['Drupal 7', 'Configuration'],
          'source' => [
            'plugin' => 'd7_field_instance_per_view_mode',
            'constants' => ['third_party_settings' => []],
          ],
          'process' => [],
          'destination' => ['plugin' => 'component_entity_form_display'],
        ],
        'expected_change_tracking' => 'track_changes',
        'expected_label' => 'Field instance widget configuration',
      ],
      'd7_color' => [
        'migration_data' => [
          'label' => 'Color',
          'migration_tags' => ['Drupal 7', 'Configuration'],
          'source' => [
            'plugin' => 'd7_color',
            'constants' => ['config_prefix' => 'color.theme.'],
          ],
          'process' => [],
          'destination' => ['plugin' => 'color'],
        ],
        'expected_change_tracking' => 'track_changes',
        'expected_label' => 'Color',
      ],
      'd7_theme_settings:bartik' => [
        'migration_data' => [
          'label' => 'D7 theme settings',
          'migration_tags' => ['Drupal 7', 'Configuration'],
          'deriver' => D7ThemeDeriver::class,
          'source' => ['plugin' => 'd7_theme_settings'],
          'process' => [],
          'destination' => ['plugin' => 'd7_theme_settings'],
        ],
        'expected_change_tracking' => 'track_changes',
        'expected_label' => 'D7 theme settings',
      ],
      'd7_shortcut_set_users' => [
        'migration_data' => [
          'label' => 'Shortcut set user mapping',
          'migration_tags' => ['Drupal 7', 'Configuration'],
          'source' => ['plugin' => 'd7_shortcut_set_users'],
          'process' => [],
          'destination' => ['plugin' => 'shortcut_set_users'],
          'migration_dependencies' => [
            'required' => ['d7_shortcut_set', 'd7_user'],
          ],
        ],
        'expected_change_tracking' => 'track_changes',
        'expected_label' => 'Shortcut set user mapping',
      ],
      // Derived bean migration.
      'bean:simple' => [
        'migration_data' => [
          'id' => 'bean',
          'label' => new TranslatableMarkup('@label (@type)', [
            '@label' => 'Bean',
            '@type' => 'Simple',
          ]),
          'migration_tags' => ['Drupal 7', 'Content'],
          'deriver' => 'A deriver class',
          'source' => ['plugin' => 'bean'],
          'destination' => ['plugin' => 'entity:block_content'],
        ],
        'expected_change_tracking' => 'track_changes',
        'expected_label' => new TranslatableMarkup(
          '@category @entity-type-plural from @source',
          [
            '@category' => 'Simple',
            '@entity-type-plural' => new TranslatableMarkup('custom blocks'),
            '@source' => 'Bean',
          ],
          [
            'context' => 'derived entity migration label',
          ]
        ),
      ],
    ];
  }

  /**
   * Test cases for ::testMigrationAltererRefineMigrationLabel test.
   *
   * @return array[]
   *   Data and expected results.
   */
  public function refineMigrationLabelTestCases() {
    $test_cases = $this->getTestCasesAndExpectations();

    $migrations = array_map(function ($test_case) {
      return $test_case['migration_data'];
    }, $test_cases);
    $expectations = array_map(function ($test_case) {
      return ['label' => $test_case['expected_label']] + $test_case['migration_data'];
    }, $test_cases);

    return [
      'migrations' => $migrations,
      'expectations' => $expectations,
    ];
  }

  /**
   * Test cases for ::testConversionToRollbackableDisplay test.
   *
   * @return array[]
   *   Data and expected results.
   */
  public function persistFieldStorageConfigsTestCases() {
    $test_cases = $this->getTestCasesAndExpectations();

    $migrations = array_map(function ($test_case) {
      return $test_case['migration_data'];
    }, $test_cases);
    $expected_plugins = array_map(function ($test_case) {
      return !empty($test_case['rollbackable_display_expected_plugin']) ?
        $test_case['rollbackable_display_expected_plugin'] :
        $test_case['migration_data']['destination']['plugin'];
    }, $test_cases);

    return [
      'migrations' => $migrations,
      'expected_plugins' => $expected_plugins,
    ];
  }

  /**
   * Asserts that two strings are exactly the same.
   *
   * If the source string is a TranslatableMarkup, then also checks the markup
   * string, options and attributes.
   *
   * @param string|\Drupal\Core\StringTranslation\TranslatableMarkup $actual
   *   The actual string or TranslatableMarkup.
   * @param string|\Drupal\Core\StringTranslation\TranslatableMarkup $expected
   *   The expected string or TranslatableMarkup.
   */
  public function assertStringsAreEqual($actual, $expected) {
    if ($actual instanceof TranslatableMarkup) {
      if (!$expected instanceof TranslatableMarkup) {
        $this->fail();
      }

      $this->assertSame($actual->getUntranslatedString(), $expected->getUntranslatedString());
      $this->assertSame($actual->getOptions(), $expected->getOptions());

      $actual_arguments = $actual->getArguments();
      $expected_arguments = $expected->getArguments();

      foreach ($actual_arguments as $argument_key => $actual_argument) {
        if (!isset($expected_arguments[$argument_key])) {
          $this->fail();
        }
        $this->assertStringsAreEqual($actual_argument, $expected_arguments[$argument_key]);
      }
    }
    else {
      $this->assertSame($actual, $expected);
    }
  }

}
