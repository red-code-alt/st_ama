<?php

namespace Drupal\Tests\acquia_migrate\Kernel\Migrate;

use Drupal\comment\Entity\CommentType;
use Drupal\migrate\Plugin\MigrationPluginManager;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Verifies how comment-related configuration migrations work.
 *
 * @group acquia_migrate
 * @group acquia_migrate__core
 */
class CommentTypeMigrationTest extends MigrateDrupal7TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'comment', 'text'];

  /**
   * {@inheritdoc}
   */
  protected function migrateCommentTypes() {
    $this->installConfig(['comment']);

    // Execute all available 'd7_comment_type' migrations.
    // @see \Drupal\Tests\migrate\Kernel\MigrateTestBase::executeMigrations()
    $migration_plugin_manager = $this->container->get('plugin.manager.migration');
    assert($migration_plugin_manager instanceof MigrationPluginManager);
    // This is a base plugin ID and we want to run all derivatives.
    $instances = $migration_plugin_manager->createInstances('d7_comment_type');
    array_walk($instances, [$this, 'executeMigration']);
  }

  /**
   * Asserts a comment type entity.
   *
   * @param string $id
   *   The entity ID.
   * @param string $label
   *   The entity label.
   */
  protected function assertEntity($id, $label) {
    $entity = CommentType::load($id);
    $this->assertInstanceOf(CommentType::class, $entity);
    $this->assertSame($label, $entity->label());
    $this->assertSame('node', $entity->getTargetEntityTypeId());
  }

  /**
   * Tests comment type migration with disabled modules on source.
   *
   * @param string[] $disabled_source_modules
   *   List of the modules to disable in the source Drupal database.
   * @param string[] $expected_comment_types
   *   List of the expected comment types keyed by ID.
   *
   * @dataProvider providerTestCommentTypeMigration
   */
  public function testCommentTypeMigration(array $disabled_source_modules, array $expected_comment_types) {
    if (!empty($disabled_source_modules)) {
      $this->sourceDatabase->update('system')
        ->condition('name', $disabled_source_modules, 'IN')
        ->fields(['status' => 0])
        ->execute();
    }

    $this->migrateCommentTypes();

    $available_comment_types = CommentType::loadMultiple();
    foreach ($expected_comment_types as $bundle => $label) {
      $this->assertEntity($bundle, $label);
      unset($available_comment_types[$bundle]);
    }
    $this->assertEmpty(
      $available_comment_types,
      sprintf('No unexpected comment types are present. This assumptions is false, because of the following comment types: "%s"', implode('", "', array_keys($available_comment_types)))
    );
  }

  /**
   * Provides test cases for ::testCommentTypeMigration().
   */
  public function providerTestCommentTypeMigration() {
    return [
      'Node and Comment modules ar enabled in source' => [
        'Disabled source modules' => [],
        'Expected comment types' => [
          'comment_forum' => 'Forum topic comment',
          'comment_node_article' => 'Article comment',
          'comment_node_blog' => 'Blog entry comment',
          'comment_node_book' => 'Book page comment',
          'comment_node_et' => 'Entity translation test comment',
          'comment_node_page' => 'Basic page comment',
          'comment_node_test_content_type' => 'Test content type comment',
          'comment_node_a_thirty_two_char' => 'Test long name comment',
        ],
      ],
      'Node module is disabled in source' => [
        'Disabled source modules' => ['node'],
        'Expected comment types' => [],
      ],
      'Comment module is disabled in source' => [
        'Disabled source modules' => ['comment'],
        'Expected comment types' => [],
      ],
      'Node and comment modules are disabled in source' => [
        'Disabled source modules' => ['comment', 'node'],
        'Expected comment types' => [],
      ],
    ];
  }

}
