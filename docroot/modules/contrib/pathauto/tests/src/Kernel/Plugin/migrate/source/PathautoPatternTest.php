<?php

namespace Drupal\Tests\pathauto\Kernel\Plugin\migrate\source;

/**
 * Tests the "pathauto_pattern" migrate source plugin.
 *
 * @covers \Drupal\pathauto\Plugin\migrate\source\PathautoPattern
 * @group pathauto
 */
class PathautoPatternTest extends PathautoSourceTestBase {

  /**
   * {@inheritdoc}
   *
   * @see https://www.drupal.org/node/2909426
   * @todo This should be changed to "protected" after Drupal core 8.x security
   *   support ends.
   */
  public static $modules = [
    'language',
    'node',
    'taxonomy',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    return [
      'No bundle and entity type restrictions' => [
        'Source' => self::TEST_DB,
        'Expected' => [
          0 => [
            'name' => 'pathauto_forum_pattern',
            'value' => 's:29:"[term:vocabulary]/[term:name]";',
            'id' => 'forum',
            'forum_vocabulary' => TRUE,
            'entity_type' => 'taxonomy_term',
            'bundle' => 'sujet_de_discussion',
            'weight' => 0,
          ],
          1 => [
            'name' => 'pathauto_node_article_en_pattern',
            'value' => 's:35:"[node:content-type]/en/[node:title]";',
            'id' => 'node_article_en',
            'forum_vocabulary' => FALSE,
            'entity_type' => 'node',
            'bundle' => 'article',
            'langcode' => 'en',
            'weight' => -1,
          ],
          2 => [
            'name' => 'pathauto_node_article_fr_pattern',
            'value' => 's:35:"[node:content-type]/fr/[node:title]";',
            'id' => 'node_article_fr',
            'forum_vocabulary' => FALSE,
            'entity_type' => 'node',
            'bundle' => 'article',
            'langcode' => 'fr',
            'weight' => -1,
          ],
          3 => [
            'name' => 'pathauto_node_article_is_pattern',
            'value' => 's:35:"[node:content-type]/is/[node:title]";',
            'id' => 'node_article_is',
            'forum_vocabulary' => FALSE,
            'entity_type' => 'node',
            'bundle' => 'article',
            'langcode' => 'is',
            'weight' => -1,
          ],
          4 => [
            'name' => 'pathauto_node_article_pattern',
            'value' => 's:32:"[node:content-type]/[node:title]";',
            'id' => 'node_article',
            'forum_vocabulary' => FALSE,
            'entity_type' => 'node',
            'bundle' => 'article',
            'weight' => 0,
          ],
          5 => [
            'name' => 'pathauto_node_blog_pattern',
            'value' => 's:37:"blogs/[node:author:name]/[node:title]";',
            'id' => 'node_blog',
            'forum_vocabulary' => FALSE,
            'entity_type' => 'node',
            'bundle' => 'blog',
            'weight' => 0,
          ],
          6 => [
            'name' => 'pathauto_node_et_pattern',
            'value' => 's:43:"[node:content-type]/[node:nid]/[node:title]";',
            'id' => 'node_et',
            'forum_vocabulary' => FALSE,
            'entity_type' => 'node',
            'bundle' => 'et',
            'weight' => 0,
          ],
          7 => [
            'name' => 'pathauto_node_pattern',
            'value' => 's:12:"[node:title]";',
            'id' => 'node',
            'forum_vocabulary' => FALSE,
            'entity_type' => 'node',
            'weight' => 1,
            'bundle' => null,
          ],
          8 => [
            'name' => 'pathauto_taxonomy_term_tags_pattern',
            'value' => 's:15:"tag/[term:name]";',
            'id' => 'taxonomy_term_tags',
            'forum_vocabulary' => FALSE,
            'entity_type' => 'taxonomy_term',
            'bundle' => 'tags',
            'weight' => 0,
          ],
          9 => [
            'name' => 'pathauto_taxonomy_term_pattern',
            'value' => 's:29:"[term:vocabulary]/[term:name]";',
            'id' => 'taxonomy_term',
            'forum_vocabulary' => FALSE,
            'entity_type' => 'taxonomy_term',
            'weight' => 1,
            'bundle' => null,
          ],
          10 => [
            'name' => 'pathauto_user_pattern',
            'value' => 's:17:"users/[user:name]";',
            'id' => 'user',
            'forum_vocabulary' => FALSE,
            'entity_type' => 'user',
            'weight' => 1,
            'bundle' => null,
          ],
        ],
        'Count' => NULL,
        'config' => [],
      ],
      'User patterns' => [
        'Source' => self::TEST_DB,
        'Expected' => [
          [
            'name' => 'pathauto_user_pattern',
            'value' => 's:17:"users/[user:name]";',
            'id' => 'user',
            'forum_vocabulary' => FALSE,
            'entity_type' => 'user',
            'bundle' => NULL,
            'weight' => 1,
          ],
        ],
        'Count' => NULL,
        'config' => [
          'entity_type' => 'user',
        ],
      ],
      'Node patterns' => [
        'Source' => self::TEST_DB,
        'Expected' => [
          [
            'name' => 'pathauto_node_article_en_pattern',
            'value' => 's:35:"[node:content-type]/en/[node:title]";',
            'id' => 'node_article_en',
            'forum_vocabulary' => FALSE,
            'entity_type' => 'node',
            'bundle' => 'article',
            'langcode' => 'en',
            'weight' => -1,
          ],
          [
            'name' => 'pathauto_node_article_fr_pattern',
            'value' => 's:35:"[node:content-type]/fr/[node:title]";',
            'id' => 'node_article_fr',
            'forum_vocabulary' => FALSE,
            'entity_type' => 'node',
            'bundle' => 'article',
            'langcode' => 'fr',
            'weight' => -1,
          ],
          [
            'name' => 'pathauto_node_article_is_pattern',
            'value' => 's:35:"[node:content-type]/is/[node:title]";',
            'id' => 'node_article_is',
            'forum_vocabulary' => FALSE,
            'entity_type' => 'node',
            'bundle' => 'article',
            'langcode' => 'is',
            'weight' => -1,
          ],
          [
            'name' => 'pathauto_node_article_pattern',
            'value' => 's:32:"[node:content-type]/[node:title]";',
            'id' => 'node_article',
            'forum_vocabulary' => FALSE,
            'entity_type' => 'node',
            'bundle' => 'article',
            'weight' => 0,
          ],
          [
            'name' => 'pathauto_node_blog_pattern',
            'value' => 's:37:"blogs/[node:author:name]/[node:title]";',
            'id' => 'node_blog',
            'forum_vocabulary' => FALSE,
            'entity_type' => 'node',
            'bundle' => 'blog',
            'weight' => 0,
          ],
          [
            'name' => 'pathauto_node_et_pattern',
            'value' => 's:43:"[node:content-type]/[node:nid]/[node:title]";',
            'id' => 'node_et',
            'forum_vocabulary' => FALSE,
            'entity_type' => 'node',
            'bundle' => 'et',
            'weight' => 0,
          ],
          [
            'name' => 'pathauto_node_pattern',
            'value' => 's:12:"[node:title]";',
            'id' => 'node',
            'forum_vocabulary' => FALSE,
            'entity_type' => 'node',
            'weight' => 1,
            'bundle' => null,
          ],
        ],
        'Count' => NULL,
        'config' => [
          'entity_type' => 'node',
        ],
      ],
      'Article node patterns' => [
        'Source' => self::TEST_DB,
        'Expected' => [
          [
            'name' => 'pathauto_node_article_en_pattern',
            'value' => 's:35:"[node:content-type]/en/[node:title]";',
            'id' => 'node_article_en',
            'forum_vocabulary' => FALSE,
            'entity_type' => 'node',
            'bundle' => 'article',
            'langcode' => 'en',
            'weight' => -1,
          ],
          [
            'name' => 'pathauto_node_article_fr_pattern',
            'value' => 's:35:"[node:content-type]/fr/[node:title]";',
            'id' => 'node_article_fr',
            'forum_vocabulary' => FALSE,
            'entity_type' => 'node',
            'bundle' => 'article',
            'langcode' => 'fr',
            'weight' => -1,
          ],
          [
            'name' => 'pathauto_node_article_is_pattern',
            'value' => 's:35:"[node:content-type]/is/[node:title]";',
            'id' => 'node_article_is',
            'forum_vocabulary' => FALSE,
            'entity_type' => 'node',
            'bundle' => 'article',
            'langcode' => 'is',
            'weight' => -1,
          ],
          [
            'name' => 'pathauto_node_article_pattern',
            'value' => 's:32:"[node:content-type]/[node:title]";',
            'id' => 'node_article',
            'forum_vocabulary' => FALSE,
            'entity_type' => 'node',
            'bundle' => 'article',
            'weight' => 0,
          ],
        ],
        'Count' => NULL,
        'config' => [
          'entity_type' => 'node',
          'bundle' => 'article',
        ],
      ],
      'Only the default pattern of taxonomy terms' => [
        'Source' => self::TEST_DB,
        'Expected' => [
          [
            'name' => 'pathauto_taxonomy_term_pattern',
            'value' => 's:29:"[term:vocabulary]/[term:name]";',
            'id' => 'taxonomy_term',
            'forum_vocabulary' => FALSE,
            'entity_type' => 'taxonomy_term',
            'weight' => 1,
            'bundle' => null,
          ],
        ],
        'Count' => NULL,
        'config' => [
          'entity_type' => 'taxonomy_term',
          'bundle' => FALSE,
        ],
      ],
    ];
  }

