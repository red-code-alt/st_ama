<?php

namespace Drupal\Tests\workbench_moderation_migrate\Functional;

use Drupal\Tests\migrate_drupal_ui\Functional\MigrateUpgradeTestBase;

/**
 * Base class for tests performed with Drupal's migration UI.
 */
abstract class CoreUiMigrateTestBase extends MigrateUpgradeTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'migrate_drupal_ui',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->loadFixture($this->getDatabaseFixtureFilePath());
  }

  /**
   * Returns the drupal-relative path to the database fixture file.
   *
   * @return string
   *   The path to the database file.
   */
  abstract public function getDatabaseFixtureFilePath(): string;

  /**
   * {@inheritdoc}
   */
  protected function getSourceBasePath() {
    return implode(DIRECTORY_SEPARATOR, [
      drupal_get_path('module', 'migrate_drupal_ui'),
      'tests',
      'src',
      'Functional',
      'd7',
      'files',
    ]);
  }

  /**
   * Executes all steps of migrations upgrade.
   */
  public function executeMigrationsWithUi() {
    $this->drupalGet('/upgrade');
    $session = $this->assertSession();
    $session->responseContains("Upgrade a site by importing its files and the data from its database into a clean and empty new install of Drupal");

    $this->submitForm([], 'Continue');
    $session->pageTextContains('Provide credentials for the database of the Drupal site you want to upgrade.');
    $session->fieldExists('mysql[host]');

    // Get valid credentials.
    $edits = $this->translatePostValues($this->getCredentials());

    $this->submitForm($edits, 'Review upgrade');

    $this->submitForm([], 'I acknowledge I may lose data. Continue anyway.');
    $session->statusCodeEquals(200);

    // Submit the review form.
    $this->submitForm([], 'Perform upgrade');
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityCounts() {
    // Unused.
    return ['nothing' => 0];
  }

  /**
   * {@inheritdoc}
   */
  protected function getAvailablePaths() {
    // Unused.
    return ['nothing' => 'nothing'];
  }

  /**
   * {@inheritdoc}
   */
  protected function getMissingPaths() {
    // Unused.
    return ['nothing' => 'nothing'];
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityCountsIncremental() {
    // Unused.
    return $this->getEntityCounts();
  }

  /**
   * Creates an array of credentials for the Credential form.
   *
   * Before submitting to the Credential form the array must be processed by
   * BrowserTestBase::translatePostValues() before submitting.
   *
   * @return array
   *   An array of values suitable for BrowserTestBase::translatePostValues().
   *
   * @todo Remove this method override when security support ends of Drupal core
   *   versions 8.9.x and 9.0.x.
   * @see https://www.drupal.org/project/drupal/issues/3143719
   */
  protected function getCredentials() {
    if (is_callable('parent::getCredentials')) {
      return parent::getCredentials();
    }
    $connection_options = $this->sourceDatabase->getConnectionOptions();
    $version = $this->getLegacyDrupalVersion($this->sourceDatabase);
    $driver = $connection_options['driver'];
    $connection_options['prefix'] = $connection_options['prefix']['default'];

    // Use the driver connection form to get the correct options out of the
    // database settings. This supports all of the databases we test against.
    $drivers = drupal_get_database_types();
    $form = $drivers[$driver]->getFormOptions($connection_options);
    $connection_options = array_intersect_key($connection_options, $form + $form['advanced_options']);
    $edit = [
      $driver => $connection_options,
      'source_private_file_path' => $this->getSourceBasePath(),
      'version' => $version,
    ];
    if ($version == 6) {
      $edit['d6_source_base_path'] = $this->getSourceBasePath();
    }
    else {
      $edit['source_base_path'] = $this->getSourceBasePath();
    }
    if (count($drivers) !== 1) {
      $edit['driver'] = $driver;
    }
    return $edit;
  }

}
