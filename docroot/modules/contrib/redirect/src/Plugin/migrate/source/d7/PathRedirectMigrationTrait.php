<?php

namespace Drupal\redirect\Plugin\migrate\source\d7;

use Drupal\Core\Database\Query\SelectInterface;

/**
 * Trait for derived path redirect migrations.
 */
trait PathRedirectMigrationTrait {

  /**
   * Placeholder for the "{entity_type_id}" parameter in the link templates.
   *
   * @var string
   */
  protected $entityTypeParameter = 'ETPARAM';

  /**
   * Placeholder link template params that are not "{entity_type_id}".
   *
   * @var string
   */
  protected $extraParameter = 'EXTRAPARAM';

  /**
   * Required entity type DB tables, keyed by the entity type ID.
   *
   * @var string[][]
   */
  protected $supportedEntityTypesTables = [
    'node' => ['node', 'node_type'],
    'taxonomy_term' => ['taxonomy_term_data', 'taxonomy_vocabulary'],
    'user' => [],
  ];

  /**
   * Prepared link templates of entity types.
   *
   * Link templates are keyed by the entity type ID, their trailing slash is
   * removed, every occurrence of the {entity_type_id} parameter is replaced by
   * $this->entityTypeParameter, and every other parameter is replaced with
   * $this->extraParameter.
   *
   * @var string[][]
   */
  protected $preparedLinkTemplates;

  /**
   * Adds entity type specific restrictions to a path redirect migration query.
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $query
   *   A Select Query object we add the restrictions to.
   * @param string $entity_type_id
   *   The ID of the entity type.
   */
  protected function addEntityTypeRestrictions(SelectInterface $query, string $entity_type_id) {
    $link_templates = $this->getPreparedLinkTemplates($entity_type_id);

    if (!empty($link_templates)) {
      $or_conditions = $query->orConditionGroup();

      foreach ($link_templates as $link_template) {
        $pattern = '/(' . $this->entityTypeParameter . '|' . $this->extraParameter . ')/';
        $template_like = preg_replace($pattern, '%', $query->escapeLike($link_template));
        $or_conditions->condition('p.redirect', $template_like, 'LIKE');
      }

      $query->condition($or_conditions);
    }
  }

  /**
   * Adds entity type specific exclude conditions to a redirect migration query.
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $query
   *   A Select Query object we add the restrictions to.
   * @param string[] $excluded_entity_type_ids
   *   An array of the IDs of the entity types that should be excluded.
   */
  protected function addExcludedEntityTypeRestrictions(SelectInterface $query, array $excluded_entity_type_ids) {
    foreach ($excluded_entity_type_ids as $entity_type_id) {
      if (empty($link_templates = $this->getPreparedLinkTemplates($entity_type_id))) {
        continue;
      }

      foreach ($link_templates as $link_template) {
        $pattern = '/(' . $this->entityTypeParameter . '|' . $this->extraParameter . ')/';
        $template_raw = preg_replace($pattern, '%', $query->escapeLike($link_template));
        $query->condition('p.redirect', $template_raw, 'NOT LIKE');
      }
    }
  }

  /**
   * Prepares a set of link templates.
   *
   * - Removes the trailing slash from the link template. This is useful for
   *   constructing query conditions that compare a value to the D7 redirect
   *   destination ("redirect" column), since the values in that column do not
   *   contain leading slash.
   * - Path parameters like {entity_type_id} are replaced by
   *   $this->entityTypeParameter.
   * - Every other parameter is replaced with $this->extraParameter.
   *
   * @param string $entity_type_id
   *   The ID of the entity type.
   *
   * @return string[]
   *   The prepared link templates.
   */
  private function getPreparedLinkTemplates(string $entity_type_id) {
    if (isset($this->preparedLinkTemplates[$entity_type_id])) {
      return $this->preparedLinkTemplates[$entity_type_id];
    }

    $definition = $this->entityTypeManager->getDefinition($entity_type_id, FALSE);
    $link_templates = $definition ? $definition->getLinkTemplates() : [];

    // Get link templates that use the entity type ID as parameter.
    $link_templates = array_filter($link_templates, function (string $template) use ($entity_type_id) {
      return mb_strpos($template, '{' . $entity_type_id . '}') !== FALSE;
    });

    array_walk($link_templates, function (&$template, $delta, $entity_type_id) {
      $template_raw = preg_replace('/(\{' . $entity_type_id . '\})/', $this->entityTypeParameter, ltrim($template, '/'));
      $template = preg_replace('/(\{[^}]+\})/', $this->extraParameter, $template_raw);
    }, $entity_type_id);

    $this->preparedLinkTemplates[$entity_type_id] = array_values($link_templates);
    return $this->preparedLinkTemplates[$entity_type_id];
  }

}