  /**
   * The test DB.
   *
   * @const array[]
   */
  const TEST_DB = [
    'system' => [
      'forum' => [
        'name' => 'forum',
        'schema_version' => 7084,
        'type' => 'module',
        'status' => 1,
      ],
      'system' => [
        'name' => 'system',
        'schema_version' => 7084,
        'type' => 'module',
        'status' => 1,
      ],
      'pathauto' => [
        'name' => 'pathauto',
        'schema_version' => 7006,
        'type' => 'module',
        'status' => 1,
      ],
      'taxonomy' => [
        'name' => 'taxonomy',
        'schema_version' => 7084,
        'type' => 'module',
        'status' => 1,
      ],
      'token' => [
        'name' => 'token',
        'schema_version' => 7000,
        'type' => 'module',
        'status' => 1,
      ],
      'locale' => [
        'name' => 'locale',
        'schema_version' => 7005,
        'type' => 'module',
        'status' => 1,
      ],
    ],
    'taxonomy_vocabulary' => [
      [
        'vid' => 2,
        'name' => 'Sujet de discussion',
        'machine_name' => 'sujet_de_discussion',
        'description' => 'Forum navigation vocabulary',
        'hierarchy' => 1,
        'module' => 'forum',
        'weight' => -10,
        'language' => 'und',
      ],
    ],
    'variable' => [
      [
        'name' => 'forum_nav_vocabulary',
        'value' => 's:1:"2";',
      ],
      [
        'name' => 'pathauto_blog_pattern',
        'value' => 's:17:"blogs/[user:name]";',
      ],
      [
        'name' => 'pathauto_case',
        'value' => 's:1:"1";',
      ],
      [
        'name' => 'pathauto_forum_pattern',
        'value' => 's:29:"[term:vocabulary]/[term:name]";',
      ],
      [
        'name' => 'pathauto_ignore_words',
        'value' => 's:134:"a, an, as, at, before, but, by, for, from, is, in, into, like, of, off, on, onto, per, since, than, the, this, that, to, up, via, with";',
      ],
      [
        'name' => 'pathauto_max_component_length',
        'value' => 's:3:"100";',
      ],
      [
        'name' => 'pathauto_max_length',
        'value' => 's:3:"100";',
      ],
      [
        'name' => 'pathauto_node_article_en_pattern',
        'value' => 's:35:"[node:content-type]/en/[node:title]";',
      ],
      [
        'name' => 'pathauto_node_article_fr_pattern',
        'value' => 's:35:"[node:content-type]/fr/[node:title]";',
      ],
      [
        'name' => 'pathauto_node_article_is_pattern',
        'value' => 's:35:"[node:content-type]/is/[node:title]";',
      ],
      [
        'name' => 'pathauto_node_article_pattern',
        'value' => 's:32:"[node:content-type]/[node:title]";',
      ],
      [
        'name' => 'pathauto_node_article_und_pattern',
        'value' => 's:0:"";',
      ],
      [
        'name' => 'pathauto_node_blog_en_pattern',
        'value' => 's:0:"";',
      ],
      [
        'name' => 'pathauto_node_blog_fr_pattern',
        'value' => 's:0:"";',
      ],
      [
        'name' => 'pathauto_node_blog_is_pattern',
        'value' => 's:0:"";',
      ],
      [
        'name' => 'pathauto_node_blog_pattern',
        'value' => 's:37:"blogs/[node:author:name]/[node:title]";',
      ],
      [
        'name' => 'pathauto_node_blog_und_pattern',
        'value' => 's:0:"";',
      ],
      [
        'name' => 'pathauto_node_book_pattern',
        'value' => 's:0:"";',
      ],
      [
        'name' => 'pathauto_node_et_en_pattern',
        'value' => 's:0:"";',
      ],
      [
        'name' => 'pathauto_node_et_fr_pattern',
        'value' => 's:0:"";',
      ],
      [
        'name' => 'pathauto_node_et_is_pattern',
        'value' => 's:0:"";',
      ],
      [
        'name' => 'pathauto_node_et_pattern',
        'value' => 's:43:"[node:content-type]/[node:nid]/[node:title]";',
      ],
      [
        'name' => 'pathauto_node_et_und_pattern',
        'value' => 's:0:"";',
      ],
      [
        'name' => 'pathauto_node_forum_pattern',
        'value' => 's:0:"";',
      ],
      [
        'name' => 'pathauto_node_page_en_pattern',
        'value' => 's:0:"";',
      ],
      [
        'name' => 'pathauto_node_page_fr_pattern',
        'value' => 's:0:"";',
      ],
      [
        'name' => 'pathauto_node_page_is_pattern',
        'value' => 's:0:"";',
      ],
      [
        'name' => 'pathauto_node_page_pattern',
        'value' => 's:0:"";',
      ],
      [
        'name' => 'pathauto_node_page_und_pattern',
        'value' => 's:0:"";',
      ],
      [
        'name' => 'pathauto_node_pattern',
        'value' => 's:12:"[node:title]";',
      ],
      [
        'name' => 'pathauto_node_test_content_type_en_pattern',
        'value' => 's:0:"";',
      ],
      [
        'name' => 'pathauto_node_test_content_type_fr_pattern',
        'value' => 's:0:"";',
      ],
      [
        'name' => 'pathauto_node_test_content_type_is_pattern',
        'value' => 's:0:"";',
      ],
      [
        'name' => 'pathauto_node_test_content_type_pattern',
        'value' => 's:0:"";',
      ],
      [
        'name' => 'pathauto_node_test_content_type_und_pattern',
        'value' => 's:0:"";',
      ],
      [
        'name' => 'pathauto_punctuation_ampersand',
        'value' => 's:1:"0";',
      ],
      [
        'name' => 'pathauto_punctuation_asterisk',
        'value' => 's:1:"0";',
      ],
      [
        'name' => 'pathauto_punctuation_at',
        'value' => 's:1:"0";',
      ],
      [
        'name' => 'pathauto_punctuation_backtick',
        'value' => 's:1:"0";',
      ],
      [
        'name' => 'pathauto_punctuation_back_slash',
        'value' => 's:1:"0";',
      ],
      [
        'name' => 'pathauto_punctuation_caret',
        'value' => 's:1:"0";',
      ],
      [
        'name' => 'pathauto_punctuation_colon',
        'value' => 's:1:"0";',
      ],
      [
        'name' => 'pathauto_punctuation_comma',
        'value' => 's:1:"0";',
      ],
      [
        'name' => 'pathauto_punctuation_dollar',
        'value' => 's:1:"0";',
      ],
      [
        'name' => 'pathauto_punctuation_double_quotes',
        'value' => 's:1:"0";',
      ],
      [
        'name' => 'pathauto_punctuation_equal',
        'value' => 's:1:"0";',
      ],
      [
        'name' => 'pathauto_punctuation_exclamation',
        'value' => 's:1:"0";',
      ],
      [
        'name' => 'pathauto_punctuation_greater_than',
        'value' => 's:1:"0";',
      ],
      [
        'name' => 'pathauto_punctuation_hash',
        'value' => 's:1:"0";',
      ],
      [
        'name' => 'pathauto_punctuation_hyphen',
        'value' => 's:1:"1";',
      ],
      [
        'name' => 'pathauto_punctuation_left_curly',
        'value' => 's:1:"0";',
      ],
      [
        'name' => 'pathauto_punctuation_left_parenthesis',
        'value' => 's:1:"0";',
      ],
      [
        'name' => 'pathauto_punctuation_left_square',
        'value' => 's:1:"0";',
      ],
      [
        'name' => 'pathauto_punctuation_less_than',
        'value' => 's:1:"0";',
      ],
      [
        'name' => 'pathauto_punctuation_percent',
        'value' => 's:1:"0";',
      ],
      [
        'name' => 'pathauto_punctuation_period',
        'value' => 's:1:"0";',
      ],
      [
        'name' => 'pathauto_punctuation_pipe',
        'value' => 's:1:"0";',
      ],
      [
        'name' => 'pathauto_punctuation_plus',
        'value' => 's:1:"0";',
      ],
      [
        'name' => 'pathauto_punctuation_question_mark',
        'value' => 's:1:"0";',
      ],
      [
        'name' => 'pathauto_punctuation_quotes',
        'value' => 's:1:"0";',
      ],
      [
        'name' => 'pathauto_punctuation_right_curly',
        'value' => 's:1:"0";',
      ],
      [
        'name' => 'pathauto_punctuation_right_parenthesis',
        'value' => 's:1:"0";',
      ],
      [
        'name' => 'pathauto_punctuation_right_square',
        'value' => 's:1:"0";',
      ],
      [
        'name' => 'pathauto_punctuation_semicolon',
        'value' => 's:1:"0";',
      ],
      [
        'name' => 'pathauto_punctuation_slash',
        'value' => 's:1:"0";',
      ],
      [
        'name' => 'pathauto_punctuation_tilde',
        'value' => 's:1:"0";',
      ],
      [
        'name' => 'pathauto_punctuation_underscore',
        'value' => 's:1:"0";',
      ],
      [
        'name' => 'pathauto_reduce_ascii',
        'value' => 'i:0;',
      ],
      [
        'name' => 'pathauto_separator',
        'value' => 's:1:"-";',
      ],
      [
        'name' => 'pathauto_taxonomy_term_sujet_de_discussion_pattern',
        'value' => 's:0:"";',
      ],
      [
        'name' => 'pathauto_taxonomy_term_tags_pattern',
        'value' => 's:15:"tag/[term:name]";',
      ],
      [
        'name' => 'pathauto_taxonomy_term_test_vocabulary_pattern',
        'value' => 's:0:"";',
      ],
      [
        'name' => 'pathauto_taxonomy_term_vocabfixed_pattern',
        'value' => 's:0:"";',
      ],
      [
        'name' => 'pathauto_taxonomy_term_vocablocalized_pattern',
        'value' => 's:0:"";',
      ],
      [
        'name' => 'pathauto_taxonomy_term_vocabtranslate_pattern',
        'value' => 's:0:"";',
      ],
      [
        'name' => 'pathauto_taxonomy_term_vocabulary_name_much_longer_than_thirty_two_characters_pattern',
        'value' => 's:0:"";',
      ],
      [
        'name' => 'pathauto_taxonomy_term_pattern',
        'value' => 's:29:"[term:vocabulary]/[term:name]";',
      ],
      [
        'name' => 'pathauto_transliterate',
        'value' => 'i:1;',
      ],
      [
        'name' => 'pathauto_update_action',
        'value' => 's:1:"2";',
      ],
      [
        'name' => 'pathauto_user_pattern',
        'value' => 's:17:"users/[user:name]";',
      ],
      [
        'name' => 'pathauto_verbose',
        'value' => 'i:0;',
      ],
    ],
    'languages' => [
      [
        'language' => 'en',
        'enabled' => 1,
      ],
      [
        'language' => 'fr',
        'enabled' => 1,
      ],
      [
        'language' => 'is',
        'enabled' => 1,
      ],
    ],
  ];

}
