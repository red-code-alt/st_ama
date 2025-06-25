<?php

namespace Drupal\Tests\pathauto\Unit\Plugin\migrate\process;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\migrate\MigrateSkipProcessException;
use Drupal\pathauto\Plugin\migrate\process\PathautoPatternSelectionCriteria;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the "pathauto_pattern_selection_criteria" migrate process plugin.
 *
 * @coversDefaultClass \Drupal\pathauto\Plugin\migrate\process\PathautoPatternSelectionCriteria
 * @group pathauto
 */
class PathautoPatternSelectionCriteriaTest extends UnitTestCase {

  /**
   * Tests the "pathauto_pattern_selection_criteria" migrate process plugin.
   *
   * @param array $source
   *   The source value for the plugin.
   * @param array|null $expected
   *   The expected result.
   * @param string[]|null $expected_exception
   *   The expected exception's class and message, or NULL.
   *
   * @covers ::transform
   *
   * @dataProvider providerTestTransform
   */
  public function testTransform(array $source, $expected, $expected_exception) {
    $executable = $this->prophesize(MigrateExecutableInterface::class)
      ->reveal();
    if (empty($row)) {
      $row = $this->prophesize(Row::class)->reveal();
    }

    $uuid_generator = $this->prophesize(UuidInterface::class);
    $uuid_generator->generate()->willReturn('uuid1', 'uuid2');

    $test_entity_type_definition = $this->prophesize(EntityTypeInterface::class);
    $test_entity_type_definition->getKey('langcode')->willReturn('test_langcode_property');

    $node_definition = $this->prophesize(EntityTypeInterface::class);
    $node_definition->getKey('langcode')->willReturn('langcode');

    $entity_type_manager = $this->prophesize(EntityTypeManagerInterface::class);
    $entity_type_manager->hasDefinition('test_entity_type')->willReturn(TRUE);
    $entity_type_manager->getDefinition('test_entity_type')->willReturn($test_entity_type_definition->reveal());
    $entity_type_manager->hasDefinition('node')->willReturn(TRUE);
    $entity_type_manager->getDefinition('ndoe')->willReturn($node_definition->reveal());

    $plugin = new PathautoPatternSelectionCriteria(
      [],
      'pathauto_pattern_selection_criteria',
      [],
      $entity_type_manager->reveal(),
      $uuid_generator->reveal()
    );

    if ($expected_exception) {
      [
        'class' => $class,
        'message' => $message,
      ] = $expected_exception;
      $this->expectException($class);
      $this->expectExceptionMessage($message);
    }
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
          NULL,
        ],
        'expected' => [
          'uuid1' => [
            'uuid' => 'uuid1',
            'id' => 'node_type',
            'bundles' => [
              'article' => 'article',
            ],
            'negate' => FALSE,
            'context_mapping' => [
              'node' => 'node',
            ],
          ],
        ],
        'exception' => NULL,
      ],
      'Entity with bundle, without language' => [
        'source' => [
          'test_entity_type',
          'test_bundle',
          NULL,
        ],
        'expected' => [
          'uuid1' => [
            'uuid' => 'uuid1',
            'id' => 'entity_bundle:test_entity_type',
            'bundles' => [
              'test_bundle' => 'test_bundle',
            ],
            'negate' => FALSE,
            'context_mapping' => [
              'test_entity_type' => 'test_entity_type',
            ],
          ],
        ],
        'exception' => NULL,
      ],
      'Entity with bundle and language' => [
        'source' => [
          'test_entity_type',
          'test_bundle',
          'test_langcode',
        ],
        'expected' => [
          'uuid1' => [
            'uuid' => 'uuid1',
            'id' => 'entity_bundle:test_entity_type',
            'bundles' => [
              'test_bundle' => 'test_bundle',
            ],
            'negate' => FALSE,
            'context_mapping' => [
              'test_entity_type' => 'test_entity_type',
            ],
          ],
          'uuid2' => [
            'uuid' => 'uuid2',
            'id' => 'language',
            'langcodes' => [
              'test_langcode' => 'test_langcode',
            ],
            'negate' => FALSE,
            'context_mapping' => [
              'language' => 'test_entity_type:test_langcode_property:language',
            ],
          ],
        ],
        'exception' => NULL,
      ],
      'Exception - not enough parameters' => [
        'source' => [
          'test_entity_type',
          NULL,
        ],
        'expected' => NULL,
        'exception' => [
          'class' => MigrateSkipProcessException::class,
          'message' => 'The entity_type, the bundle, the langcode or more of these sources are missing.',
        ],
      ],
      'Exception - entity_type is not a string' => [
        'source' => [
          NULL,
          NULL,
          NULL,
        ],
        'expected' => NULL,
        'exception' => [
          'class' => MigrateSkipProcessException::class,
          'message' => 'The entity_type must be a string.',
        ],
      ],
    ];
  }

}
