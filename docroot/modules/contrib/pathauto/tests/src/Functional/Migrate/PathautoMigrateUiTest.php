<?php

namespace Drupal\Tests\pathauto\Functional\Migrate;

use Drupal\node\Entity\Node;
use Drupal\path_alias\AliasRepositoryInterface;
use Drupal\pathauto\Plugin\migrate\process\PathautoPatternSelectionCriteria;
use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\migrate_drupal_ui\Functional\MigrateUpgradeTestBase;
use Drupal\Tests\pathauto\Traits\PathautoMigrationAssertionsTrait;

/**
 * Tests migration of pathauto with Migrate Drupal UI.
 *
 * @group pathauto
 */
class PathautoMigrateUiTest extends MigrateUpgradeTestBase {

  use PathautoMigrationAssertionsTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'content_translation',
    'migrate_drupal_ui',
    'pathauto',
    'pathauto_test_uuid_generator',
    'telephone',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getSourceBasePath() {
    return drupal_get_path('module', 'migrate_drupal_ui') . '/tests/src/Functional/d7/files';
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Pathauto's migration database fixture extends Drupal core's fixture.
    $this->loadFixture(implode(DIRECTORY_SEPARATOR, [
      DRUPAL_ROOT,
      drupal_get_path('module', 'migrate_drupal'),
      'tests',
      'fixtures',
      'drupal7.php',
    ]));
    $this->loadFixture(implode(DIRECTORY_SEPARATOR, [
      DRUPAL_ROOT,
      drupal_get_path('module', 'pathauto'),
      'tests',
      'fixtures',
      'drupal7.php',
    ]));

    // UUIDs used in selection criteria must be predictable.
    $this->container->get('state')->set('pathauto_test_uuid_generator.watch', PathautoPatternSelectionCriteria::class);
  }

  /**
   * Tests the result of pathauto migrations including path alias states.
   */
  public function testPathautoMigrate() {
    $this->executeMigrateUpgradeViaUi();

    $this->assertPathautoSettings();

    $this->assertTermForumsPattern(1);
    $this->assertNodeArticleEnPattern(2);
    $this->assertNodeArticleFrPattern(4);
    $this->assertNodeArticleIsPattern(6);
    $this->assertNodeArticlePattern(8);
    $this->assertNodeBlogPattern(9);
    $this->assertNodeEtPattern(10);
    $this->assertNodePattern();
    $this->assertTermTagsPattern(11);
    $this->assertTermPattern();
    $this->assertUserPattern();

    $path_alias_repository = $this->container->get('path_alias.repository');
    assert($path_alias_repository instanceof AliasRepositoryInterface);

    // Check that the migrated URL aliases are present.
    $this->assertEquals('/term33', $path_alias_repository->lookupBySystemPath('/taxonomy/term/4', 'en')['alias']);
    $this->assertEquals('/term33', $path_alias_repository->lookupBySystemPath('/taxonomy/term/4', 'fr')['alias']);
    $this->assertEquals('/term33', $path_alias_repository->lookupBySystemPath('/taxonomy/term/4', 'is')['alias']);
    $this->assertEquals('/deep-space-9', $path_alias_repository->lookupBySystemPath('/node/2', 'en')['alias']);
    $this->assertEquals('/deep-space-9-is', $path_alias_repository->lookupBySystemPath('/node/2', 'is')['alias']);
    $this->assertEquals('/firefly-is', $path_alias_repository->lookupBySystemPath('/node/4', 'is')['alias']);
    $this->assertEquals('/firefly', $path_alias_repository->lookupBySystemPath('/node/4', 'en')['alias']);

    // Node 11 and taxonomy term 11 will have a generated path alias (after a
    // resave), since they have pathalias = 1 on the source site.
    $this->assertEquals(NULL, $path_alias_repository->lookupBySystemPath('/node/11', 'en')['alias']);
    $this->assertEquals(NULL, $path_alias_repository->lookupBySystemPath('/taxonomy/term/11', 'en')['alias']);
    Node::load(11)->save();
    Term::load(11)->save();
    $this->assertEquals('/entity-translation-test/11/page-one', $path_alias_repository->lookupBySystemPath('/node/11', 'en')['alias']);
    $this->assertEquals('/tag/dax', $path_alias_repository->lookupBySystemPath('/taxonomy/term/11', 'en')['alias']);

    // Taxonomy terms 2 and 3 do not have path alias, and their path alias state
    // is "0": They shouldn't get (new) path alias, neither after a resave.
    $this->assertEquals(NULL, $path_alias_repository->lookupBySystemPath('/taxonomy/term/2', 'en')['alias']);
    $this->assertEquals(NULL, $path_alias_repository->lookupBySystemPath('/taxonomy/term/3', 'en')['alias']);
    Term::load(2)->save();
    Term::load(3)->save();
    $this->assertEquals(NULL, $path_alias_repository->lookupBySystemPath('/taxonomy/term/2', 'en')['alias']);
    $this->assertEquals(NULL, $path_alias_repository->lookupBySystemPath('/taxonomy/term/3', 'en')['alias']);

    // The French translation of node 8 (its node ID on source is "9") has
    // path auto state "0", but the other translations do not have states.
    // So node 8 should't get generated path aliases.
    $this->assertEquals(NULL, $path_alias_repository->lookupBySystemPath('/node/8', 'en')['alias']);
    $this->assertEquals(NULL, $path_alias_repository->lookupBySystemPath('/node/8', 'fr')['alias']);
    $this->assertEquals(NULL, $path_alias_repository->lookupBySystemPath('/node/8', 'is')['alias']);
    Node::load(8)->save();
    $this->assertEquals(NULL, $path_alias_repository->lookupBySystemPath('/node/8', 'en')['alias']);
    $this->assertEquals(NULL, $path_alias_repository->lookupBySystemPath('/node/8', 'fr')['alias']);
    $this->assertEquals(NULL, $path_alias_repository->lookupBySystemPath('/node/8', 'is')['alias']);
  }

