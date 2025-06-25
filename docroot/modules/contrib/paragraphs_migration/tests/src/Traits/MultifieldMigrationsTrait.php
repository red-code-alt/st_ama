<?php

namespace Drupal\Tests\paragraphs_migration\Traits;

use Drupal\comment\CommentFieldItemList;
use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\TypedData\TranslatableInterface;
use Drupal\entity_reference_revisions\EntityReferenceRevisionsFieldItemList;
use Drupal\field\FieldConfigStorage;
use Drupal\field\FieldStorageConfigStorage;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\Entity\Term;

/**
 * Common assertions for multifield paragraphs migrations.
 */
trait MultifieldMigrationsTrait {

  /**
   * Tests the paragraphs type migrated from "field_multifield_w_text_fields".
   */
  protected function assertMultifieldTextType() {
    $storage = \Drupal::entityTypeManager()->getStorage('paragraphs_type');
    assert($storage instanceof ConfigEntityStorageInterface);

    $type = $storage->loadOverrideFree('multifield_w_text_fields');

    $this->assertEquals([
      'langcode' => 'en',
      'status' => TRUE,
      'dependencies' => [],
      'id' => 'multifield_w_text_fields',
      'label' => 'Multifield with text fields',
      'icon_uuid' => NULL,
      'icon_default' => NULL,
      'description' => '',
      'behavior_plugins' => [],
    ], $this->getEntityValues($type));
  }

  /**
   * Tests the paragraphs type migrated from "field_multifield_complex_fields".
   */
  protected function assertMultifieldComplexType() {
    $storage = \Drupal::entityTypeManager()->getStorage('paragraphs_type');
    assert($storage instanceof ConfigEntityStorageInterface);

    $type = $storage->loadOverrideFree('multifield_complex_fields');

    $this->assertEquals([
      'langcode' => 'en',
      'status' => TRUE,
      'dependencies' => [],
      'id' => 'multifield_complex_fields',
      'label' => 'Multifield with complex fields',
      'icon_uuid' => NULL,
      'icon_default' => NULL,
      'description' => '',
      'behavior_plugins' => [],
    ], $this->getEntityValues($type));
  }

  /**
   * Tests field storage migrated for "field_multifield_w_text_fields" in nodes.
   */
  protected function assertNodeMultifieldTextFieldStorage() {
    $storage = \Drupal::entityTypeManager()->getStorage('field_storage_config');
    assert($storage instanceof FieldStorageConfigStorage);

    $field_storage = $storage->loadOverrideFree('node.field_multifield_w_text_fields');

    $this->assertEquals([
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
      'status' => TRUE,
      'dependencies' => [
        'module' => [
          'entity_reference_revisions',
          'node',
          'paragraphs',
        ],
      ],
      'id' => 'node.field_multifield_w_text_fields',
      'field_name' => 'field_multifield_w_text_fields',
      'entity_type' => 'node',
      'type' => 'entity_reference_revisions',
      'settings' => [
        'target_type' => 'paragraph',
      ],
      'module' => 'entity_reference_revisions',
      'locked' => FALSE,
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
      'translatable' => TRUE,
      'indexes' => [],
      'custom_storage' => FALSE,
    ], $this->getEntityValues($field_storage));
  }

  /**
   * Tests field storage migrated for "field_multifield_complex_fields" (nodes).
   */
  protected function assertNodeMultifieldComplexFieldStorage() {
    $storage = \Drupal::entityTypeManager()->getStorage('field_storage_config');
    assert($storage instanceof FieldStorageConfigStorage);

    $field_storage = $storage->loadOverrideFree('node.field_multifield_complex_fields');

    $this->assertEquals([
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
      'status' => TRUE,
      'dependencies' => [
        'module' => [
          'entity_reference_revisions',
          'node',
          'paragraphs',
        ],
      ],
      'id' => 'node.field_multifield_complex_fields',
      'field_name' => 'field_multifield_complex_fields',
      'entity_type' => 'node',
      'type' => 'entity_reference_revisions',
      'settings' => [
        'target_type' => 'paragraph',
      ],
      'module' => 'entity_reference_revisions',
      'locked' => FALSE,
      'cardinality' => 1,
      'translatable' => TRUE,
      'indexes' => [],
      'custom_storage' => FALSE,
    ], $this->getEntityValues($field_storage));
  }

