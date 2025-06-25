<?php

namespace Drupal\Tests\pathauto\Traits;

use Drupal\Core\Entity\EntityInterface;
use Drupal\pathauto\Entity\PathautoPattern;
use Drupal\pathauto\PathautoPatternInterface;

/**
 * Trait for testing pathauto migration results.
 */
trait PathautoMigrationAssertionsTrait {

  /**
   * List of pathauto pattern properties which are irrelevant.
   *
   * @var string[]
   */
  protected $pathautoPatternUnconcernedProperties = [
    'uuid',
    'dependencies',
  ];

  /**
   * Tests pathauto settings.
   */
  protected function assertPathautoSettings() {
    $raw_data = $this->config('pathauto.settings')->getRawData();
    unset($raw_data['_core']);
    $this->assertEquals([
      'enabled_entity_types' => [
        0 => 'user',
      ],
      'punctuation' => [
        'hyphen' => 1,
        'ampersand' => 0,
        'asterisk' => 0,
        'at' => 0,
        'backtick' => 0,
        'back_slash' => 0,
        'caret' => 0,
        'colon' => 0,
        'comma' => 0,
        'dollar' => 0,
        'double_quotes' => 0,
        'equal' => 0,
        'exclamation' => 0,
        'greater_than' => 0,
        'hash' => 0,
        'left_curly' => 0,
        'left_parenthesis' => 0,
        'left_square' => 0,
        'less_than' => 0,
        'percent' => 0,
        'period' => 0,
        'pipe' => 0,
        'plus' => 0,
        'question_mark' => 0,
        'quotes' => 0,
        'right_curly' => 0,
        'right_parenthesis' => 0,
        'right_square' => 0,
        'semicolon' => 0,
        'slash' => 0,
        'tilde' => 0,
        'underscore' => 0,
      ],
      'verbose' => FALSE,
      'separator' => '-',
      'max_length' => 100,
      'max_component_length' => 100,
      'transliterate' => TRUE,
      'reduce_ascii' => FALSE,
      'case' => TRUE,
      'ignore_words' => 'a, an, as, at, before, but, by, for, from, is, in, into, like, of, off, on, onto, per, since, than, the, this, that, to, up, via, with',
      'update_action' => 2,
      'safe_tokens' => [
        0 => 'alias',
        1 => 'path',
        2 => 'join-path',
        3 => 'login-url',
        4 => 'url',
        5 => 'url-brief',
      ],
    ], $raw_data);
  }

