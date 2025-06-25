<?php

namespace Drupal\Tests\paragraphs_migration\Traits;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\TypedData\TranslatableInterface;
use Drupal\entity_reference_revisions\EntityReferenceRevisionsFieldItemList;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\paragraphs\ParagraphInterface;

/**
 * Common assertions for the migrated paragraphs test nodes.
 */
trait ParagraphsNodeMigrationAssertionsTrait {

  /**
   * List of node properties whose value shouldn't have to be checked.
   *
   * @var string[]
   */
  protected $nodeUnconcernedProperties = [
    'uuid',
    'revision_timestamp',
    'revision_uid',
    'revision_log',
    'revision_default',
    'revision_translation_affected',
    'path',
    'comment_node_paragraphs_test',
    'comment_node_content_with_para',
    'comment_node_content_with_coll',
    'content_translation_source',
    'content_translation_outdated',
  ];

  /**
   * List of paragraph properties whose value shouldn't have been checked.
   *
   * @var string[]
   */
  protected $paragraphUnconcernedProperties = [
    'uuid',
    'created',
    'behavior_settings',
    // We test the values of the referred paragraphs, so we assume that we don't
    // have to test the IDs (since the IDs vary by node migration type).
    'id',
    'revision_id',
    'parent_id',
    'parent_type',
  ];

  /**
   * Assertions on node 8.
   */
  protected function assertNode8Paragraphs() {
    $node_8 = Node::load(8);
    assert($node_8 instanceof NodeInterface);
    // Check 'field collection test' field.
    $node_8_field_collection_field_entities = $this->getReferencedEntities($node_8, 'field_field_collection_test', 2);
    $this->assertEquals('Field Collection Text Data One UND', $node_8_field_collection_field_entities[0]->field_text->value);
    $this->assertEquals('1', $node_8_field_collection_field_entities[0]->field_integer_list->value);
    $this->assertEquals('Field Collection Text Data Two UND', $node_8_field_collection_field_entities[1]->field_text->value);
    $this->assertNull($node_8_field_collection_field_entities[1]->field_integer_list->value);
    // Check 'any paragraph' field.
    $node_8_field_any_paragraph_entities = $this->getReferencedEntities($node_8, 'field_any_paragraph', 2);
    $this->assertEquals('Paragraph Field One Bundle One UND', $node_8_field_any_paragraph_entities[0]->field_text->value);
    $this->assertEquals('Some Text', $node_8_field_any_paragraph_entities[0]->field_text_list->value);
    $this->assertEquals('Paragraph Field One Bundle Two UND', $node_8_field_any_paragraph_entities[1]->field_text->value);
    $this->assertEquals('joe@joe.com', $node_8_field_any_paragraph_entities[1]->field_email->value);
    // Check 'paragraph one only' field.
    $node_8_field_paragraph_one_only_entities = $this->getReferencedEntities($node_8, 'field_paragraph_one_only', 1);
    $this->assertEquals('Paragraph Field Two Bundle One Revision Two UND', $node_8_field_paragraph_one_only_entities[0]->field_text->value);
    $this->assertEquals('Some more text', $node_8_field_paragraph_one_only_entities[0]->field_text_list->value);
    // Check 'nested fc outer' field.
    $node_8_field_nested_fc_outer_entities = $this->getReferencedEntities($node_8, 'field_nested_fc_outer', 1);
    assert($node_8_field_nested_fc_outer_entities[0] instanceof ParagraphInterface);
    $node_8_inner_nested_fc_0_entities = $this->getReferencedEntities($node_8_field_nested_fc_outer_entities[0], 'field_nested_fc_inner', 1);
    $this->assertEquals('Nested FC test text', $node_8_inner_nested_fc_0_entities[0]->field_text->value);
  }

  /**
   * Assertions of node 9.
   */
  protected function assertNode9Paragraphs() {
    $node_9 = Node::load(9);
    assert($node_9 instanceof NodeInterface);

    if ($this->container->get('module_handler')->moduleExists('content_translation') && $node_9 instanceof TranslatableInterface) {
      // Test the default translation.
      $node_9 = $node_9->getUntranslated();
      $this->assertSame('en', $node_9->language()->getId());
    }

    // Check 'field collection test' field.
    $node_9_field_collection_field_entities = $this->getReferencedEntities($node_9, 'field_field_collection_test', 1);
    $this->assertEquals('Field Collection Text Data Two EN', $node_9_field_collection_field_entities[0]->field_text->value);
    $this->assertEquals('2', $node_9_field_collection_field_entities[0]->field_integer_list->value);
    // Check 'any paragraph' field.
    $node_9_field_any_paragraph_entities = $this->getReferencedEntities($node_9, 'field_any_paragraph', 2);
    $this->assertEquals('Paragraph Field One Bundle One EN', $node_9_field_any_paragraph_entities[0]->field_text->value);
    $this->assertEquals('Some Text', $node_9_field_any_paragraph_entities[0]->field_text_list->value);
    $this->assertEquals('Paragraph Field One Bundle Two EN', $node_9_field_any_paragraph_entities[1]->field_text->value);
    $this->assertEquals('jose@jose.com', $node_9_field_any_paragraph_entities[1]->field_email->value);
    // Check 'paragraph one only' field.
    $node_9_field_paragraph_one_only_entities = $this->getReferencedEntities($node_9, 'field_paragraph_one_only', 1);
    $this->assertEquals('Paragraph Field Two Bundle One EN', $node_9_field_paragraph_one_only_entities[0]->field_text->value);
    $this->assertEquals('Some Text', $node_9_field_paragraph_one_only_entities[0]->field_text_list->value);
    // The 'nested fc outer' field should be empty.
    $this->getReferencedEntities($node_9, 'field_nested_fc_outer', 0);
  }