  /**
   * Submits the Migrate Upgrade source connection and files form.
   */
  protected function submitMigrateUpgradeSourceConnectionForm() {
    $connection_options = $this->sourceDatabase->getConnectionOptions();
    $this->drupalGet('/upgrade');
    $session = $this->assertSession();
    $session->responseContains("Upgrade a site by importing its files and the data from its database into a clean and empty new install of Drupal");

    $this->drupalPostForm(NULL, [], 'Continue');
    $session->pageTextContains('Provide credentials for the database of the Drupal site you want to upgrade.');

    $driver = $connection_options['driver'];
    $connection_options['prefix'] = $connection_options['prefix']['default'];

    // Use the driver connection form to get the correct options out of the
    // database settings. This supports all of the databases we test against.
    $drivers = drupal_get_database_types();
    $form = $drivers[$driver]->getFormOptions($connection_options);
    $connection_options = array_intersect_key($connection_options, $form + $form['advanced_options']);
    $version = $this->getLegacyDrupalVersion($this->sourceDatabase);
    $edit = [
      $driver => $connection_options,
      'source_private_file_path' => $this->getSourceBasePath(),
      'version' => $version,
      'source_base_path' => $this->getSourceBasePath(),
    ];

    if (count($drivers) !== 1) {
      $edit['driver'] = $driver;
    }
    $edits = $this->translatePostValues($edit);

    $this->drupalPostForm(NULL, $edits, 'Review upgrade');
  }

  /**
   * Executes the upgrade process with Migrate Drupal UI.
   */
  protected function executeMigrateUpgradeViaUi() {
    $this->submitMigrateUpgradeSourceConnectionForm();
    $assert_session = $this->assertSession();
    $assert_session->pageTextNotContains('Resolve all issues below to continue the upgrade.');

    // When complete node migration is executed, Drupal 8.9 and above (even 9.x)
    // will complain about content id conflicts. Drupal 8.8 and below won't.
    // @see https://www.drupal.org/node/2928118
    // @see https://www.drupal.org/node/3105503
    if ($this->getSession()->getPage()->findButton('I acknowledge I may lose data. Continue anyway.')) {
      $this->drupalPostForm(NULL, [], 'I acknowledge I may lose data. Continue anyway.');
      $assert_session->statusCodeEquals(200);
    }

    // Perform the upgrade.
    $this->drupalPostForm(NULL, [], 'Perform upgrade');
    $this->assertText('Congratulations, you upgraded Drupal!');

    // Have to reset all the statics after migration to ensure entities are
    // loadable.
    $this->resetAll();
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityCounts() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function getAvailablePaths() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function getMissingPaths() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityCountsIncremental() {
    return [];
  }

}
