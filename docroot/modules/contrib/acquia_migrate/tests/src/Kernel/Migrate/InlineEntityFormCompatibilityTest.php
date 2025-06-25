<?php

namespace Drupal\Tests\acquia_migrate\Kernel\Migrate;

use Drupal\Tests\inline_entity_form\Kernel\Migrate\MigrateFieldInstanceWidgetSettingsTest;

if (class_exists(MigrateFieldInstanceWidgetSettingsTest::class)) {
  /**
   * Tests whether IEF is compatible with AMA and Recommendations.
   *
   * @group acquia_migrate
   * @group acquia_migrate__contrib
   */
  class InlineEntityFormCompatibilityTest extends MigrateFieldInstanceWidgetSettingsTest {

    /**
     * {@inheritdoc}
     */
    protected function setUp() {
      $this->startCollectingMessages();
      parent::setUp();
      $this->assertEmpty($this->migrateMessages);
    }

  }
}