  /**
   * Assertions of the Icelandic translation of node 9.
   */
  protected function assertIcelandicNode9Paragraphs() {
    // Confirm that the Icelandic translation of node 9 (which was node 10 on
    // the source site) has the expected data.
    $node_9 = Node::load(9);
    assert($node_9 instanceof NodeInterface);
    assert($node_9 instanceof TranslatableInterface);
    $node_9_translation_languages = $node_9->getTranslationLanguages(FALSE);
    $this->assertEquals(['is'], array_keys($node_9_translation_languages));
    $node_9 = $node_9->getTranslation('is');
    $this->assertSame('is', $node_9->language()->getId());

    // Check 'field collection test' field.
    $node_9_field_collection_field_entities = $this->getReferencedEntities($node_9, 'field_field_collection_test', 3);
    $this->assertEquals('Field Collection Text Data One IS', $node_9_field_collection_field_entities[0]->field_text->value);
    $this->assertEquals('1', $node_9_field_collection_field_entities[0]->field_integer_list->value);
    $this->assertEquals('Field Collection Text Data Two IS', $node_9_field_collection_field_entities[1]->field_text->value);
    $this->assertEquals('2', $node_9_field_collection_field_entities[1]->field_integer_list->value);
    $this->assertEquals('Field Collection Text Data Three IS', $node_9_field_collection_field_entities[2]->field_text->value);
    $this->assertEquals('3', $node_9_field_collection_field_entities[2]->field_integer_list->value);
    // Check 'any paragraph' field.
    $node_9_field_any_paragraph_entities = $this->getReferencedEntities($node_9, 'field_any_paragraph', 3);
    $this->assertEquals('Paragraph Field One Bundle One IS', $node_9_field_any_paragraph_entities[0]->field_text->value);
    $this->assertEquals('Some Text', $node_9_field_any_paragraph_entities[0]->field_text_list->value);
    $this->assertEquals('Paragraph Field One Bundle Two IS', $node_9_field_any_paragraph_entities[1]->field_text->value);
    $this->assertEquals('jose@jose.com', $node_9_field_any_paragraph_entities[1]->field_email->value);
    $this->assertEquals('Paragraph Field One Bundle Two Delta 3 IS', $node_9_field_any_paragraph_entities[2]->field_text->value);
    $this->assertEquals('john@john.com', $node_9_field_any_paragraph_entities[2]->field_email->value);
    // Check 'paragraph one only' field.
    $node_9_field_paragraph_one_only_entities = $this->getReferencedEntities($node_9, 'field_paragraph_one_only', 1);
    $this->assertEquals('Paragraph Field Two Bundle One IS', $node_9_field_paragraph_one_only_entities[0]->field_text->value);
    $this->assertEquals('Some more text', $node_9_field_paragraph_one_only_entities[0]->field_text_list->value);
    // The 'nested fc outer' field should be empty.
    $this->getReferencedEntities($node_9, 'field_nested_fc_outer', 0);
  }