  /**
   * Tests article node's pattern.
   */
  protected function assertNodeArticlePattern(?int $uuid_index = NULL) {
    $pattern = PathautoPattern::load('node_article');
    assert($pattern instanceof PathautoPatternInterface);
    if (!isset($uuid_index)) {
      $uuid_index = $this->multilingual ? 7 : 4;
    }
    $this->assertEquals([
      'langcode' => 'en',
      'status' => TRUE,
      'id' => 'node_article',
      'label' => 'Content - Article',
      'type' => 'canonical_entities:node',
      'pattern' => '[node:content-type]/[node:title]',
      'selection_criteria' => [
        "uuid{$uuid_index}" => [
          'uuid' => "uuid{$uuid_index}",
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
      'selection_logic' => 'and',
      'weight' => 0,
      'relationships' => [],
    ], $this->getImportantEntityProperties($pattern));
  }

  /**
   * Tests article node's English pattern.
   */
  protected function assertNodeArticleEnPattern(int $uuid_index = 1) {
    $pattern = PathautoPattern::load('node_article_en');
    assert($pattern instanceof PathautoPatternInterface);
    $next_uuid_index = $uuid_index + 1;
    $this->assertEquals([
      'langcode' => 'en',
      'status' => TRUE,
      'id' => 'node_article_en',
      'label' => 'Content - Article (en)',
      'type' => 'canonical_entities:node',
      'pattern' => '[node:content-type]/en/[node:title]',
      'selection_criteria' => [
        "uuid{$uuid_index}" => [
          'uuid' => "uuid{$uuid_index}",
          'id' => 'node_type',
          'bundles' => [
            'article' => 'article',
          ],
          'negate' => FALSE,
          'context_mapping' => [
            'node' => 'node',
          ],
        ],
        "uuid{$next_uuid_index}" => [
          'uuid' => "uuid{$next_uuid_index}",
          'id' => 'language',
          'langcodes' => [
            'en' => 'en',
          ],
          'negate' => FALSE,
          'context_mapping' => [
            'language' => 'node:langcode:language',
          ],
        ],
      ],
      'selection_logic' => 'and',
      'weight' => -1,
      'relationships' => ['node:langcode:language' => ['label' => 'Language']],
    ], $this->getImportantEntityProperties($pattern));
  }

  /**
   * Tests article node's French pattern.
   */
  protected function assertNodeArticleFrPattern(int $uuid_index = 3) {
    $pattern = PathautoPattern::load('node_article_fr');
    assert($pattern instanceof PathautoPatternInterface);
    $next_uuid_index = $uuid_index + 1;
    $this->assertEquals([
      'langcode' => 'en',
      'status' => TRUE,
      'id' => 'node_article_fr',
      'label' => 'Content - Article (fr)',
      'type' => 'canonical_entities:node',
      'pattern' => '[node:content-type]/fr/[node:title]',
      'selection_criteria' => [
        "uuid{$uuid_index}" => [
          'uuid' => "uuid{$uuid_index}",
          'id' => 'node_type',
          'bundles' => [
            'article' => 'article',
          ],
          'negate' => FALSE,
          'context_mapping' => [
            'node' => 'node',
          ],
        ],
        "uuid{$next_uuid_index}" => [
          'uuid' => "uuid{$next_uuid_index}",
          'id' => 'language',
          'langcodes' => [
            'fr' => 'fr',
          ],
          'negate' => FALSE,
          'context_mapping' => [
            'language' => 'node:langcode:language',
          ],
        ],
      ],
      'selection_logic' => 'and',
      'weight' => -1,
      'relationships' => ['node:langcode:language' => ['label' => 'Language']],
    ], $this->getImportantEntityProperties($pattern));
  }

  /**
   * Tests article node's Icelandic pattern.
   */
  protected function assertNodeArticleIsPattern(int $uuid_index = 5) {
    $pattern = PathautoPattern::load('node_article_is');
    assert($pattern instanceof PathautoPatternInterface);
    $next_uuid_index = $uuid_index + 1;
    $this->assertEquals([
      'langcode' => 'en',
      'status' => TRUE,
      'id' => 'node_article_is',
      'label' => 'Content - Article (is)',
      'type' => 'canonical_entities:node',
      'pattern' => '[node:content-type]/is/[node:title]',
      'selection_criteria' => [
        "uuid{$uuid_index}" => [
          'uuid' => "uuid{$uuid_index}",
          'id' => 'node_type',
          'bundles' => [
            'article' => 'article',
          ],
          'negate' => FALSE,
          'context_mapping' => [
            'node' => 'node',
          ],
        ],
        "uuid{$next_uuid_index}" => [
          'uuid' => "uuid{$next_uuid_index}",
          'id' => 'language',
          'langcodes' => [
            'is' => 'is',
          ],
          'negate' => FALSE,
          'context_mapping' => [
            'language' => 'node:langcode:language',
          ],
        ],
      ],
      'selection_logic' => 'and',
      'weight' => -1,
      'relationships' => ['node:langcode:language' => ['label' => 'Language']],
    ], $this->getImportantEntityProperties($pattern));
  }

  /**
   * Tests blog node's pattern.
   */
  protected function assertNodeBlogPattern(?int $uuid_index = NULL) {
    $pattern = PathautoPattern::load('node_blog');
    assert($pattern instanceof PathautoPatternInterface);
    if (!isset($uuid_index)) {
      $uuid_index = $this->multilingual ? 8 : 5;
    }
    $this->assertEquals([
      'langcode' => 'en',
      'status' => TRUE,
      'id' => 'node_blog',
      'label' => 'Content - Blog entry',
      'type' => 'canonical_entities:node',
      'pattern' => 'blogs/[node:author:name]/[node:title]',
      'selection_criteria' => [
        "uuid{$uuid_index}" => [
          'uuid' => "uuid{$uuid_index}",
          'id' => 'node_type',
          'bundles' => [
            'blog' => 'blog',
          ],
          'negate' => FALSE,
          'context_mapping' => [
            'node' => 'node',
          ],
        ],
      ],
      'selection_logic' => 'and',
      'weight' => 0,
      'relationships' => [],
    ], $this->getImportantEntityProperties($pattern));
  }

  /**
   * Tests et node's pattern.
   */
  protected function assertNodeEtPattern(?int $uuid_index = NULL) {
    $pattern = PathautoPattern::load('node_et');
    assert($pattern instanceof PathautoPatternInterface);
    if (!isset($uuid_index)) {
      $uuid_index = $this->multilingual ? 9 : 6;
    }
    $this->assertEquals([
      'langcode' => 'en',
      'status' => TRUE,
      'id' => 'node_et',
      'label' => 'Content - Entity translation test',
      'type' => 'canonical_entities:node',
      'pattern' => '[node:content-type]/[node:nid]/[node:title]',
      'selection_criteria' => [
        "uuid{$uuid_index}" => [
          'uuid' => "uuid{$uuid_index}",
          'id' => 'node_type',
          'bundles' => [
            'et' => 'et',
          ],
          'negate' => FALSE,
          'context_mapping' => [
            'node' => 'node',
          ],
        ],
      ],
      'selection_logic' => 'and',
      'weight' => 0,
      'relationships' => [],
    ], $this->getImportantEntityProperties($pattern));
  }

  /**
   * Tests default node pattern.
   */
  protected function assertNodePattern() {
    $pattern = PathautoPattern::load('node');
    assert($pattern instanceof PathautoPatternInterface);
    $this->assertEquals([
      'langcode' => 'en',
      'status' => TRUE,
      'id' => 'node',
      'label' => 'Content - default',
      'type' => 'canonical_entities:node',
      'pattern' => '[node:title]',
      'selection_criteria' => [],
      'selection_logic' => 'and',
      'weight' => 1,
      'relationships' => [],
    ], $this->getImportantEntityProperties($pattern));
  }

  /**
   * Tests tags taxonomy term's pattern.
   */
  protected function assertTermTagsPattern(?int $uuid_index = NULL) {
    $pattern = PathautoPattern::load('taxonomy_term_tags');
    assert($pattern instanceof PathautoPatternInterface);
    if (!isset($uuid_index)) {
      $uuid_index = $this->multilingual ? 11 : ($this->withForum ? 8 : 7);
    }
    $this->assertEquals([
      'langcode' => 'en',
      'status' => TRUE,
      'id' => 'taxonomy_term_tags',
      'label' => 'Taxonomy term - Tags',
      'type' => 'canonical_entities:taxonomy_term',
      'pattern' => 'tag/[term:name]',
      'selection_criteria' => [
        "uuid{$uuid_index}" => [
          'uuid' => "uuid{$uuid_index}",
          'id' => 'entity_bundle:taxonomy_term',
          'bundles' => [
            'tags' => 'tags',
          ],
          'negate' => FALSE,
          'context_mapping' => [
            'taxonomy_term' => 'taxonomy_term',
          ],
        ],
      ],
      'selection_logic' => 'and',
      'weight' => 0,
      'relationships' => [],
    ], $this->getImportantEntityProperties($pattern));
  }

  /**
   * Tests forums taxonomy term's pattern.
   */
  protected function assertTermForumsPattern(?int $uuid_index = NULL) {
    $pattern = PathautoPattern::load('forum');
    assert($pattern instanceof PathautoPatternInterface);
    if (!isset($uuid_index)) {
      $uuid_index = $this->multilingual ? 10 : 7;
    }
    $this->assertEquals([
      'langcode' => 'en',
      'status' => TRUE,
      'id' => 'forum',
      'label' => 'Taxonomy term - Sujet de discussion',
      'type' => 'canonical_entities:taxonomy_term',
      'pattern' => '[term:vocabulary]/[term:name]',
      'selection_criteria' => [
        "uuid{$uuid_index}" => [
          'uuid' => "uuid{$uuid_index}",
          'id' => 'entity_bundle:taxonomy_term',
          'bundles' => [
            'forums' => 'forums',
          ],
          'negate' => FALSE,
          'context_mapping' => [
            'taxonomy_term' => 'taxonomy_term',
          ],
        ],
      ],
      'selection_logic' => 'and',
      'weight' => 0,
      'relationships' => [],
    ], $this->getImportantEntityProperties($pattern));
  }

  /**
   * Tests default taxonomy term pattern.
   */
  protected function assertTermPattern() {
    $pattern = PathautoPattern::load('taxonomy_term');
    assert($pattern instanceof PathautoPatternInterface);
    $this->assertEquals([
      'langcode' => 'en',
      'status' => TRUE,
      'id' => 'taxonomy_term',
      'label' => 'Taxonomy term - default',
      'type' => 'canonical_entities:taxonomy_term',
      'pattern' => '[term:vocabulary]/[term:name]',
      'selection_criteria' => [],
      'selection_logic' => 'and',
      'weight' => 1,
      'relationships' => [],
    ], $this->getImportantEntityProperties($pattern));
  }

  /**
   * Tests user's pattern.
   */
  protected function assertUserPattern() {
    $pattern = PathautoPattern::load('user');
    assert($pattern instanceof PathautoPatternInterface);
    $this->assertEquals([
      'langcode' => 'en',
      'status' => TRUE,
      'id' => 'user',
      'label' => 'User - default',
      'type' => 'canonical_entities:user',
      'pattern' => 'users/[user:name]',
      'selection_criteria' => [],
      'selection_logic' => 'and',
      'weight' => 1,
      'relationships' => [],
    ], $this->getImportantEntityProperties($pattern));
  }

  /**
   * Tests every pattern of article node's, including language-specific ones.
   */
  protected function assertAllNodeArticlePatterns() {
    $this->assertNodeArticlePattern();
    $this->assertNodeArticleEnPattern();
    $this->assertNodeArticleFrPattern();
    $this->assertNodeArticleIsPattern();
    $pattern_und = PathautoPattern::load('node_article_und');
    $this->assertNull($pattern_und);
  }

  /**
   * Filters out unconcerned properties from an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   An entity instance.
   *
   * @return array
   *   The important entity property values as array.
   */
  protected function getImportantEntityProperties(EntityInterface $entity) {
    $entity_type_id = $entity->getEntityTypeId();
    $exploded = explode('_', $entity_type_id);
    $prop_prefix = count($exploded) > 1
      ? $exploded[0] . implode('', array_map('ucfirst', array_slice($exploded, 1)))
      : $entity_type_id;
    $property_filter_preset_property = "{$prop_prefix}UnconcernedProperties";
    $entity_array = $entity->toArray();
    $unconcerned_properties = property_exists(get_class($this), $property_filter_preset_property)
      ? $this->$property_filter_preset_property
      : [
        'uuid',
        'langcode',
        'dependencies',
        '_core',
      ];

    foreach ($unconcerned_properties as $item) {
      unset($entity_array[$item]);
    }

    return $entity_array;
  }

}
