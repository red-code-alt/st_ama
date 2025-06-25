<?php

namespace Drupal\Tests\paragraphs_migration\Kernel\migrate;

use Drupal\Core\TypedData\TranslatableInterface;
use Drupal\node\Entity\Node;
use Drupal\Tests\paragraphs_migration\Traits\ParagraphsNodeMigrationAssertionsTrait;

/**
 * Test 'classic' Paragraph content migration.
 *
 * @group paragraphs_migration
 * @require entity_reference_revisions
 */
class ParagraphContentMigrationTest extends ParagraphsMigrationTestBase {

  use ParagraphsNodeMigrationAssertionsTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'comment',
    'datetime',
    'datetime_range',
    'field',
    'file',
    'image',
    'link',
    'menu_ui',
    'node',
    'options',
    'system',
    'taxonomy',
    'telephone',
    'text',
    'user',
    'content_translation',
    'language',
    'migrate_drupal_multilingual',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->installEntitySchema('file');
    $this->installEntitySchema('node');
    $this->installEntitySchema('paragraph');
    $this->installEntitySchema('comment');
    $this->installSchema('node', ['node_access']);
    $this->installSchema('comment', [
      'comment_entity_statistics',
    ]);
    $this->installSchema('file', ['file_usage']);
  }

  /**
   * Tests the migration of a content with paragraphs and field collections.
   *
   * @dataProvider providerParagraphContentMigration
   */
  public function testParagraphContentMigration(array $migrations_to_run) {
    // The Drupal 8 entity revision migration causes a file not found exception
    // without properly migrated files. For this test, it is enough to properly
    // migrate the public files.
    $fs_fixture_path = implode(DIRECTORY_SEPARATOR, [
      DRUPAL_ROOT,
      drupal_get_path('module', 'paragraphs_migration'),
      'tests',
      'fixtures',
    ]);
    $this->startCollectingMessages();
    $file_migration = $this->getMigration('d7_file');
    $source = $file_migration->getSourceConfiguration();
    $source['constants']['source_base_path'] = $fs_fixture_path;
    $file_migration->set('source', $source);
    $this->executeMigration($file_migration);
    $this->executeMigrations([
      'language',
      'd7_user_role',
      'd7_file_private',
      'd7_field',
      'd7_view_modes',
      'd7_taxonomy_vocabulary',
      'd7_node_type',
      'd7_comment_type',
      'd7_pm_paragraphs_type',
      'd7_pm_field_collection_type',
      'd7_field_instance',
      'd7_field_formatter_settings',
      'd7_field_instance_widget_settings',
      'd7_user',
      'd7_pm_field_collection',
      'd7_pm_field_collection_revisions',
      'd7_pm_paragraphs',
      'd7_pm_paragraphs_revisions',
    ]);
    $this->executeMigrations($migrations_to_run);

    $this->assertNode8Paragraphs();
    $this->assertNode9Paragraphs();
    $this->assertNode13Paragraphs();
    $this->assertNode14Paragraphs();

    if (($node_9 = Node::load(9)) instanceof TranslatableInterface && !empty($node_9->getTranslationLanguages(FALSE))) {
      $this->assertIcelandicNode9Paragraphs();
    }

    $this->assertNode11Paragraphs();
    $this->assertNode12Paragraphs();
    $this->assertNoMigrationMessages();
  }

  /**
   * Provides data and expected results for testing paragraph migrations.
   *
   * @return string[][]
   *   The node migration to run.
   */
  public function providerParagraphContentMigration() {
    $test_cases = [
      // Per parent paragraph migration derivation.
      'd7_node migration' => [
        'node_migrations' => [
          'd7_node:paragraphs_test',
          'd7_node:content_with_para',
          'd7_node:content_with_coll',
        ],
      ],
      'd7_node_revision migration' => [
        'node_migrations' => [
          // Derived node revision migrations depend on "d7_node", and not on a
          // derivative.
          'd7_node',
          'd7_node_revision:paragraphs_test',
          'd7_node_revision:content_with_coll',
          'd7_node_revision:content_with_para',
        ],
      ],
      'd7_node_translation migration' => [
        'node_migrations' => [
          'd7_node:paragraphs_test',
          'd7_node:content_with_coll',
          'd7_node:content_with_para',
          'd7_node_translation:paragraphs_test',
          'd7_node_translation:content_with_coll',
          'd7_node_translation:content_with_para',
        ],
      ],
      'd7_node_complete migration' => [
        'node_migrations' => [
          'd7_node_complete:paragraphs_test',
          'd7_node_complete:content_with_para',
          'd7_node_complete:content_with_coll',
        ],
      ],
    ];

    // Drupal 8.8.x only has 'classic' node migrations.
    // @see https://www.drupal.org/node/3105503
    if (version_compare(\Drupal::VERSION, '8.9', '<')) {
      return array_filter($test_cases, function ($test_case) {
        return empty(
          array_filter($test_case['node_migrations'], function (string $migration_id) {
            return strpos($migration_id, 'd7_node_complete') === 0;
          })
        );
      });
    }

    return $test_cases;
  }

  /**
   * Checks migration messages & shows dev friendly output if there are errors.
   */
  public function assertNoMigrationMessages() {
    $messages_as_strings = [];
    $dummies = [];
    foreach ($this->migrateMessages as $type => $messages) {
      foreach ($messages as $message) {
        $messages_as_strings[$type][] = (string) $message;
      }
      $dummies[$type] = array_fill(0, count($messages), '...');
    }
    $this->assertEquals($dummies, $messages_as_strings);
  }

}