  /**
   * Assertions of node 11.
   */
  protected function assertNode11Paragraphs() {
    $node = $this->container->get('entity_type.manager')->getStorage('node')->load(11);
    assert($node instanceof NodeInterface);
    $node_important_properties = $this->getImportantEntityProperties($node);
    // Don't care about paragraph field content and referenced IDs: we test the
    // values of the referenced entities.
    unset($node_important_properties['field_any_paragraph']);

    $this->assertEquals([
      'nid' => [['value' => 11]],
      'vid' => [['value' => 12]],
      'langcode' => [['value' => 'en']],
      'default_langcode' => [['value' => 1]],
      'type' => [['target_id' => 'paragraphs_test']],
      'status' => [['value' => 1]],
      'uid' => [['target_id' => 1]],
      'title' => [['value' => 'Content with nested paragraphs ENG']],
      'created' => [['value' => 1591702138]],
      'changed' => [['value' => 1591702138]],
      'promote' => [['value' => 1]],
      'sticky' => [['value' => 0]],
      'body' => [],
      'field_field_collection_test' => [],
      'field_nested_fc_outer' => [],
      'field_paragraph_one_only' => [],
    ], $node_important_properties);

    // Check the paragraph entities referenced by the node.
    $field_any_paragraph_entities = $this->getReferencedEntities($node, 'field_any_paragraph', 2);

    assert($field_any_paragraph_entities[0] instanceof ParagraphInterface);
    $this->assertEquals([
      'langcode' => [['value' => 'en']],
      'type' => [['target_id' => 'paragraph_bundle_two']],
      'status' => [['value' => 1]],
      'parent_field_name' => [['value' => 'field_any_paragraph']],
      'default_langcode' => [['value' => 1]],
      'revision_default' => [['value' => 1]],
      'revision_translation_affected' => [['value' => 1]],
      'field_text' => [
        0 => [
          'value' => 'Non-nested bundle two ENG',
        ],
      ],
      'field_email' => [
        0 => [
          'value' => 'non-nested-email-1@bundle.two.eng',
        ],
        1 => [
          'value' => 'non-nested-email-2@bundle.two.eng',
        ],
      ],
    ], $this->getImportantEntityProperties($field_any_paragraph_entities[0]));

    assert($field_any_paragraph_entities[1] instanceof ParagraphInterface);
    $para_important_properties = $this->getImportantEntityProperties($field_any_paragraph_entities[1]);
    // Don't care about paragraph field content and referenced IDs: we test the
    // values of the referenced entities.
    unset($para_important_properties['field_any_paragraph']);
    $this->assertEquals([
      'langcode' => [['value' => 'en']],
      'type' => [['target_id' => 'nested_host']],
      'status' => [['value' => 1]],
      'parent_field_name' => [['value' => 'field_any_paragraph']],
      'default_langcode' => [['value' => 1]],
      'revision_default' => [['value' => 1]],
      'revision_translation_affected' => [['value' => 1]],
      'field_text_long_filtered' => [],
    ], $para_important_properties);

    // Check the paragraph entities referenced by the second host paragraph.
    $para_field_any_paragraph_entities = $this->getReferencedEntities($field_any_paragraph_entities[1], 'field_any_paragraph', 2);
    assert($para_field_any_paragraph_entities[0] instanceof ParagraphInterface);
    $this->assertEquals([
      'langcode' => [['value' => 'en']],
      'type' => [['target_id' => 'paragraph_bundle_one']],
      'status' => [['value' => 1]],
      'parent_field_name' => [['value' => 'field_any_paragraph']],
      'default_langcode' => [['value' => 1]],
      'revision_default' => [['value' => 1]],
      'revision_translation_affected' => [['value' => 1]],
      'field_text' => [
        0 => [
          'value' => "Nested Paragraph Bundle One ENG with 'The label'",
        ],
      ],
      'field_text_list' => [
        0 => [
          'value' => 'The key',
        ],
      ],
    ], $this->getImportantEntityProperties($para_field_any_paragraph_entities[0]));

    assert($para_field_any_paragraph_entities[1] instanceof ParagraphInterface);
    $this->assertEquals([
      'langcode' => [['value' => 'en']],
      'type' => [['target_id' => 'paragraph_bundle_two']],
      'status' => [['value' => 1]],
      'parent_field_name' => [['value' => 'field_any_paragraph']],
      'default_langcode' => [['value' => 1]],
      'revision_default' => [['value' => 1]],
      'revision_translation_affected' => [['value' => 1]],
      'field_text' => [
        0 => [
          'value' => 'Nested Paragraph Bundle Two ENG',
        ],
      ],
      'field_email' => [
        0 => [
          'value' => 'nested-email@bundle.two.eng',
        ],
      ],
    ], $this->getImportantEntityProperties($para_field_any_paragraph_entities[1]));
  }

