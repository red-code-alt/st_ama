<?php

namespace Drupal\redirect\Plugin\migrate\source\d7;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal 7 path redirect source from database.
 *
 * @MigrateSource(
 *   id = "d7_path_redirect",
 *   source_module = "redirect"
 * )
 */
class PathRedirect extends DrupalSqlBase {

  use PathRedirectMigrationTrait;

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Select path redirects.
    $query = $this->select('redirect', 'p')->fields('p');

    $entity_type_id = $this->configuration['entity_type_id'] ?? NULL;
    $bundle_id = $this->configuration['bundle'] ?? NULL;
    $excluded_entity_type_ids = $this->configuration['excluded_entity_type_ids'] ?? NULL;

    if ($entity_type_id && in_array($entity_type_id, array_keys($this->supportedEntityTypesTables), TRUE)) {
      $this->addEntityTypeRestrictions($query, $entity_type_id);

      if ($bundle_id) {
        switch ($entity_type_id) {
          case 'node':
            // We want to get a per-node-type URL alias migration. So we inner
            // join the base query on node table based on the link templates of
            // nodes.
            $query->join('node', 'n', "n.nid = REPLACE(ABS(REPLACE(p.redirect, 'node/',  '')), '.0', '')");
            $query->condition('n.type', $bundle_id);
            break;

          case 'taxonomy_term':
            // Join the taxonomy term data table to the base query; based on the
            // link templates of taxonomy term entities.
            $query->join('taxonomy_term_data', 'ttd', "ttd.tid = REPLACE(ABS(REPLACE(p.redirect, 'taxonomy/term/',  '')), '.0', '')");
            $query->fields('ttd', ['vid']);
            // Since the "taxonomy_term_data" table contains only the taxonomy
            // vocabulary ID, but not the vocabulary name, we have to inner
            // join the "taxonomy_vocabulary" table as well.
            $query->join('taxonomy_vocabulary', 'tv', 'ttd.vid = tv.vid');
            $query->condition('tv.machine_name', $bundle_id);
            break;
        }
      }
    }
    elseif ($excluded_entity_type_ids) {
      // Add the opposite of the links template specific conditions we added for
      // the entity_type specific "$base_query": operator is "NOT LIKE", and the
      // conjunction is "AND". Since "AND" is the default, we don't need a
      // separate condition group in this case.
      $this->addExcludedEntityTypeRestrictions($query, $excluded_entity_type_ids);
    }

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    static $default_status_code;
    if (!isset($default_status_code)) {
      // The default status code not necessarily saved to the source database.
      // In this case, redirects should get the default value from the Drupal 7
      // version's variable_get() calls, which is 301.
      // @see https://git.drupalcode.org/project/redirect/-/blob/7f9531d08/redirect.admin.inc#L16
      $default_status_code = $this->variableGet('redirect_default_status_code', 301);
    }
    $current_status_code = $row->getSourceProperty('status_code');
    $status_code = !empty($current_status_code) ? $current_status_code : $default_status_code;
    $row->setSourceProperty('status_code', $status_code);
    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'rid' => $this->t('Redirect ID'),
      'hash' => $this->t('Hash'),
      'type' => $this->t('Type'),
      'uid' => $this->t('UID'),
      'source' => $this->t('Source'),
      'source_options' => $this->t('Source Options'),
      'redirect' => $this->t('Redirect'),
      'redirect_options' => $this->t('Redirect Options'),
      'language' => $this->t('Language'),
      'status_code' => $this->t('Status Code'),
      'count' => $this->t('Count'),
      'access' => $this->t('Access'),
    ];
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['rid']['type'] = 'integer';
    return $ids;
  }

}
