<?php

namespace Drupal\webform_migrate\Plugin\migrate\source;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Driver\sqlite\Connection as DeprecatedSqLiteConnection;
use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\Plugin\MigrationDeriverTrait;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;
use Drupal\sqlite\Driver\Database\sqlite\Connection as SqLiteConnection;

/**
 * Deriver class for webform submission migration.
 *
 * This class is used for determining the actual dependencies of webform
 * submission migration; and doesn't produce webform submission migration
 * derivatives.
 *
 * @todo Check how this could be used for Drupal 6 webform submissions.
 */
class WebformSubmissionDeriver extends DeriverBase {

  use MigrationDeriverTrait;

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $source = static::getSourcePlugin($base_plugin_definition['source']['plugin']);

    if (!($source instanceof DrupalSqlBase)) {
      return $this->derivatives;
    }
    try {
      $source->checkRequirements();
    }
    catch (RequirementsException $e) {
      return $this->derivatives;
    }

    $system_data = $source->getSystemData();
    $system_schema_version = $system_data['module']['system']['schema_version'];

    if ($system_schema_version < 7000) {
      // Not a Drupal 7 source.
      throw new \LogicException(sprintf('The "%s" migration deriver class should be used in Drupal 7 migrations only.', get_class($this)));
    }

    // File source.
    $file_source = static::getSourcePlugin('d7_file');
    try {
      $file_source->checkRequirements();
    }
    catch (RequirementsException $e) {
      // File migration requirements are not met.
      return [0 => $base_plugin_definition];
    }

    assert($file_source instanceof DrupalSqlBase);
    $webform_submission_files_query = $file_source->getDatabase()
      ->select('file_managed', 'fm')
      ->distinct(TRUE);
    $webform_submission_files_query->join('file_usage', 'fu', 'fu.fid = fm.fid AND fu.module = :module AND fu.type = :entity_type', [
      ':module' => 'webform',
      ':entity_type' => 'submission',
    ]);
    $webform_submission_files_query->addExpression($this->getSchemeExpression($file_source->getDatabase()), 'scheme');
    $webform_submission_file_schemes = $webform_submission_files_query->execute()->fetchAllKeyed(0, 0);

    $file_migrations_scheme_map = [
      'public' => 'd7_file',
      'private' => 'd7_file_private',
    ];
    foreach ($webform_submission_file_schemes as $scheme) {
      if (empty($file_migrations_scheme_map[$scheme])) {
        continue;
      }
      $required_migrations = $base_plugin_definition['migration_dependencies']['required'] ?? [];
      $base_plugin_definition['migration_dependencies']['required'] = array_unique(
        array_merge(
          array_values($required_migrations),
          [$file_migrations_scheme_map[$scheme]]
        )
      );
    }

    return [0 => $base_plugin_definition];
  }

  /**
   * Returns the expression for the DB for getting the URI scheme.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   Database connection of the source Drupal 7 instance.
   *
   * @return string
   *   The expression for the DB for getting the URI scheme.
   */
  protected function getSchemeExpression($connection) {
    assert($connection instanceof Connection);
    return ($connection instanceof DeprecatedSqLiteConnection) || ($connection instanceof SqLiteConnection)
      ? "SUBSTRING(fm.uri, 1, INSTR(fm.uri, '://') - 1)"
      : "SUBSTRING(fm.uri, 1, POSITION('://' IN fm.uri) - 1)";
  }

}