  /**
   * Assertions of node 12.
   */
  protected function assertNode12Paragraphs() {
    // Check the values of the host node.
    $node = $this->container->get('entity_type.manager')->getStorage('node')->load(12);
    assert($node instanceof NodeInterface);
    $node_important_properties = $this->getImportantEntityProperties($node);
    unset($node_important_properties['field_any_paragraph']);
    $this->assertEquals([
      'nid' => [['value' => 12]],
      'vid' => [['value' => 13]],
      'langcode' => [['value' => 'en']],
      'default_langcode' => [['value' => 1]],
      'type' => [['target_id' => 'content_with_para']],
      'status' => [['value' => 1]],
      'uid' => [['target_id' => 1]],
      'title' => [['value' => 'Nested Paragraphs']],
      'created' => [['value' => 1596184690]],
      'changed' => [['value' => 1596185122]],
      'promote' => [['value' => 0]],
      'sticky' => [['value' => 0]],
    ], $node_important_properties);

    // Check the paragraph entities referenced by the node.
    $field_any_paragraph_entities = $this->getReferencedEntities($node, 'field_any_paragraph', 3);

    assert($field_any_paragraph_entities[0] instanceof ParagraphInterface);
    $this->assertEquals([
      'langcode' => [['value' => 'en']],
      'type' => [['target_id' => 'paragraph_bundle_one']],
      'status' => [['value' => 1]],
      'parent_field_name' => [['value' => 'field_any_paragraph']],
      'default_langcode' => [['value' => 1]],
      'revision_default' => [['value' => 1]],
      'revision_translation_affected' => [['value' => 1]],
      'field_text' => [
        0 => [
          'value' => "Paragraph Bundle One, nesting 0, d0, Some Text",
        ],
      ],
      'field_text_list' => [
        0 => [
          'value' => 'Some Text',
        ],
      ],
    ], $this->getImportantEntityProperties($field_any_paragraph_entities[0]));

    assert($field_any_paragraph_entities[1] instanceof ParagraphInterface);
    $para_important_properties = $this->getImportantEntityProperties($field_any_paragraph_entities[1]);
    // Don't care about paragraph field content and referenced IDs: we test the
    // values of the referenced entities.
    unset($para_important_properties['field_any_paragraph']);
    $this->assertEquals([
      'langcode' => [['value' => 'en']],
      'type' => [['target_id' => 'nested_host']],
      'status' => [['value' => 1]],
      'parent_field_name' => [['value' => 'field_any_paragraph']],
      'default_langcode' => [['value' => 1]],
      'revision_default' => [['value' => 1]],
      'revision_translation_affected' => [['value' => 1]],
      'field_text_long_filtered' => [
        0 => [
          'value' => 'Host for nested paragraphs, nesting 0, d1',
          'format' => 'filtered_html',
        ],
      ],
    ], $para_important_properties);

    assert($field_any_paragraph_entities[2] instanceof ParagraphInterface);
    $this->assertEquals([
      'langcode' => [['value' => 'en']],
      'type' => [['target_id' => 'paragraph_bundle_two']],
      'status' => [['value' => 1]],
      'parent_field_name' => [['value' => 'field_any_paragraph']],
      'default_langcode' => [['value' => 1]],
      'revision_default' => [['value' => 1]],
      'revision_translation_affected' => [['value' => 1]],
      'field_text' => [
        0 => [
          'value' => 'Paragraph Bundle Two, nesting 0, d1, security@drupal.org',
        ],
      ],
      'field_email' => [
        0 => [
          'value' => 'security@drupal.org',
        ],
      ],
    ], $this->getImportantEntityProperties($field_any_paragraph_entities[2]));

    // Now check the nested paragraph entities referenced by the second node
    // paragraphs field item (delta = 1).
    $para_field_any_paragraph_entities = $this->getReferencedEntities($field_any_paragraph_entities[1], 'field_any_paragraph', 2);
    assert($para_field_any_paragraph_entities[0] instanceof ParagraphInterface);
    $this->assertEquals([
      'langcode' => [['value' => 'en']],
      'type' => [['target_id' => 'paragraph_bundle_two']],
      'status' => [['value' => 1]],
      'parent_field_name' => [['value' => 'field_any_paragraph']],
      'default_langcode' => [['value' => 1]],
      'revision_default' => [['value' => 1]],
      'revision_translation_affected' => [['value' => 1]],
      'field_text' => [
        0 => [
          'value' => 'Paragraph Bundle Two, nesting 1 in host n0 d1, d0, info@drupal.org and hello@drupal.org',
        ],
      ],
      'field_email' => [
        0 => [
          'value' => 'info@drupal.org',
        ],
        1 => [
          'value' => 'hello@drupal.org',
        ],
      ],
    ], $this->getImportantEntityProperties($para_field_any_paragraph_entities[0]));

    assert($para_field_any_paragraph_entities[1] instanceof ParagraphInterface);
    $para_important_properties = $this->getImportantEntityProperties($para_field_any_paragraph_entities[1]);
    // Don't care about paragraph field content and referenced IDs: we test the
    // values of the referenced entities.
    unset($para_important_properties['field_any_paragraph']);
    $this->assertEquals([
      'langcode' => [['value' => 'en']],
      'type' => [['target_id' => 'nested_host_2']],
      'status' => [['value' => 1]],
      'parent_field_name' => [['value' => 'field_any_paragraph']],
      'default_langcode' => [['value' => 1]],
      'revision_default' => [['value' => 1]],
      'revision_translation_affected' => [['value' => 1]],
      'field_text' => [
        0 => [
          'value' => 'Another host, nesting 1, d1',
        ],
      ],
    ], $para_important_properties);

    // Now check the nested paragraph entity referenced by the nested host
    // paragraph above (this is the "second" nesting level).
    $paragraph_nesting_level_2 = $this->getReferencedEntities($para_field_any_paragraph_entities[1], 'field_any_paragraph', 1)[0];
    assert($paragraph_nesting_level_2 instanceof ParagraphInterface);
    $this->assertEquals([
      'langcode' => [['value' => 'en']],
      'type' => [['target_id' => 'paragraph_bundle_one']],
      'status' => [['value' => 1]],
      'parent_field_name' => [['value' => 'field_any_paragraph']],
      'default_langcode' => [['value' => 1]],
      'revision_default' => [['value' => 1]],
      'revision_translation_affected' => [['value' => 1]],
      'field_text' => [
        0 => [
          'value' => 'Paragraph Bundle One, nesting 2 in host_2 n1 d1, d0, no Text list value',
        ],
      ],
      'field_text_list' => [],
    ], $this->getImportantEntityProperties($paragraph_nesting_level_2));
  }

