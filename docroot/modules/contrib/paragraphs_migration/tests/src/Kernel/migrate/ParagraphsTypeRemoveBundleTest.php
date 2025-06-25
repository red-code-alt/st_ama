<?php

namespace Drupal\Tests\paragraphs_migration\Kernel\migrate;

/**
 * Test missing bundle migration.
 *
 * @group paragraphs_migration
 * @require entity_reference_revisions
 */
class ParagraphsTypeRemoveBundleTest extends ParagraphContentMigrationTest {

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->sourceDatabase->delete('paragraphs_bundle')->condition('bundle', 'paragraph_bundle_one')->execute();
  }

}
