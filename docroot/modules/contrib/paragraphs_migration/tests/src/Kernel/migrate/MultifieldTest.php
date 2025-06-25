<?php

namespace Drupal\Tests\paragraphs_migration\Kernel\migrate;

use Drupal\Tests\migrate_drupal\Kernel\MigrateDrupalTestBase;
use Drupal\Tests\paragraphs_migration\Traits\MultifieldMigrationsTrait;

/**
 * Tests multifield to paragraphs migrations.
 *
 * @group paragraphs_migration
 * @require entity_reference_revisions
 */
class MultifieldTest extends MigrateDrupalTestBase {

  use MultifieldMigrationsTrait;

  /**
   * {@inheritdoc}
   *
   * @todo This should be changed to "protected" after Drupal core 8.x security
   *   support ends.
   * @see https://www.drupal.org/node/2909426
   */
  public static $modules = [
    'comment',
    'config_translation',
    'content_translation',
    'datetime',
    'datetime_range',
    'entity_reference_revisions',
    'field',
    'file',
    'image',
    'language',
    'link',
    'menu_ui',
    'migrate',
    'migrate_drupal',
    'node',
    'options',
    'paragraphs',
    'paragraphs_migration',
    'system',
    'taxonomy',
    'telephone',
    'text',
    'user',
    'smart_sql_idmap',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    MigrateDrupalTestBase::setUp();

    $this->installEntitySchema('file');
    $this->installEntitySchema('node');
    $this->installEntitySchema('paragraph');
    $this->installEntitySchema('comment');
    $this->installSchema('node', ['node_access']);
    $this->installSchema('comment', [
      'comment_entity_statistics',
    ]);
    $this->installSchema('file', ['file_usage']);
    $this->installConfig(['comment', 'node']);

    $this->loadFixture(implode(DIRECTORY_SEPARATOR, [
      drupal_get_path('module', 'migrate_drupal'),
      'tests',
      'fixtures',
      'drupal7.php',
    ]));

    $this->loadFixture(implode(DIRECTORY_SEPARATOR, [
      drupal_get_path('module', 'paragraphs_migration'),
      'tests',
      'fixtures',
      'drupal7_multifield_on_core_fixture.php',
    ]));
  }

  /**
   * Tests the migration of a content with paragraphs and field collections.
   */
  public function testMultifieldMigration() {
    // The Drupal 8 entity revision migration causes a file not found exception
    // without properly migrated files. For this test, it is enough to properly
    // migrate the public files.
    $fs_fixture_path = implode(DIRECTORY_SEPARATOR, [
      DRUPAL_ROOT,
      drupal_get_path('module', 'paragraphs_migration'),
      'tests',
      'fixtures',
    ]);
    $file_migration = $this->getMigration('d7_file');
    $source = $file_migration->getSourceConfiguration();
    $source['constants']['source_base_path'] = $fs_fixture_path;
    $file_migration->set('source', $source);

    // Ignore migration messages of core migrations we don't touched, or which
    // are saving migration messages but aren't crucial.
    $this->startCollectingMessages();
    $this->executeMigration($file_migration);
    $this->executeMigrations([
      'language',
      'default_language',
      'd7_user_role',
      'd7_node_type',
      'd7_comment_type',
      'taxonomy_settings',
      'd7_taxonomy_vocabulary',
      'd7_field',
      'd7_view_modes',
      'd7_entity_translation_settings',
    ]);

    // Migrate paragraph types from multifield bundles.
    $this->startCollectingMessages();
    $this->executeMigrations([
      'pm_multifield_type',
      'pm_multifield_translation_settings',
    ]);
    $this->assertEquals([], $this->migrateMessages);

    // Migrate field instances.
    $this->executeMigrations([
      'd7_field_instance',
      'd7_field_formatter_settings',
    ]);

    $this->startCollectingMessages();
    $this->executeMigrations([
      'pm_multifield',
    ]);
    $this->assertEquals([], $this->migrateMessages);

    $this->assertEquals(8, $this->getActualParagraphRevisionTranslationsCount());
    $this->assertEquals(7, $this->getActualParagraphRevisionsCount());
    $this->assertEquals(4, $this->getActualParagraphsCount());

    // Execute host content entity migrations.
    $this->executeMigrations([
      'd7_user',
    ]);

    $this->startCollectingMessages();
    $this->executeMigrations([
      'd7_taxonomy_term',
      'd7_taxonomy_term_entity_translation:vocabulary_with_multifields',
      'd7_node_complete:type_with_multifields',
    ]);
    $this->assertEquals([], $this->migrateMessages);

    $this->assertEquals(8, $this->getActualParagraphRevisionTranslationsCount());
    $this->assertEquals(7, $this->getActualParagraphRevisionsCount());
    $this->assertEquals(4, $this->getActualParagraphsCount());

    $this->assertMultifieldTextType();
    $this->assertMultifieldComplexType();

    $this->assertTermMultifieldFieldStorage();
    $this->assertTermMultifieldFieldInstance();
    $this->assertTerm126();

    $this->assertNodeMultifieldTextFieldStorage();
    $this->assertNodeMultifieldComplexFieldStorage();
    $this->assertNodeMultifieldTextFieldInstance();
    $this->assertNodeMultifieldComplexFieldInstance();
    $this->assertNode112();
  }

  /**
   * Asserts whether no migration errors were logged to $this->migrateMessages.
   *
   * This is a DX helper method which makes migration messages easy to capture
   * when something goes wrong.
   */
  protected function assertNoErrors() {
    foreach ($this->migrateMessages as $severity => $messages) {
      $actual[$severity] = array_reduce(
        $messages,
        function (array $carry, $message) {
          $carry[] = (string) $message;
          return $carry;
        },
        []
      );
      $dummy[$severity] = array_fill(0, count($messages), '...');
    }
    $this->assertEquals($actual ?? [], $dummy ?? []);
  }

}