  /**
   * Tests the field storage migrated from the taxonomy multifield field.
   */
  protected function assertTermMultifieldFieldStorage() {
    $storage = \Drupal::entityTypeManager()->getStorage('field_storage_config');
    assert($storage instanceof FieldStorageConfigStorage);

    $field_storage = $storage->loadOverrideFree('taxonomy_term.field_multifield_w_text_fields');

    $this->assertEquals([
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
      'status' => TRUE,
      'dependencies' => [
        'module' => [
          'entity_reference_revisions',
          'paragraphs',
          'taxonomy',
        ],
      ],
      'id' => 'taxonomy_term.field_multifield_w_text_fields',
      'field_name' => 'field_multifield_w_text_fields',
      'entity_type' => 'taxonomy_term',
      'type' => 'entity_reference_revisions',
      'settings' => [
        'target_type' => 'paragraph',
      ],
      'module' => 'entity_reference_revisions',
      'locked' => FALSE,
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
      'translatable' => TRUE,
      'indexes' => [],
      'custom_storage' => FALSE,
    ], $this->getEntityValues($field_storage));
  }

  /**
   * Tests the field migrated for "field_multifield_w_text_fields" in the node.
   */
  protected function assertNodeMultifieldTextFieldInstance() {
    $storage = \Drupal::entityTypeManager()->getStorage('field_config');
    assert($storage instanceof FieldConfigStorage);

    $field_instance = $storage->loadOverrideFree('node.type_with_multifields.field_multifield_w_text_fields');

    $this->assertEquals([
      'langcode' => 'en',
      'status' => TRUE,
      'dependencies' => [
        'config' => [
          'field.storage.node.field_multifield_w_text_fields',
          'node.type.type_with_multifields',
          'paragraphs.paragraphs_type.multifield_w_text_fields',
        ],
        'module' => [
          'entity_reference_revisions',
        ],
      ],
      'id' => 'node.type_with_multifields.field_multifield_w_text_fields',
      'field_name' => 'field_multifield_w_text_fields',
      'entity_type' => 'node',
      'bundle' => 'type_with_multifields',
      'label' => 'Multifield with text fields',
      'description' => '',
      'required' => FALSE,
      'translatable' => FALSE,
      'default_value' => [],
      'default_value_callback' => '',
      'settings' => [
        'handler' => 'default:paragraph',
        'handler_settings' => [
          'negate' => 0,
          'target_bundles' => [
            'multifield_w_text_fields' => 'multifield_w_text_fields',
          ],
        ],
      ],
      'field_type' => 'entity_reference_revisions',
    ], $this->getEntityValues($field_instance));
  }

  /**
   * Tests the field migrated for "field_multifield_complex_fields" in the node.
   */
  protected function assertNodeMultifieldComplexFieldInstance() {
    $storage = \Drupal::entityTypeManager()->getStorage('field_config');
    assert($storage instanceof FieldConfigStorage);

    $field_instance = $storage->loadOverrideFree('node.type_with_multifields.field_multifield_complex_fields');

    $this->assertEquals([
      'langcode' => 'en',
      'status' => TRUE,
      'dependencies' => [
        'config' => [
          'field.storage.node.field_multifield_complex_fields',
          'node.type.type_with_multifields',
          'paragraphs.paragraphs_type.multifield_complex_fields',
        ],
        'module' => [
          'entity_reference_revisions',
        ],
      ],
      'id' => 'node.type_with_multifields.field_multifield_complex_fields',
      'field_name' => 'field_multifield_complex_fields',
      'entity_type' => 'node',
      'bundle' => 'type_with_multifields',
      'label' => 'Multifield with complex fields',
      'description' => '',
      'required' => FALSE,
      'translatable' => FALSE,
      'default_value' => [],
      'default_value_callback' => '',
      'settings' => [
        'handler' => 'default:paragraph',
        'handler_settings' => [
          'negate' => 0,
          'target_bundles' => [
            'multifield_complex_fields' => 'multifield_complex_fields',
          ],
        ],
      ],
      'field_type' => 'entity_reference_revisions',
    ], $this->getEntityValues($field_instance));
  }

