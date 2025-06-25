<?php

namespace Drupal\Tests\media_migration\Kernel;

use Drupal\media\Entity\Media;
use Drupal\node\Entity\Node;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Tests Video Embed Field migration.
 *
 * @group media_migration
 */
class VideoEmbedFieldTest extends MigrateDrupal7TestBase {

  use MediaTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'media',
    'media_migration',
    'media_migration_test_oembed',
    'node',
    'smart_sql_idmap',
    'image',
    'text',
    'filter',
    'comment',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->createMediaType('oembed:video', ['id' => 'remote_video']);
    $this->installEntitySchema('media');
    $this->installEntitySchema('file');
    $this->installEntitySchema('node');
    $this->installConfig(['node']);
    $this->installSchema('file', 'file_usage');
  }

  /**
   * {@inheritdoc}
   */
  protected function getFixtureFilePath() {
    return implode(DIRECTORY_SEPARATOR, [
      \Drupal::service('extension.list.module')->getPath('media_migration'),
      'tests',
      'fixtures',
      'drupal7.php',
    ]);
  }

  /**
   * Tests Video Embed Field migration.
   */
  public function testVideoEmbedFieldMigration(): void {
    $this->startCollectingMessages();
    $this->executeMigrations([
      'd7_field',
      'd7_view_modes',
      'd7_video_embed_field',
      'd7_node_type',
      'd7_comment_type',
      'd7_field_instance',
      'd7_user_role',
      'd7_user',
      'd7_node_complete',
    ]);
    $this->assertEmpty($this->migrateMessages);
    $media_entities = Media::loadMultiple();
    $this->assertCount(2, $media_entities);
    $this->assertEquals(
      ['field_vid_emb' => [['target_id' => 2]]],
      array_intersect_key(
        Node::load(7)->toArray(),
        ['field_vid_emb' => 1]
      )
    );
    $this->assertCount(2, Node::loadMultiple());
    $media_props_to_ignore = [
      'uuid',
      'revision_created',
      'thumbnail',
      'created',
      'changed',
    ];

    $this->assertEquals([
      'mid' => [['value' => '1']],
      'vid' => [['value' => '1']],
      'langcode' => [['value' => 'en']],
      'bundle' => [['target_id' => 'remote_video']],
      'revision_user' => [],
      'revision_log_message' => [],
      'status' => [['value' => '1']],
      'uid' => [['target_id' => '0']],
      'name' => [['value' => 'https://vimeo.com/632060933']],
      'default_langcode' => [['value' => '1']],
      'revision_default' => [['value' => '1']],
      'revision_translation_affected' => [['value' => '1']],
      'field_media_oembed_video' => [['value' => 'https://vimeo.com/632060933']],
    ],
    array_diff_key(
    $media_entities[1]->toArray(),
    array_combine($media_props_to_ignore, $media_props_to_ignore)
    )
    );
  }

}