  /**
   * Assertions of node 13.
   *
   * Expected structure:
   * @code
   * [Node 13]
   *  ├─[field_nested_fc_outer (empty)]
   *  │
   *  └─[field_nested_fc_outer]
   *     └─[NFCIO N0 D0]
   *        ├─[field_nested_fc_inner]
   *        |  ├─[NFCI N1 D0]
   *        |  └─[NFCI N1 D1]
   *        │
   *        └─[field_nested_fc_inner_outer]
   *           ├─[NFCIO N1 D0]
   *           |  └─[field_nested_fc_inner]
   *           |     ├─[NFCI N2 D0]
   *           |     ├─[NFCI N2 D1]
   *           |     └─[NFCI N2 D2]
   *           |
   *           └─[NFCIO N1 D0]
   *              └─[field_nested_fc_inner]
   *                 └─[NFCI N2 D0]
   * @endcode
   */
  protected function assertNode13Paragraphs() {
    $node = $this->container->get('entity_type.manager')->getStorage('node')->load(13);
    assert($node instanceof NodeInterface);
    $node_important_properties = $this->getImportantEntityProperties($node);
    // Don't care about paragraph field content and referenced IDs: we test the
    // values of the referenced entities.
    unset($node_important_properties['field_nested_fc_outer_2']);

    $this->assertEquals([
      'nid' => [['value' => 13]],
      'vid' => [['value' => 14]],
      'langcode' => [['value' => 'en']],
      'default_langcode' => [['value' => 1]],
      'type' => [['target_id' => 'content_with_coll']],
      'status' => [['value' => 1]],
      'uid' => [['target_id' => 1]],
      'title' => [['value' => 'Nested Field Collections']],
      'created' => [['value' => 1599759423]],
      'changed' => [['value' => 1599759423]],
      'promote' => [['value' => 0]],
      'sticky' => [['value' => 0]],
      'field_nested_fc_outer' => [],
    ], $node_important_properties);

    // There should be 1 item inside the "field_nested_fc_outer_2" field.
    $nested_fc_outer_2_entities = $this->getReferencedEntities($node, 'field_nested_fc_outer_2', 1);

    // Check the paragraph entity referenced by the node's
    // "field_nested_fc_outer" field.
    // @code
    // [Node 13]
    //  └─[field_nested_fc_outer_2]
    //     └─[NFCO2 N0 D0] «
    // @endcode
    assert($nested_fc_outer_2_entities[0] instanceof ParagraphInterface);
    $para_important_properties = $this->getImportantEntityProperties($nested_fc_outer_2_entities[0]);
    unset($para_important_properties['field_nested_fc_inner_outer']);
    unset($para_important_properties['field_nested_fc_inner']);
    $this->assertEquals([
      'langcode' => [['value' => 'en']],
      'type' => [['target_id' => 'nested_fc_outer_2']],
      'status' => [['value' => 1]],
      'parent_field_name' => [['value' => 'field_nested_fc_outer_2']],
      'default_langcode' => [['value' => 1]],
      'revision_default' => [['value' => 1]],
      'revision_translation_affected' => [['value' => 1]],
      'field_text' => [
        0 => [
          'value' => 'NFCO2 text – N0 D0; parent is node 13; 2 NFCI and 2 NFCIO inside',
        ],
      ],
    ], $para_important_properties);

    // There should be 2 items inside the "field_nested_fc_inner" field.
    $nested_fc_inner_entities = $this->getReferencedEntities($nested_fc_outer_2_entities[0], 'field_nested_fc_inner', 2);

    // Check the first paragraph entity referenced by the parent paragraph's
    // "field_nested_fc_inner" field.
    // @code
    // [Node 13]
    //  └─[field_nested_fc_outer]
    //     └─[NFCIO N0 D0]
    //        └─[field_nested_fc_inner]
    //           ├─[NFCI N1 D0] «
    //           └─[NFCI N1 D1]
    // @endcode
    assert($nested_fc_inner_entities[0] instanceof ParagraphInterface);
    $this->assertEquals([
      'langcode' => [['value' => 'en']],
      'type' => [['target_id' => 'nested_fc_inner']],
      'status' => [['value' => 1]],
      'parent_field_name' => [['value' => 'field_nested_fc_inner']],
      'default_langcode' => [['value' => 1]],
      'revision_default' => [['value' => 1]],
      'revision_translation_affected' => [['value' => 1]],
      'field_text' => [
        0 => [
          'value' => 'NFCI text – N1 D0; parent is NFCO2 N0 D0 (in node 13)',
        ],
      ],
    ], $this->getImportantEntityProperties($nested_fc_inner_entities[0]));

    // Check the last paragraph entity referenced by the parent paragraph's
    // "field_nested_fc_inner" field.
    // @code
    // [Node 13]
    //  └─[field_nested_fc_outer]
    //     └─[NFCIO N0 D0]
    //        └─[field_nested_fc_inner]
    //           ├─[NFCI N1 D0]
    //           └─[NFCI N1 D1] «
    // @endcode
    assert($nested_fc_inner_entities[1] instanceof ParagraphInterface);
    $this->assertEquals([
      'langcode' => [['value' => 'en']],
      'type' => [['target_id' => 'nested_fc_inner']],
      'status' => [['value' => 1]],
      'parent_field_name' => [['value' => 'field_nested_fc_inner']],
      'default_langcode' => [['value' => 1]],
      'revision_default' => [['value' => 1]],
      'revision_translation_affected' => [['value' => 1]],
      'field_text' => [
        0 => [
          'value' => 'NFCI text – N1 D1; parent is NFCO2 N0 D0 (in node 13)',
        ],
      ],
    ], $this->getImportantEntityProperties($nested_fc_inner_entities[1]));

    // There should be 2 item inside the "field_nested_fc_inner_outer" field.
    $nested_fc_inner_outer_entities = $this->getReferencedEntities($nested_fc_outer_2_entities[0], 'field_nested_fc_inner_outer', 2);

    // Check the first paragraph entity referenced by the parent paragraph's
    // "field_nested_fc_inner_outer" field.
    // @code
    // [Node 13]
    //  └─[field_nested_fc_outer]
    //     └─[NFCIO N0 D0]
    //        ├─[field_nested_fc_inner]
    //        └─[field_nested_fc_inner_outer]
    //           ├─[NFCIO N1 D0] «
    //           └─[NFCIO N1 D1]
    // @endcode
    assert($nested_fc_inner_outer_entities[0] instanceof ParagraphInterface);
    $para_important_properties = $this->getImportantEntityProperties($nested_fc_inner_outer_entities[0]);
    unset($para_important_properties['field_nested_fc_inner']);
    $this->assertEquals([
      'langcode' => [['value' => 'en']],
      'type' => [['target_id' => 'nested_fc_inner_outer']],
      'status' => [['value' => 1]],
      'parent_field_name' => [['value' => 'field_nested_fc_inner_outer']],
      'default_langcode' => [['value' => 1]],
      'revision_default' => [['value' => 1]],
      'revision_translation_affected' => [['value' => 1]],
      'field_text' => [
        0 => [
          'value' => 'NFCIO text – N1 D0; parent is NFCO2 N0 D0 (in node 13); 3 NFCI inside',
        ],
      ],
    ], $para_important_properties);

    // There should be 3 item inside the "field_nested_fc_inner" field.
    $nested_fc_inner_entities = $this->getReferencedEntities($nested_fc_inner_outer_entities[0], 'field_nested_fc_inner', 3);

    // Check the first paragraph entity referenced by the parent paragraph's
    // "field_nested_fc_inner" field.
    // @code
    // [Node 13]
    //  └─[field_nested_fc_outer]
    //     └─[NFCIO N0 D0]
    //        ├─[field_nested_fc_inner]
    //        └─[field_nested_fc_inner_outer]
    //           ├─[NFCIO N1 D0]
    //           |  └─[field_nested_fc_inner]
    //           |     ├─[NFCI N2 D0] «
    //           |     ├─[NFCI N2 D1]
    //           |     └─[NFCI N2 D2]
    //           └─[NFCIO N1 D1]
    // @endcode
    assert($nested_fc_inner_entities[0] instanceof ParagraphInterface);
    $this->assertEquals([
      'langcode' => [['value' => 'en']],
      'type' => [['target_id' => 'nested_fc_inner']],
      'status' => [['value' => 1]],
      'parent_field_name' => [['value' => 'field_nested_fc_inner']],
      'default_langcode' => [['value' => 1]],
      'revision_default' => [['value' => 1]],
      'revision_translation_affected' => [['value' => 1]],
      'field_text' => [
        0 => [
          'value' => 'NFCI text – N2 D0; parent is NFCIO N1 D0 (in NFCO2 N0 D0 / node 13)',
        ],
      ],
    ], $this->getImportantEntityProperties($nested_fc_inner_entities[0]));

    // Check the second paragraph entity referenced by the parent paragraph's
    // "field_nested_fc_inner" field.
    // @code
    // [Node 13]
    //  └─[field_nested_fc_outer]
    //     └─[NFCIO N0 D0]
    //        ├─[field_nested_fc_inner]
    //        └─[field_nested_fc_inner_outer]
    //           ├─[NFCIO N1 D0]
    //           |  └─[field_nested_fc_inner]
    //           |     ├─[NFCI N2 D0]
    //           |     ├─[NFCI N2 D1] «
    //           |     └─[NFCI N2 D2]
    //           └─[NFCIO N1 D1]
    // @endcode
    assert($nested_fc_inner_entities[1] instanceof ParagraphInterface);
    $this->assertEquals([
      'langcode' => [['value' => 'en']],
      'type' => [['target_id' => 'nested_fc_inner']],
      'status' => [['value' => 1]],
      'parent_field_name' => [['value' => 'field_nested_fc_inner']],
      'default_langcode' => [['value' => 1]],
      'revision_default' => [['value' => 1]],
      'revision_translation_affected' => [['value' => 1]],
      'field_text' => [
        0 => [
          'value' => 'NFCI text – N2 D1; parent is NFCIO N1 D0 (in NFCO2 N0 D0 / node 13)',
        ],
      ],
    ], $this->getImportantEntityProperties($nested_fc_inner_entities[1]));

    // Check the third paragraph entity referenced by the parent paragraph's
    // "field_nested_fc_inner" field.
    // @code
    // [Node 13]
    //  └─[field_nested_fc_outer]
    //     └─[NFCIO N0 D0]
    //        ├─[field_nested_fc_inner]
    //        └─[field_nested_fc_inner_outer]
    //           ├─[NFCIO N1 D0]
    //           |  └─[field_nested_fc_inner]
    //           |     ├─[NFCI N2 D0]
    //           |     ├─[NFCI N2 D1]
    //           |     └─[NFCI N2 D2] «
    //           └─[NFCIO N1 D1]
    // @endcode
    assert($nested_fc_inner_entities[2] instanceof ParagraphInterface);
    $this->assertEquals([
      'langcode' => [['value' => 'en']],
      'type' => [['target_id' => 'nested_fc_inner']],
      'status' => [['value' => 1]],
      'parent_field_name' => [['value' => 'field_nested_fc_inner']],
      'default_langcode' => [['value' => 1]],
      'revision_default' => [['value' => 1]],
      'revision_translation_affected' => [['value' => 1]],
      'field_text' => [
        0 => [
          'value' => 'NFCI text – N2 D2; parent is NFCIO N1 D0 (in NFCO2 N0 D0 / node 13)',
        ],
      ],
    ], $this->getImportantEntityProperties($nested_fc_inner_entities[2]));

    // Check the last paragraph entity referenced by the parent paragraph's
    // "field_nested_fc_inner_outer" field.
    // @code
    // [Node 13]
    //  └─[field_nested_fc_outer]
    //     └─[NFCIO N0 D0]
    //        ├─[field_nested_fc_inner]
    //        └─[field_nested_fc_inner_outer]
    //           ├─[NFCIO N1 D0]
    //           └─[NFCIO N1 D1] «
    // @endcode
    assert($nested_fc_inner_outer_entities[1] instanceof ParagraphInterface);
    $para_important_properties = $this->getImportantEntityProperties($nested_fc_inner_outer_entities[1]);
    unset($para_important_properties['field_nested_fc_inner']);
    $this->assertEquals([
      'langcode' => [['value' => 'en']],
      'type' => [['target_id' => 'nested_fc_inner_outer']],
      'status' => [['value' => 1]],
      'parent_field_name' => [['value' => 'field_nested_fc_inner_outer']],
      'default_langcode' => [['value' => 1]],
      'revision_default' => [['value' => 1]],
      'revision_translation_affected' => [['value' => 1]],
      'field_text' => [
        0 => [
          'value' => 'NFCIO text – N1 D1; parent is NFCO2 N0 D0 (in node 13); 1 NFCI inside',
        ],
      ],
    ], $para_important_properties);

    // There should be 1 item inside the "field_nested_fc_inner" field.
    $nested_fc_inner_entities = $this->getReferencedEntities($nested_fc_inner_outer_entities[1], 'field_nested_fc_inner', 1);

    // Check the single paragraph entity referenced by the parent paragraph's
    // "field_nested_fc_inner" field.
    // @code
    // [Node 13]
    //  └─[field_nested_fc_outer]
    //     └─[NFCIO N0 D0]
    //        ├─[field_nested_fc_inner]
    //        └─[field_nested_fc_inner_outer]
    //           ├─[NFCIO N1 D0]
    //           └─[NFCIO N1 D1]
    //              └─[field_nested_fc_inner]
    //                 └─[NFCI N2 D0] «
    // @endcode
    assert($nested_fc_inner_entities[0] instanceof ParagraphInterface);
    $this->assertEquals([
      'langcode' => [['value' => 'en']],
      'type' => [['target_id' => 'nested_fc_inner']],
      'status' => [['value' => 1]],
      'parent_field_name' => [['value' => 'field_nested_fc_inner']],
      'default_langcode' => [['value' => 1]],
      'revision_default' => [['value' => 1]],
      'revision_translation_affected' => [['value' => 1]],
      'field_text' => [
        0 => [
          'value' => 'NFCI text – N2 D0; parent is NFCIO N1 D1 (in NFCO2 N0 D0 / node 13)',
        ],
      ],
    ], $this->getImportantEntityProperties($nested_fc_inner_entities[0]));
  }