  /**
   * Tests the field instance migrated from the taxonomy multifield.
   */
  protected function assertTermMultifieldFieldInstance() {
    $storage = \Drupal::entityTypeManager()->getStorage('field_config');
    assert($storage instanceof FieldConfigStorage);

    $field_instance = $storage->loadOverrideFree('taxonomy_term.vocabulary_with_multifields.field_multifield_w_text_fields');

    $this->assertEquals([
      'langcode' => 'en',
      'status' => TRUE,
      'dependencies' => [
        'config' => [
          'field.storage.taxonomy_term.field_multifield_w_text_fields',
          'paragraphs.paragraphs_type.multifield_w_text_fields',
          'taxonomy.vocabulary.vocabulary_with_multifields',
        ],
        'module' => [
          'entity_reference_revisions',
        ],
      ],
      'id' => 'taxonomy_term.vocabulary_with_multifields.field_multifield_w_text_fields',
      'field_name' => 'field_multifield_w_text_fields',
      'entity_type' => 'taxonomy_term',
      'bundle' => 'vocabulary_with_multifields',
      'label' => 'Multifield with text fields',
      'description' => '',
      'required' => FALSE,
      'translatable' => TRUE,
      'default_value' => [],
      'default_value_callback' => '',
      'settings' => [
        'handler' => 'default:paragraph',
        'handler_settings' => [
          'negate' => 0,
          'target_bundles' => [
            'multifield_w_text_fields' => 'multifield_w_text_fields',
          ],
        ],
      ],
      'field_type' => 'entity_reference_revisions',
    ], $this->getEntityValues($field_instance));
  }

