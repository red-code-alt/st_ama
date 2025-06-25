<?php

/**
 * @file
 * A database agnostic dump for testing purposes.
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

$connection->schema()->createTable('pathauto_state', [
  'fields' => [
    'entity_type' => [
      'type' => 'varchar',
      'not null' => TRUE,
      'length' => '32',
    ],
    'entity_id' => [
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ],
    'pathauto' => [
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'tiny',
      'default' => '0',
    ],
  ],
  'primary key' => [
    'entity_type',
    'entity_id',
  ],
  'mysql_character_set' => 'utf8',
]);

$connection->insert('pathauto_state')
  ->fields([
    'entity_type',
    'entity_id',
    'pathauto',
  ])
  ->values([
    'entity_type' => 'taxonomy_term',
    'entity_id' => '2',
    'pathauto' => '0',
  ])
  ->values([
    'entity_type' => 'taxonomy_term',
    'entity_id' => '3',
    'pathauto' => '0',
  ])
  ->values([
    'entity_type' => 'taxonomy_term',
    'entity_id' => '4',
    'pathauto' => '0',
  ])
  ->values([
    'entity_type' => 'taxonomy_term',
    'entity_id' => '11',
    'pathauto' => '1',
  ])
  ->values([
    'entity_type' => 'node',
    'entity_id' => '2',
    'pathauto' => '0',
  ])
  ->values([
    'entity_type' => 'node',
    'entity_id' => '3',
    'pathauto' => '0',
  ])
  ->values([
    'entity_type' => 'node',
    'entity_id' => '4',
    'pathauto' => '0',
  ])
  ->values([
    'entity_type' => 'node',
    'entity_id' => '5',
    'pathauto' => '0',
  ])
  ->values([
    'entity_type' => 'node',
    'entity_id' => '9',
    'pathauto' => '0',
  ])
  ->values([
    'entity_type' => 'node',
    'entity_id' => '11',
    'pathauto' => '1',
  ])
  ->execute();

$connection->insert('variable')
  ->fields([
    'name',
    'value',
  ])
  ->values([
    'name' => 'pathauto_blog_pattern',
    'value' => 's:17:"blogs/[user:name]";',
  ])
  ->values([
    'name' => 'pathauto_case',
    'value' => 's:1:"1";',
  ])
  ->values([
    'name' => 'pathauto_forum_pattern',
    'value' => 's:29:"[term:vocabulary]/[term:name]";',
  ])
  ->values([
    'name' => 'pathauto_ignore_words',
    'value' => 's:134:"a, an, as, at, before, but, by, for, from, is, in, into, like, of, off, on, onto, per, since, than, the, this, that, to, up, via, with";',
  ])
  ->values([
    'name' => 'pathauto_max_component_length',
    'value' => 's:3:"100";',
  ])
  ->values([
    'name' => 'pathauto_max_length',
    'value' => 's:3:"100";',
  ])
  ->values([
    'name' => 'pathauto_node_article_en_pattern',
    'value' => 's:35:"[node:content-type]/en/[node:title]";',
  ])
  ->values([
    'name' => 'pathauto_node_article_fr_pattern',
    'value' => 's:35:"[node:content-type]/fr/[node:title]";',
  ])
  ->values([
    'name' => 'pathauto_node_article_is_pattern',
    'value' => 's:35:"[node:content-type]/is/[node:title]";',
  ])
  ->values([
    'name' => 'pathauto_node_article_pattern',
    'value' => 's:32:"[node:content-type]/[node:title]";',
  ])
  ->values([
    'name' => 'pathauto_node_article_und_pattern',
    'value' => 's:0:"";',
  ])
  ->values([
    'name' => 'pathauto_node_blog_en_pattern',
    'value' => 's:0:"";',
  ])
  ->values([
    'name' => 'pathauto_node_blog_fr_pattern',
    'value' => 's:0:"";',
  ])
  ->values([
    'name' => 'pathauto_node_blog_is_pattern',
    'value' => 's:0:"";',
  ])
  ->values([
    'name' => 'pathauto_node_blog_pattern',
    'value' => 's:37:"blogs/[node:author:name]/[node:title]";',
  ])
  ->values([
    'name' => 'pathauto_node_blog_und_pattern',
    'value' => 's:0:"";',
  ])
  ->values([
    'name' => 'pathauto_node_book_pattern',
    'value' => 's:0:"";',
  ])
  ->values([
    'name' => 'pathauto_node_et_en_pattern',
    'value' => 's:0:"";',
  ])
  ->values([
    'name' => 'pathauto_node_et_fr_pattern',
    'value' => 's:0:"";',
  ])
  ->values([
    'name' => 'pathauto_node_et_is_pattern',
    'value' => 's:0:"";',
  ])
  ->values([
    'name' => 'pathauto_node_et_pattern',
    'value' => 's:43:"[node:content-type]/[node:nid]/[node:title]";',
  ])
  ->values([
    'name' => 'pathauto_node_et_und_pattern',
    'value' => 's:0:"";',
  ])
  ->values([
    'name' => 'pathauto_node_forum_pattern',
    'value' => 's:0:"";',
  ])
  ->values([
    'name' => 'pathauto_node_page_en_pattern',
    'value' => 's:0:"";',
  ])
  ->values([
    'name' => 'pathauto_node_page_fr_pattern',
    'value' => 's:0:"";',
  ])
  ->values([
    'name' => 'pathauto_node_page_is_pattern',
    'value' => 's:0:"";',
  ])
  ->values([
    'name' => 'pathauto_node_page_pattern',
    'value' => 's:0:"";',
  ])
  ->values([
    'name' => 'pathauto_node_page_und_pattern',
    'value' => 's:0:"";',
  ])
  ->values([
    'name' => 'pathauto_node_pattern',
    'value' => 's:12:"[node:title]";',
  ])
  ->values([
    'name' => 'pathauto_node_test_content_type_en_pattern',
    'value' => 's:0:"";',
  ])
  ->values([
    'name' => 'pathauto_node_test_content_type_fr_pattern',
    'value' => 's:0:"";',
  ])
  ->values([
    'name' => 'pathauto_node_test_content_type_is_pattern',
    'value' => 's:0:"";',
  ])
  ->values([
    'name' => 'pathauto_node_test_content_type_pattern',
    'value' => 's:0:"";',
  ])
  ->values([
    'name' => 'pathauto_node_test_content_type_und_pattern',
    'value' => 's:0:"";',
  ])
  ->values([
    'name' => 'pathauto_punctuation_ampersand',
    'value' => 's:1:"0";',
  ])
  ->values([
    'name' => 'pathauto_punctuation_asterisk',
    'value' => 's:1:"0";',
  ])
  ->values([
    'name' => 'pathauto_punctuation_at',
    'value' => 's:1:"0";',
  ])
  ->values([
    'name' => 'pathauto_punctuation_backtick',
    'value' => 's:1:"0";',
  ])
  ->values([
    'name' => 'pathauto_punctuation_back_slash',
    'value' => 's:1:"0";',
  ])
  ->values([
    'name' => 'pathauto_punctuation_caret',
    'value' => 's:1:"0";',
  ])
  ->values([
    'name' => 'pathauto_punctuation_colon',
    'value' => 's:1:"0";',
  ])
  ->values([
    'name' => 'pathauto_punctuation_comma',
    'value' => 's:1:"0";',
  ])
  ->values([
    'name' => 'pathauto_punctuation_dollar',
    'value' => 's:1:"0";',
  ])
  ->values([
    'name' => 'pathauto_punctuation_double_quotes',
    'value' => 's:1:"0";',
  ])
  ->values([
    'name' => 'pathauto_punctuation_equal',
    'value' => 's:1:"0";',
  ])
  ->values([
    'name' => 'pathauto_punctuation_exclamation',
    'value' => 's:1:"0";',
  ])
  ->values([
    'name' => 'pathauto_punctuation_greater_than',
    'value' => 's:1:"0";',
  ])
  ->values([
    'name' => 'pathauto_punctuation_hash',
    'value' => 's:1:"0";',
  ])
  ->values([
    'name' => 'pathauto_punctuation_hyphen',
    'value' => 's:1:"1";',
  ])
  ->values([
    'name' => 'pathauto_punctuation_left_curly',
    'value' => 's:1:"0";',
  ])
  ->values([
    'name' => 'pathauto_punctuation_left_parenthesis',
    'value' => 's:1:"0";',
  ])
  ->values([
    'name' => 'pathauto_punctuation_left_square',
    'value' => 's:1:"0";',
  ])
  ->values([
    'name' => 'pathauto_punctuation_less_than',
    'value' => 's:1:"0";',
  ])
  ->values([
    'name' => 'pathauto_punctuation_percent',
    'value' => 's:1:"0";',
  ])
  ->values([
    'name' => 'pathauto_punctuation_period',
    'value' => 's:1:"0";',
  ])
  ->values([
    'name' => 'pathauto_punctuation_pipe',
    'value' => 's:1:"0";',
  ])
  ->values([
    'name' => 'pathauto_punctuation_plus',
    'value' => 's:1:"0";',
  ])
  ->values([
    'name' => 'pathauto_punctuation_question_mark',
    'value' => 's:1:"0";',
  ])
  ->values([
    'name' => 'pathauto_punctuation_quotes',
    'value' => 's:1:"0";',
  ])
  ->values([
    'name' => 'pathauto_punctuation_right_curly',
    'value' => 's:1:"0";',
  ])
  ->values([
    'name' => 'pathauto_punctuation_right_parenthesis',
    'value' => 's:1:"0";',
  ])
  ->values([
    'name' => 'pathauto_punctuation_right_square',
    'value' => 's:1:"0";',
  ])
  ->values([
    'name' => 'pathauto_punctuation_semicolon',
    'value' => 's:1:"0";',
  ])
  ->values([
    'name' => 'pathauto_punctuation_slash',
    'value' => 's:1:"0";',
  ])
  ->values([
    'name' => 'pathauto_punctuation_tilde',
    'value' => 's:1:"0";',
  ])
  ->values([
    'name' => 'pathauto_punctuation_underscore',
    'value' => 's:1:"0";',
  ])
  ->values([
    'name' => 'pathauto_reduce_ascii',
    'value' => 'i:0;',
  ])
  ->values([
    'name' => 'pathauto_separator',
    'value' => 's:1:"-";',
  ])
  ->values([
    'name' => 'pathauto_taxonomy_term_sujet_de_discussion_pattern',
    'value' => 's:0:"";',
  ])
  ->values([
    'name' => 'pathauto_taxonomy_term_tags_pattern',
    'value' => 's:15:"tag/[term:name]";',
  ])
  ->values([
    'name' => 'pathauto_taxonomy_term_test_vocabulary_pattern',
    'value' => 's:0:"";',
  ])
  ->values([
    'name' => 'pathauto_taxonomy_term_vocabfixed_pattern',
    'value' => 's:0:"";',
  ])
  ->values([
    'name' => 'pathauto_taxonomy_term_vocablocalized_pattern',
    'value' => 's:0:"";',
  ])
  ->values([
    'name' => 'pathauto_taxonomy_term_vocabtranslate_pattern',
    'value' => 's:0:"";',
  ])
  ->values([
    'name' => 'pathauto_taxonomy_term_vocabulary_name_much_longer_than_thirty_two_characters_pattern',
    'value' => 's:0:"";',
  ])
  ->values([
    'name' => 'pathauto_taxonomy_term_pattern',
    'value' => 's:29:"[term:vocabulary]/[term:name]";',
  ])
  ->values([
    'name' => 'pathauto_transliterate',
    'value' => 'i:1;',
  ])
  ->values([
    'name' => 'pathauto_update_action',
    'value' => 's:1:"2";',
  ])
  ->values([
    'name' => 'pathauto_user_pattern',
    'value' => 's:17:"users/[user:name]";',
  ])
  ->values([
    'name' => 'pathauto_verbose',
    'value' => 'i:0;',
  ])
  ->execute();

$connection->insert('system')
  ->fields([
    'filename',
    'name',
    'type',
    'owner',
    'status',
    'bootstrap',
    'schema_version',
    'weight',
    'info',
  ])
  ->values([
    'filename' => 'sites/all/modules/pathauto/pathauto.module',
    'name' => 'pathauto',
    'type' => 'module',
    'owner' => '',
    'status' => '1',
    'bootstrap' => '0',
    'schema_version' => '7006',
    'weight' => '1',
    'info' => 'a:14:{s:4:"name";s:8:"Pathauto";s:11:"description";s:95:"Provides a mechanism for modules to automatically generate aliases for the content they manage.";s:12:"dependencies";a:2:{i:0;s:4:"path";i:1;s:5:"token";}s:4:"core";s:3:"7.x";s:5:"files";a:2:{i:0;s:20:"pathauto.migrate.inc";i:1;s:13:"pathauto.test";}s:9:"configure";s:33:"admin/config/search/path/patterns";s:10:"recommends";a:1:{i:0;s:8:"redirect";}s:7:"version";s:7:"7.x-1.3";s:7:"project";s:8:"pathauto";s:9:"datestamp";s:10:"1444232655";s:5:"mtime";i:1561521861;s:7:"package";s:5:"Other";s:3:"php";s:5:"5.2.4";s:9:"bootstrap";i:0;}',
  ])
  ->values([
    'filename' => 'sites/all/modules/token/token.module',
    'name' => 'token',
    'type' => 'module',
    'owner' => '',
    'status' => '1',
    'bootstrap' => '0',
    'schema_version' => '7000',
    'weight' => '0',
    'info' => 'a:12:{s:4:"name";s:7:"Tracker";s:11:"description";s:45:"Enables tracking of recent content for users.";s:12:"dependencies";a:1:{i:0;s:7:"comment";}s:7:"package";s:4:"Core";s:7:"version";s:4:"7.61";s:4:"core";s:3:"7.x";s:5:"files";a:1:{i:0;s:12:"tracker.test";}s:7:"project";s:6:"drupal";s:9:"datestamp";s:10:"1541684322";s:5:"mtime";i:1541684322;s:3:"php";s:5:"5.2.4";s:9:"bootstrap";i:0;}',
  ])
  ->execute();