  /**
   * Assertions of node 14.
   *
   * Expected structure:
   * @code
   * [Node 14]
   *  ├─[field_nested_fc_outer_2 (empty)]
   *  │
   *  └─[field_nested_fc_outer]
   *     └─[NFCO N0 D0]
   *     │  └─[field_nested_fc_inner]
   *     │     ├─[NFCI N1 D0]
   *     │     ├─[NFCI N1 D1]
   *     │     └─[NFCI N1 D2]
   *     │
   *     └─[NFCO N0 D1]
   *        └─[field_nested_fc_inner]
   *           └─[NFCI N1 D0]
   * @endcode
   */
  protected function assertNode14Paragraphs() {
    $node = $this->container->get('entity_type.manager')->getStorage('node')->load(14);
    assert($node instanceof NodeInterface);
    $node_important_properties = $this->getImportantEntityProperties($node);
    unset($node_important_properties['field_nested_fc_outer']);

    $this->assertEquals([
      'nid' => [['value' => 14]],
      'vid' => [['value' => 15]],
      'langcode' => [['value' => 'en']],
      'default_langcode' => [['value' => 1]],
      'type' => [['target_id' => 'content_with_coll']],
      'status' => [['value' => 1]],
      'uid' => [['target_id' => 3]],
      'title' => [['value' => 'Nested Field Collections 2']],
      'created' => [['value' => 1599811396]],
      'changed' => [['value' => 1599811396]],
      'promote' => [['value' => 0]],
      'sticky' => [['value' => 0]],
      'field_nested_fc_outer_2' => [],
    ], $node_important_properties);

    // There should be 2 item inside the "field_nested_fc_outer" field.
    $nested_fc_outer_entities = $this->getReferencedEntities($node, 'field_nested_fc_outer', 2);

    // Check the first paragraph entity referenced by the node's
    // "field_nested_fc_outer" field.
    // @code
    // [Node 14]
    //  └─[field_nested_fc_outer]
    //     └─[NFCO N0 D0] «
    //     └─[NFCO N0 D1]
    // @endcode
    assert($nested_fc_outer_entities[0] instanceof ParagraphInterface);
    $para_important_properties = $this->getImportantEntityProperties($nested_fc_outer_entities[0]);
    unset($para_important_properties['field_nested_fc_inner']);
    $this->assertEquals([
      'langcode' => [['value' => 'en']],
      'type' => [['target_id' => 'nested_fc_outer']],
      'status' => [['value' => 1]],
      'parent_field_name' => [['value' => 'field_nested_fc_outer']],
      'default_langcode' => [['value' => 1]],
      'revision_default' => [['value' => 1]],
      'revision_translation_affected' => [['value' => 1]],
    ], $para_important_properties);

    // There should be 3 nested item inside the "field_nested_fc_inner" field.
    $nested_fc_inner_entities = $this->getReferencedEntities($nested_fc_outer_entities[0], 'field_nested_fc_inner', 3);

    // Check the first paragraph entity referenced by the parent paragraph's
    // "field_nested_fc_inner" field.
    // @code
    // [Node 14]
    //  └─[field_nested_fc_outer]
    //     └─[NFCO N0 D0]
    //     │  └─[field_nested_fc_inner]
    //     │     ├─[NFCI N1 D0] «
    //     │     ├─[NFCI N1 D1]
    //     │     └─[NFCI N1 D2]
    //     │
    //     └─[NFCO N0 D1]
    // @endcode
    assert($nested_fc_inner_entities[0] instanceof ParagraphInterface);
    $this->assertEquals([
      'langcode' => [['value' => 'en']],
      'type' => [['target_id' => 'nested_fc_inner']],
      'status' => [['value' => 1]],
      'parent_field_name' => [['value' => 'field_nested_fc_inner']],
      'default_langcode' => [['value' => 1]],
      'revision_default' => [['value' => 1]],
      'revision_translation_affected' => [['value' => 1]],
      'field_text' => [
        0 => [
          'value' => 'NFCI text – N1 D0; parent is NFCO N0 D0 (in node 14)',
        ],
      ],
    ], $this->getImportantEntityProperties($nested_fc_inner_entities[0]));

    // Check the second paragraph entity referenced by the parent paragraph's
    // "field_nested_fc_inner" field.
    // @code
    // [Node 14]
    //  └─[field_nested_fc_outer]
    //     └─[NFCO N0 D0]
    //     │  └─[field_nested_fc_inner]
    //     │     ├─[NFCI N1 D0]
    //     │     ├─[NFCI N1 D1] «
    //     │     └─[NFCI N1 D2]
    //     │
    //     └─[NFCO N0 D1]
    // @endcode
    assert($nested_fc_inner_entities[1] instanceof ParagraphInterface);
    $this->assertEquals([
      'langcode' => [['value' => 'en']],
      'type' => [['target_id' => 'nested_fc_inner']],
      'status' => [['value' => 1]],
      'parent_field_name' => [['value' => 'field_nested_fc_inner']],
      'default_langcode' => [['value' => 1]],
      'revision_default' => [['value' => 1]],
      'revision_translation_affected' => [['value' => 1]],
      'field_text' => [
        0 => [
          'value' => 'NFCI text – N1 D1; parent is NFCO N0 D0 (in node 14)',
        ],
      ],
    ], $this->getImportantEntityProperties($nested_fc_inner_entities[1]));

    // Check the third paragraph entity referenced by the parent paragraph's
    // "field_nested_fc_inner" field.
    // @code
    // [Node 14]
    //  └─[field_nested_fc_outer]
    //     └─[NFCO N0 D0]
    //     │  └─[field_nested_fc_inner]
    //     │     ├─[NFCI N1 D0]
    //     │     ├─[NFCI N1 D1]
    //     │     └─[NFCI N1 D2] «
    //     │
    //     └─[NFCO N0 D1]
    // @endcode
    assert($nested_fc_inner_entities[2] instanceof ParagraphInterface);
    $this->assertEquals([
      'langcode' => [['value' => 'en']],
      'type' => [['target_id' => 'nested_fc_inner']],
      'status' => [['value' => 1]],
      'parent_field_name' => [['value' => 'field_nested_fc_inner']],
      'default_langcode' => [['value' => 1]],
      'revision_default' => [['value' => 1]],
      'revision_translation_affected' => [['value' => 1]],
      'field_text' => [
        0 => [
          'value' => 'NFCI text – N1 D2; parent is NFCO N0 D0 (in node 14)',
        ],
      ],
    ], $this->getImportantEntityProperties($nested_fc_inner_entities[2]));

    // Check the second paragraph entity referenced by the node's
    // "field_nested_fc_outer" field.
    // @code
    // [Node 14]
    //  └─[field_nested_fc_outer]
    //     └─[NFCO N0 D0]
    //     └─[NFCO N0 D1] «
    // @endcode
    assert($nested_fc_outer_entities[1] instanceof ParagraphInterface);
    $para_important_properties = $this->getImportantEntityProperties($nested_fc_outer_entities[1]);
    unset($para_important_properties['field_nested_fc_inner']);
    $this->assertEquals([
      'langcode' => [['value' => 'en']],
      'type' => [['target_id' => 'nested_fc_outer']],
      'status' => [['value' => 1]],
      'parent_field_name' => [['value' => 'field_nested_fc_outer']],
      'default_langcode' => [['value' => 1]],
      'revision_default' => [['value' => 1]],
      'revision_translation_affected' => [['value' => 1]],
    ], $para_important_properties);

    // There should be 1 nested item inside the "field_nested_fc_inner" field.
    $nested_fc_inner_entities = $this->getReferencedEntities($nested_fc_outer_entities[1], 'field_nested_fc_inner', 1);

    // Check the paragraph entity referenced by the parent paragraph's
    // "field_nested_fc_inner" field.
    // @code
    // [Node 14]
    //  └─[field_nested_fc_outer]
    //     └─[NFCO N0 D0]
    //     └─[NFCO N0 D1]
    //        └─[field_nested_fc_inner]
    //           └─[NFCI N1 D0] «
    // @endcode
    assert($nested_fc_inner_entities[0] instanceof ParagraphInterface);
    $this->assertEquals([
      'langcode' => [['value' => 'en']],
      'type' => [['target_id' => 'nested_fc_inner']],
      'status' => [['value' => 1]],
      'parent_field_name' => [['value' => 'field_nested_fc_inner']],
      'default_langcode' => [['value' => 1]],
      'revision_default' => [['value' => 1]],
      'revision_translation_affected' => [['value' => 1]],
      'field_text' => [
        0 => [
          'value' => 'NFCI text – N1 D0; parent is NFCO N0 D1 (in node 14)',
        ],
      ],
    ], $this->getImportantEntityProperties($nested_fc_inner_entities[0]));
  }

  /**
   * Get the referred entities.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The parent entity.
   * @param string $field_name
   *   The name of the entity revision reference field.
   * @param int $expected_count
   *   The expected number of the referenced entities.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   An array of entity objects keyed by field item deltas.
   */
  protected function getReferencedEntities(ContentEntityInterface $entity, $field_name, int $expected_count) {
    $entity_field = $entity->hasField($field_name) ?
      $entity->get($field_name) :
      NULL;
    assert($entity_field instanceof EntityReferenceRevisionsFieldItemList);
    $entity_field_entities = $entity_field->referencedEntities();
    $this->assertCount($expected_count, $entity_field_entities);

    return $entity_field_entities;
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
    $property_filter_preset_property = "{$entity_type_id}UnconcernedProperties";
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