  /**
   * Tests the values of the term migrated form D7 with tid 126.
   */
  protected function assertTerm126() {
    $storage = $this->container->get('entity_type.manager')->getStorage('taxonomy_term');
    assert($storage instanceof EntityStorageInterface);
    $term = $storage->load(126);
    $this->assertInstanceOf(Term::class, $term);

    // Check the default (IS) translation.
    $this->assertEquals([
      'tid' => [['value' => '126']],
      'langcode' => [['value' => 'is']],
      'vid' => [['target_id' => 'vocabulary_with_multifields']],
      'revision_user' => [],
      'revision_log_message' => [],
      'status' => [['value' => '1']],
      'name' => [['value' => 'Multifield term [IS - default]']],
      'description' => [
        [
          'value' => 'Multifield term description [IS - default]',
          'format' => 'filtered_html',
        ],
      ],
      'weight' => [['value' => '0']],
      'parent' => [['target_id' => '0']],
      'default_langcode' => [['value' => '1']],
      'revision_default' => [['value' => '1']],
      'revision_translation_affected' => [['value' => '1']],
      'content_translation_source' => [['value' => 'und']],
      'content_translation_outdated' => [['value' => '0']],
      'field_multifield_w_text_fields' => [
        0 => [
          'langcode' => [['value' => 'is']],
          'type' => [['target_id' => 'multifield_w_text_fields']],
          'status' => [['value' => '1']],
          'parent_id' => [['value' => '126']],
          'parent_type' => [['value' => 'taxonomy_term']],
          'parent_field_name' => [
            [
              'value' => 'field_multifield_w_text_fields',
            ],
          ],
          'behavior_settings' => [['value' => 'a:0:{}']],
          'default_langcode' => [['value' => '1']],
          'revision_default' => [['value' => '1']],
          'revision_translation_affected' => [['value' => '1']],
          'content_translation_source' => [['value' => 'und']],
          'content_translation_outdated' => [['value' => '0']],
          'field_text_plain' => [
            [
              'value' => 'Multifield term "text plain" copy [IS - default]',
            ],
          ],
          'field_text_sum_filtered' => [
            [
              'value' => "Multifield term \"text summary filtered\" summary [IS - default]\r\n<!--break-->\r\nMultifield term \"text summary filtered\" copy [IS - default]",
              'summary' => '',
              'format' => 'filtered_html',
            ],
          ],
        ],
      ],
    ], $this->getEntityValues($term));

    // Check the French translation.
    $this->assertEquals([
      'tid' => [['value' => '126']],
      'langcode' => [['value' => 'fr']],
      'vid' => [['target_id' => 'vocabulary_with_multifields']],
      'revision_user' => [],
      'revision_log_message' => [],
      'status' => [['value' => '1']],
      'name' => [['value' => 'Multifield term [FR]']],
      'description' => [
        [
          'value' => 'Multifield term description [FR]',
          'format' => 'filtered_html',
        ],
      ],
      'weight' => [['value' => '0']],
      'parent' => [['target_id' => '0']],
      'default_langcode' => [['value' => '0']],
      'revision_default' => [['value' => '1']],
      'revision_translation_affected' => [['value' => '1']],
      'content_translation_source' => [['value' => 'is']],
      'content_translation_outdated' => [['value' => '0']],
      'field_multifield_w_text_fields' => [
        0 => [
          'langcode' => [['value' => 'fr']],
          'type' => [['target_id' => 'multifield_w_text_fields']],
          'status' => [['value' => '1']],
          'parent_id' => [['value' => '126']],
          'parent_type' => [['value' => 'taxonomy_term']],
          'parent_field_name' => [
            [
              'value' => 'field_multifield_w_text_fields',
            ],
          ],
          'behavior_settings' => [['value' => 'a:0:{}']],
          'default_langcode' => [['value' => '0']],
          'revision_default' => [['value' => '1']],
          'revision_translation_affected' => [['value' => '1']],
          'content_translation_source' => [['value' => 'is']],
          'content_translation_outdated' => [['value' => '0']],
          'field_text_plain' => [
            [
              'value' => 'Multifield term "text plain" copy [FR]',
            ],
          ],
          'field_text_sum_filtered' => [
            [
              'value' => "Multifield term \"text summary filtered\" summary [FR]\r\n<!--break-->\r\nMultifield term \"text summary filtered\" copy [FR]",
              'summary' => '',
              'format' => 'filtered_html',
            ],
          ],
        ],
      ],
    ], $this->getEntityValues($term, 'fr'));
  }

