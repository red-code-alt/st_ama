<?php

/**
 * @file
 * Database fixture for testing acquia_migrate_update_9202().
 *
 * @see \Drupal\Tests\acquia_migrate\Functional\RollbackableTablesUpdateTest
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

$connection->schema()->createTable('acquia_migrate_config_new', [
  'fields' => [
    'config_id' => [
      'type' => 'varchar_ascii',
      'not null' => TRUE,
      'length' => '192',
    ],
    'langcode' => [
      'type' => 'varchar_ascii',
      'not null' => TRUE,
      'length' => '32',
      'default' => '',
    ],
  ],
  'primary key' => [
    'config_id',
    'langcode',
  ],
  'mysql_character_set' => 'utf8mb4',
]);

$connection->insert('acquia_migrate_config_new')
  ->fields([
    'config_id',
    'langcode',
  ])
  ->values([
    'config_id' => 'bartik.settings',
    'langcode' => '',
  ])
  ->values([
    'config_id' => 'color.theme.bartik',
    'langcode' => '',
  ])
  ->values([
    'config_id' => 'core.entity_form_display.node.et.default',
    'langcode' => '',
  ])
  ->values([
    'config_id' => 'core.entity_form_display.node.test_content_type.default',
    'langcode' => '',
  ])
  ->values([
    'config_id' => 'core.entity_form_display.user.user.default',
    'langcode' => '',
  ])
  ->values([
    'config_id' => 'core.entity_view_display.node.test_content_type.default',
    'langcode' => '',
  ])
  ->values([
    'config_id' => 'core.entity_view_display.node.test_content_type.teaser',
    'langcode' => '',
  ])
  ->values([
    'config_id' => 'core.entity_view_display.user.user.default',
    'langcode' => '',
  ])
  ->execute();

$connection->schema()->createTable('acquia_migrate_config_rollback_data', [
  'fields' => [
    'migration_plugin_id' => [
      'type' => 'varchar_ascii',
      'not null' => TRUE,
      'length' => '192',
    ],
    'config_id' => [
      'type' => 'varchar_ascii',
      'not null' => TRUE,
      'length' => '255',
    ],
    'langcode' => [
      'type' => 'varchar_ascii',
      'not null' => TRUE,
      'length' => '32',
      'default' => '',
    ],
    'field_name' => [
      'type' => 'varchar_ascii',
      'not null' => TRUE,
      'length' => '255',
      'default' => '',
    ],
    'rollback_data' => [
      'type' => 'blob',
      'not null' => FALSE,
      'size' => 'big',
    ],
  ],
  'primary key' => [
    'migration_plugin_id',
    'config_id',
    'langcode',
    'field_name',
  ],
  'mysql_character_set' => 'utf8mb4',
]);
$connection->insert('acquia_migrate_config_rollback_data')
  ->fields([
    'migration_plugin_id',
    'config_id',
    'langcode',
    'field_name',
    'rollback_data',
  ])
  ->values([
    'migration_plugin_id' => 'block_content_entity_display',
    'config_id' => 'core.entity_view_display.block_content.basic.default',
    'langcode' => '',
    'field_name' => 'body',
    'rollback_data' => 'N;',
  ])
  ->values([
    'migration_plugin_id' => 'block_content_entity_form_display',
    'config_id' => 'core.entity_form_display.block_content.basic.default',
    'langcode' => '',
    'field_name' => 'body',
    'rollback_data' => 'N;',
  ])
  ->values([
    'migration_plugin_id' => 'd7_color',
    'config_id' => 'color.theme.bartik',
    'langcode' => '',
    'field_name' => '',
    'rollback_data' => 'N;',
  ])
  ->values([
    'migration_plugin_id' => 'd7_contact_settings',
    'config_id' => 'contact.settings',
    'langcode' => '',
    'field_name' => '',
    'rollback_data' => 'a:3:{s:20:"user_default_enabled";b:1;s:5:"flood";a:2:{s:5:"limit";i:5;s:8:"interval";i:3600;}s:12:"default_form";s:8:"feedback";}',
  ])
  ->values([
    'migration_plugin_id' => 'd7_field_formatter_settings:node:test_content_type',
    'config_id' => 'core.entity_view_display.node.test_content_type.default',
    'langcode' => '',
    'field_name' => 'field_boolean',
    'rollback_data' => 'N;',
  ])
  ->values([
    'migration_plugin_id' => 'd7_field_formatter_settings:node:test_content_type',
    'config_id' => 'core.entity_view_display.node.test_content_type.default',
    'langcode' => '',
    'field_name' => 'field_date',
    'rollback_data' => 'N;',
  ])
  ->values([
    'migration_plugin_id' => 'd7_field_formatter_settings:node:test_content_type',
    'config_id' => 'core.entity_view_display.node.test_content_type.default',
    'langcode' => '',
    'field_name' => 'field_long_text',
    'rollback_data' => 'N;',
  ])
  ->values([
    'migration_plugin_id' => 'd7_field_formatter_settings:node:test_content_type',
    'config_id' => 'core.entity_view_display.node.test_content_type.default',
    'langcode' => '',
    'field_name' => 'field_telephone',
    'rollback_data' => 'N;',
  ])
  ->values([
    'migration_plugin_id' => 'd7_field_formatter_settings:node:test_content_type',
    'config_id' => 'core.entity_view_display.node.test_content_type.teaser',
    'langcode' => '',
    'field_name' => 'field_telephone',
    'rollback_data' => 'N;',
  ])
  ->values([
    'migration_plugin_id' => 'd7_field_formatter_settings:user:user',
    'config_id' => 'core.entity_view_display.user.user.default',
    'langcode' => '',
    'field_name' => 'field_file',
    'rollback_data' => 'N;',
  ])
  ->values([
    'migration_plugin_id' => 'd7_field_formatter_settings:user:user',
    'config_id' => 'core.entity_view_display.user.user.default',
    'langcode' => '',
    'field_name' => 'field_integer',
    'rollback_data' => 'N;',
  ])
  ->values([
    'migration_plugin_id' => 'd7_field_formatter_settings:user:user',
    'config_id' => 'core.entity_view_display.user.user.default',
    'langcode' => '',
    'field_name' => 'field_reference',
    'rollback_data' => 'N;',
  ])
  ->values([
    'migration_plugin_id' => 'd7_field_instance_widget_settings:node:test_content_type',
    'config_id' => 'core.entity_form_display.node.test_content_type.default',
    'langcode' => '',
    'field_name' => 'field_boolean',
    'rollback_data' => 'N;',
  ])
  ->values([
    'migration_plugin_id' => 'd7_field_instance_widget_settings:node:test_content_type',
    'config_id' => 'core.entity_form_display.node.test_content_type.default',
    'langcode' => '',
    'field_name' => 'field_date',
    'rollback_data' => 'N;',
  ])
  ->values([
    'migration_plugin_id' => 'd7_field_instance_widget_settings:node:test_content_type',
    'config_id' => 'core.entity_form_display.node.test_content_type.default',
    'langcode' => '',
    'field_name' => 'field_long_text',
    'rollback_data' => 'N;',
  ])
  ->values([
    'migration_plugin_id' => 'd7_field_instance_widget_settings:node:test_content_type',
    'config_id' => 'core.entity_form_display.node.test_content_type.default',
    'langcode' => '',
    'field_name' => 'field_telephone',
    'rollback_data' => 'N;',
  ])
  ->values([
    'migration_plugin_id' => 'd7_field_instance_widget_settings:user:user',
    'config_id' => 'core.entity_form_display.user.user.default',
    'langcode' => '',
    'field_name' => 'field_file',
    'rollback_data' => 'N;',
  ])
  ->values([
    'migration_plugin_id' => 'd7_field_instance_widget_settings:user:user',
    'config_id' => 'core.entity_form_display.user.user.default',
    'langcode' => '',
    'field_name' => 'field_integer',
    'rollback_data' => 'N;',
  ])
  ->values([
    'migration_plugin_id' => 'd7_field_instance_widget_settings:user:user',
    'config_id' => 'core.entity_form_display.user.user.default',
    'langcode' => '',
    'field_name' => 'field_reference',
    'rollback_data' => 'N;',
  ])
  ->values([
    'migration_plugin_id' => 'd7_filter_settings',
    'config_id' => 'filter.settings',
    'langcode' => '',
    'field_name' => '',
    'rollback_data' => 'a:1:{s:15:"fallback_format";s:10:"plain_text";}',
  ])
  ->values([
    'migration_plugin_id' => 'd7_global_theme_settings',
    'config_id' => 'system.theme.global',
    'langcode' => '',
    'field_name' => '',
    'rollback_data' => 'a:3:{s:8:"features";a:4:{s:20:"comment_user_picture";b:1;s:25:"comment_user_verification";b:1;s:7:"favicon";b:1;s:17:"node_user_picture";b:1;}s:4:"logo";a:3:{s:4:"path";s:0:"";s:3:"url";s:0:"";s:11:"use_default";b:1;}s:7:"favicon";a:4:{s:8:"mimetype";s:24:"image/vnd.microsoft.icon";s:4:"path";s:0:"";s:3:"url";s:0:"";s:11:"use_default";b:1;}}',
  ])
  ->values([
    'migration_plugin_id' => 'd7_shortcut_set_users',
    'config_id' => 'user-shortcut-set-:2',
    'langcode' => '',
    'field_name' => '',
    'rollback_data' => 's:7:"default";',
  ])
  ->values([
    'migration_plugin_id' => 'd7_theme_settings:bartik',
    'config_id' => 'bartik.settings',
    'langcode' => '',
    'field_name' => '',
    'rollback_data' => 'N;',
  ])
  ->values([
    'migration_plugin_id' => 'user_picture_entity_display',
    'config_id' => 'core.entity_view_display.user.user.default',
    'langcode' => '',
    'field_name' => 'user_picture',
    'rollback_data' => 'N;',
  ])
  ->values([
    'migration_plugin_id' => 'user_picture_entity_form_display',
    'config_id' => 'core.entity_form_display.user.user.default',
    'langcode' => '',
    'field_name' => 'user_picture',
    'rollback_data' => 'N;',
  ])
  ->execute();
