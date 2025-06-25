<?php

namespace Drupal\Tests\acquia_migrate\Unit;

use Drupal\acquia_migrate\Migration;
use Drupal\Tests\UnitTestCase;

/**
 * @covers \Drupal\acquia_migrate\Migration
 *
 * @group acquia_migrate
 * @group acquia_migrate__core
 */
class MigrationTest extends UnitTestCase {

  /**
   * @covers isValidMigrationId
   * @covers generateIdFromLabel
   * @dataProvider dataMigrationLabelsAndIds
   */
  public function testMigrationLabelsAndIds(string $label, string $expected_id): void {
    $id = Migration::generateIdFromLabel($label);
    $this->assertTrue(Migration::isValidMigrationId($id));
    $this->assertSame($expected_id, $id);
  }

  public function dataMigrationLabelsAndIds(): array {
    return [
      'ASCII, no spaces' => ['Users', 'f9aae5fda8d810a29f12d1e61b4ab25f-Users'],
      'ASCII, spaces' => ['Shared structure for menus', 'cfddcadb31b559c03c57b20372420c1f-Shared structure for menus'],
      'Unicode no spaces' => ['Catégories', '83f62264e331203b7b0b16be3a8667ca-Catégories'],
      'Unicode, spaces' => ['Sujet de discussion énorme taxonomy terms', '15a675c66f059cc65388d2536d49740a-Sujet de discussion énorme taxonomy terms'],
    ];
  }

}