  /**
   * Tests the values of the node migrated form D7 nid 112.
   */
  protected function assertNode112() {
    $storage = $this->container->get('entity_type.manager')->getStorage('node');
    assert($storage instanceof EntityStorageInterface);
    $node = $storage->loadRevision(119);
    $this->assertInstanceOf(Node::class, $node);

    // Check the first (obsolete) revision.
    $this->assertEquals([
      'nid' => [['value' => '112']],
      'vid' => [['value' => '119']],
      'langcode' => [['value' => 'en']],
      'type' => [['target_id' => 'type_with_multifields']],
      'revision_timestamp' => [['value' => '1622704732']],
      'revision_uid' => [['target_id' => '3']],
      'revision_log' => [['value' => 'Initial revision.']],
      'status' => [['value' => 1]],
      'uid' => [['target_id' => '3']],
      'title' => [['value' => 'Content with multifields [rev1]']],
      'created' => [['value' => '1622704732']],
      'changed' => [['value' => '1622704732']],
      'promote' => [['value' => 1]],
      'sticky' => [['value' => 0]],
      'default_langcode' => [['value' => 1]],
      'revision_default' => [['value' => 1]],
      'revision_translation_affected' => [['value' => 1]],
      'content_translation_source' => [],
      'content_translation_outdated' => [['value' => 0]],
      'body' => [],
      'field_multifield_w_text_fields' => [
        0 => [
          'langcode' => [['value' => 'en']],
          'type' => [['target_id' => 'multifield_w_text_fields']],
          'status' => [['value' => 1]],
          'parent_id' => [['value' => 112]],
          'parent_type' => [['value' => 'node']],
          'parent_field_name' => [
            ['value' => 'field_multifield_w_text_fields'],
          ],
          'behavior_settings' => [['value' => 'a:0:{}']],
          'default_langcode' => [['value' => 1]],
          'revision_default' => [['value' => 1]],
          'revision_translation_affected' => [['value' => 1]],
          'content_translation_source' => [['value' => 'und']],
          'content_translation_outdated' => [['value' => 0]],
          'field_text_plain' => [
            0 => [
              'value' => 'Content with multifields text plain copy [delta0] [rev1]',
            ],
          ],
          'field_text_sum_filtered' => [
            0 => [
              'value' => 'Content with multifields text summary copy [delta0] [rev1]',
              'summary' => '',
              'format' => 'filtered_html',
            ],
          ],
        ],
        1 => [
          'langcode' => [['value' => 'en']],
          'type' => [['target_id' => 'multifield_w_text_fields']],
          'status' => [['value' => 1]],
          'parent_id' => [['value' => 112]],
          'parent_type' => [['value' => 'node']],
          'parent_field_name' => [
            ['value' => 'field_multifield_w_text_fields'],
          ],
          'behavior_settings' => [['value' => 'a:0:{}']],
          'default_langcode' => [['value' => 1]],
          'revision_default' => [['value' => 1]],
          'revision_translation_affected' => [['value' => 1]],
          'content_translation_source' => [['value' => 'und']],
          'content_translation_outdated' => [['value' => 0]],
          'field_text_plain' => [
            0 => [
              'value' => 'Content with multifields text plain copy [delta1] [rev1]',
            ],
          ],
          'field_text_sum_filtered' => [
            0 => [
              'value' => 'Content with multifields text summary copy [delta1] [rev1]',
              'summary' => '',
              'format' => 'filtered_html',
            ],
          ],
        ],
      ],
      'field_multifield_complex_fields' => [
        0 => [
          'langcode' => [['value' => 'en']],
          'type' => [['target_id' => 'multifield_complex_fields']],
          'status' => [['value' => 1]],
          'parent_id' => [['value' => 112]],
          'parent_type' => [['value' => 'node']],
          'parent_field_name' => [
            ['value' => 'field_multifield_complex_fields'],
          ],
          'behavior_settings' => [['value' => 'a:0:{}']],
          'default_langcode' => [['value' => 1]],
          'revision_default' => [['value' => 1]],
          'revision_translation_affected' => [['value' => 1]],
          'content_translation_source' => [['value' => 'und']],
          'content_translation_outdated' => [['value' => 0]],
          'field_tags' => [
            0 => [
              'target_id' => '11',
            ],
          ],
          'field_date' => [
            0 => [
              'value' => '2021-06-03T07:15:00',
            ],
          ],
          'field_link' => [
            0 => [
              'uri' => 'https://www.drupal.org',
              'title' => 'Drupal org [rev1]',
              'options' => [
                'attributes' => [],
              ],
            ],
          ],
        ],
      ],
    ], $this->getEntityValues($node));
  }

