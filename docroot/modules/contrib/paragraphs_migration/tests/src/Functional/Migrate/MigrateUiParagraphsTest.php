<?php

namespace Drupal\Tests\paragraphs_migration\Functional\Migrate;

use Drupal\Tests\paragraphs_migration\Traits\ParagraphsNodeMigrationAssertionsTrait;

/**
 * Tests the migration of paragraph entities.
 *
 * @group paragraphs_migration
 *
 * @group legacy
 */
class MigrateUiParagraphsTest extends MigrateUiParagraphsTestBase {

  use ParagraphsNodeMigrationAssertionsTrait;

  /**
   * Tests the result of the paragraphs migration.
   *
   * @dataProvider providerParagraphsMigrate
   */
  public function testParagraphsMigrate($node_migrate_type_classic) {
    $this->setClassicNodeMigration($node_migrate_type_classic);
    $this->assertMigrateUpgradeViaUi();
    $this->assertParagraphsMigrationResults();
    $this->assertNode8Paragraphs();
    $this->assertNode9Paragraphs();
    $this->assertIcelandicNode9Paragraphs();
    $this->assertNode13Paragraphs();
    $this->assertNode14Paragraphs();
    $this->assertNode11Paragraphs();
    $this->assertNode12Paragraphs();
  }

  /**
   * Provides data and expected results for testing paragraph migrations.
   *
   * @return bool[][]
   *   Classic node migration type.
   */
  public function providerParagraphsMigrate() {
    $test_cases = [
      'Classic node migration' => [
        'node_migrate_type_classic' => TRUE,
      ],
      'Complete node migration' => [
        'node_migrate_type_classic' => FALSE,
      ],
    ];

    // Drupal 8.8.x only has 'classic' node migrations.
    // @see https://www.drupal.org/node/3105503
    if (version_compare(\Drupal::VERSION, '8.9', '<')) {
      return array_filter($test_cases, function ($test_case) {
        return $test_case['node_migrate_type_classic'] === TRUE;
      });
    }

    return $test_cases;
  }

}
