<?php

namespace Drupal\webform_migrate\Plugin\migrate\source\d7;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal 7 webform submission source from database.
 *
 * @MigrateSource(
 *   id = "d7_webform_submission",
 *   core = {7},
 *   source_module = "webform",
 *   destination_module = "webform"
 * )
 */
class D7WebformSubmission extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('webform_submissions', 'wfs');

    $query->fields('wfs', [
      'nid',
      'sid',
      'uid',
      'submitted',
      'remote_addr',
      'is_draft',
    ]);

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'nid' => $this->t('Webform node Id'),
      'sid' => $this->t('Webform submission Id'),
      'uid' => $this->t('User Id of submitter'),
      'submitted' => $this->t('Submission timestamp'),
      'remote_addr' => $this->t('IP Address of submitter'),
      'is_draft' => $this->t('Whether this submission is draft'),
      'webform_id' => $this->t('Id to be used for Webform'),
      'webform_data' => $this->t('Webform submitted data'),
      'webform_uri' => $this->t('Submission uri'),
    ];
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $nid = $row->getSourceProperty('nid');
    $sid = $row->getSourceProperty('sid');
    $submitted_data = $this->buildSubmittedData($sid);
    $row->setSourceProperty('webform_id', 'webform_' . $nid);
    $row->setSourceProperty('webform_data', $submitted_data);
    $row->setSourceProperty('webform_uri', '/node/' . $nid);
    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['sid']['type'] = 'integer';
    $ids['sid']['alias'] = 'wfs';
    return $ids;
  }

  /**
   * Build submitted data from webform submitted data table.
   */
  private function buildSubmittedData($sid) {
    $query = $this->select('webform_submitted_data', 'wfsd');
    $query->innerJoin('webform_component', 'wc', 'wc.nid=wfsd.nid AND wc.cid=wfsd.cid');

    $query->fields('wfsd', [
        'no',
        'data',
      ])
      ->fields('wc', [
        'form_key',
        'extra',
        'type',
        'pid',
      ]);
    $wf_submissions = $query->condition('sid', $sid)->execute();

    $submitted_data = [];
    foreach ($wf_submissions as $wf_submission) {
      $extra = unserialize($wf_submission['extra']);
      // Nested form values will get their parent form item's ID as suffix, so
      // the source and the destination form keys are not always equal.
      $src_form_key = $wf_submission['form_key'];
      $destination_form_key = $wf_submission['pid'] ? "{$src_form_key}_{$wf_submission['pid']}" : $src_form_key;
      $is_multiple = !empty($extra['multiple']) || $wf_submission['type'] === 'grid';

      $item = $is_multiple
        ? $submitted_data[$destination_form_key] ?? []
        : $wf_submission['data'];

      if ($is_multiple && !empty($wf_submission['data'])) {
        $item[$wf_submission['no']] = $wf_submission['data'];
      }

      if (!empty($item)) {
        $submitted_data[$destination_form_key] = $item;
      }
    }
    return $submitted_data;
  }

}