  /**
   * Gets the values of the paragraphs referenced by the given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $host_entity
   *   The entity.
   * @param string|null $langcode
   *   The language code.
   *
   * @return array[]
   *   The values of the paragraphs referenced by the given entity.
   */
  protected function getParagraphFieldValues(EntityInterface $host_entity, $langcode = NULL) :array {
    if (!$host_entity instanceof ContentEntityInterface) {
      return [];
    }
    return array_reduce($host_entity->getFields(FALSE), function (array $carry, FieldItemListInterface $field) use ($langcode) {
      if ($field instanceof EntityReferenceRevisionsFieldItemList) {
        $carry[$field->getName()] = array_reduce($field->referencedEntities(), function (array $value, EntityInterface $paragraph) use ($langcode) {
          $paragraph = $paragraph->hasTranslation($langcode)
            ? $paragraph->getTranslation($langcode)
            : $paragraph->getUntranslated();
          $value[] = array_diff_key($paragraph->toArray(), [
            'id' => TRUE,
            'revision_id' => TRUE,
            'uuid' => TRUE,
            'created' => TRUE,
            'content_translation_changed' => TRUE,
            'path' => TRUE,
          ]);
          return $value;
        }, []);
      }
      return $carry;
    }, []);
  }

  /**
   * Returns the entity properties as an array.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param string|null $langcode
   *   The language code.
   *
   * @return array[]
   *   The entity properties as an array.
   */
  protected function getEntityValues(EntityInterface $entity, $langcode = NULL) :array {
    if ($entity instanceof TranslatableInterface) {
      $entity = $langcode
        ? $entity->getTranslation($langcode)
        : $entity->getUntranslated();
    }

    $keys_to_ignore = [
      'uuid',
      'path',
      'persist_with_no_fields',
    ];

    // In D7, only nodes are revisionable.
    if (!$entity instanceof NodeInterface) {
      $keys_to_ignore = array_merge(
        $keys_to_ignore,
        [
          'revision_id',
          'revision_created',
          'changed',
          'content_translation_uid',
          'content_translation_created',
          'content_translation_changed',
        ]
      );
    }

    $base_values = array_diff_key(
      $entity->toArray(),
      array_combine($keys_to_ignore, $keys_to_ignore)
    );
    // Exclude comment fields.
    if ($entity instanceof ContentEntityInterface) {
      $base_values = array_filter($base_values, function ($property_name) use ($entity) {
        return strpos($property_name, 'comment_') !== 0 || !($entity->get($property_name) instanceof CommentFieldItemList);
      }, ARRAY_FILTER_USE_KEY);
    }

    return array_merge(
      $base_values,
      $this->getParagraphFieldValues($entity, $langcode)
    );
  }

  /**
   * Returns the actual number of paragraph entities.
   */
  protected function getActualParagraphsCount() :int {
    $paragraphs_data = \Drupal::database()
      ->select('paragraphs_item', 'p')
      ->fields('p')
      ->orderBy('p.id')
      ->orderBy('p.revision_id')
      ->execute()
      ->fetchAll(\PDO::FETCH_ASSOC);
    return count($paragraphs_data);
  }

  /**
   * Returns the actual number of paragraph revisions.
   */
  protected function getActualParagraphRevisionsCount() :int {
    $db = \Drupal::database();
    if (!$db->schema()->tableExists('paragraphs_item_revision')) {
      return $this->getActualParagraphsCount();
    }
    $paragraph_revisions_data = \Drupal::database()
      ->select('paragraphs_item_revision', 'pr')
      ->fields('pr')
      ->orderBy('pr.id')
      ->orderBy('pr.revision_id')
      ->execute()
      ->fetchAll(\PDO::FETCH_ASSOC);
    return count($paragraph_revisions_data);
  }

  /**
   * Returns the actual number of paragraph entities.
   */
  protected function getActualParagraphRevisionTranslationsCount() :int {
    $db = \Drupal::database();
    if (!$db->schema()->tableExists('paragraphs_item_revision_field_data')) {
      return $this->getActualParagraphRevisionsCount();
    }
    $paragraphs_revision_translations_data = $db
      ->select('paragraphs_item_revision_field_data', 'prt')
      ->fields('prt')
      ->orderBy('prt.id')
      ->orderBy('prt.revision_id')
      ->execute()
      ->fetchAll(\PDO::FETCH_ASSOC);
    return count($paragraphs_revision_translations_data);
  }

}
