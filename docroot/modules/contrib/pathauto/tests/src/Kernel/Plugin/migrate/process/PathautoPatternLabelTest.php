<?php

namespace Drupal\Tests\pathauto\Kernel\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Drupal\pathauto\Plugin\migrate\process\PathautoPatternLabel;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\token\Kernel\KernelTestBase;

/**
 * Tests the "pathauto_pattern_label" migrate process plugin.
 *
 * @coversDefaultClass \Drupal\pathauto\Plugin\migrate\process\PathautoPatternLabel
 * @group pathauto
 */
class PathautoPatternLabelTest extends KernelTestBase {

  use ContentTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'field',
    'node',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->installSchema('node', 'node_access');
    $this->installEntitySchema('node');
    $this->installConfig(['field', 'node']);

    $this->createContentType([
      'type' => 'article',
      'name' => 'Article',
    ]);
    $this->createContentType([
      'type' => 'blog',
      'name' => 'Blog entry',
    ]);
  }

  /**
   * Tests pathauto pattern label transform.
   *
   * @param array $source
   *   The source values for the plugin.
   * @param string $expected
   *   The expected result.
   *
   * @dataProvider providerTestTransform
   *
   * @covers ::transform
   */
  public function testTransform(array $source, $expected) {
    $entity_type_manager = $this->container->get('entity_type.manager');
    $bundle_info = $this->container->get('entity_type.bundle.info');
    $row = new Row();
    $migration = $this->prophesize(MigrationInterface::class)->reveal();
    $executable = $this->prophesize(MigrateExecutableInterface::class)->reveal();

    $plugin = new PathautoPatternLabel([], 'pathauto_pattern_label', [], $migration, $entity_type_manager, $bundle_info);

    $actual = $plugin->transform($source, $executable, $row, 'destination_prop');
    $this->assertSame($expected, $actual);
  }

  /**
   * Data provider for ::testTransform.
   */
  public function providerTestTransform() {
    return [
      'Node with bundle, no language set' => [
        'source' => [
          'node',
          'article',
        ],
        'expected' => 'Content - Article',
      ],
      'Node with bundle and with a language' => [
        'source' => [
          'node',
          'blog',
          'hu',
        ],
        'expected' => 'Content - Blog entry (hu)',
      ],
      'Node with missing bundle' => [
        'source' => [
          'node',
          'missing_bundle',
        ],
        'expected' => 'Content - missing_bundle',
      ],
      'Missing entity type' => [
        'source' => [
          'missing_entity_type',
          'missing_bundle',
          'custom_langcode',
        ],
        'expected' => 'missing_entity_type - missing_bundle (custom_langcode)',
      ],
    ];
  }

}
