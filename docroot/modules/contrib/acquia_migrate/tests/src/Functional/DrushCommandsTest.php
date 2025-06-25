<?php

namespace Drupal\Tests\acquia_migrate\Functional;

use Drush\TestTraits\DrushTestTrait;

/**
 * Executes drush on fully functional website.
 *
 * Also serves as executable documentation on how to use AMA's drush commands.
 *
 * @group acquia_migrate
 * @group acquia_migrate__core
 */
class DrushCommandsTest extends HttpApiTestBase {

  use DrushTestTrait {
    getPathToDrush as traitGetPathToDrush;
  }

  /**
   * {@inheritdoc}
   */
  protected $profile = 'standard';

  /**
   * {@inheritdoc}
   */
  public function getPathToDrush() {
    // Make terminal double width to make AMA's Drush output easier to digest.
    // ⚠️ That makes this return value *not* a path, but since
    // \Drush\TestTraits\DrushTestTrait::drush() is the only caller of this
    // method, this seems relatively safe.
    // 😬 Using `putenv('COLUMNS=160');` does not work unfortunately.
    return 'COLUMNS=160 ' . $this->traitGetPathToDrush();
  }

  /**
   * Tests the typical auditing Acquia Support would do using ama:* commands.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function testCommands(): void {
    // We need to explicitly test whitespace, so disable that portion of phpcs.
    // phpcs:disable Drupal.WhiteSpace.ScopeIndent.Incorrect
    // phpcs:disable Drupal.Commenting.InlineComment.SpacingAfter
    // phpcs:disable DrupalPractice.Commenting.CommentEmptyLine.SpacingAfter

    // First, find out which commands are available to us.
    $this->drush('ama', [], [], NULL, NULL, 1);
    $this->assertOutputEquals('');
    $this->assertErrorOutputEquals(<<<OUTPUT


  Command "ama" is ambiguous.
  Did you mean one of these?
      ama:module-audit    Audits installed modules.
      ama:status          Status of Acquia Migrate Accelerate. ASCII view of the dashboard + details.
      ama:remaining-rows  Lists remaining rows.
      ama:import          Import migrations in Acquia Migrate Accelerate.
      ama:messages:export Export migration messages..



OUTPUT
);

    // Start with a module audit, to ensure the customer did not install modules
    // that break something.
    // TRICKY: for testing purposes only, we add `--fields=…`, to omit the
    // install date which would be painful to assert.
    $omit_installed_date = ' --fields=module,vetted,stable,has_migrations,alters_migrations,risk,installed';
    $this->drush('ama:module-audit --risky' . $omit_installed_date);
    $this->assertOutputEquals(<<<OUTPUT
 -------- -------- -------- ---------------- ------------------- ------ -----------
  Module   Vetted   Stable   Has Migrations   Alters Migrations   Risk   Installed
 -------- -------- -------- ---------------- ------------------- ------ -----------

OUTPUT
);

    // No risky modules, great! Now let's run a complete module audit to get a
    // complete picture.
    $this->drush('ama:module-audit' . $omit_installed_date);
    $this->assertEmpty($this->getErrorOutput());
    $this->assertOutputEquals(<<<OUTPUT
 ------------------------------- -------- -------- ---------------- ------------------- ------ -----------
  Module                          Vetted   Stable   Has Migrations   Alters Migrations   Risk   Installed
 ------------------------------- -------- -------- ---------------- ------------------- ------ -----------
  acquia_migrate                  yes      yes      yes              yes                        yes
  automated_cron                  yes      yes                                                  yes
  big_pipe                        yes      yes                                                  yes
  block                           yes      yes      yes                                         yes
  block_content                   yes      yes      yes                                         yes
  breakpoint                      yes      yes                                                  yes
  ckeditor5                       yes      yes                                                  yes
  comment                         yes      yes      yes                                         yes
  config                          yes      yes                                                  yes
  contact                         yes      yes      yes                                         yes
  contextual                      yes      yes                                                  yes
  datetime                        yes      yes      yes                                         yes
  dblog                           yes      yes      yes                                         yes
  decoupled_pages                 yes      yes                                                  yes
  dynamic_page_cache              yes      yes                                                  yes
  editor                          yes      yes                                                  yes
  field                           yes      yes      yes                                         yes
  field_ui                        yes      yes                                                  yes
  file                            yes      yes      yes                                         yes
  filter                          yes      yes      yes                                         yes
  help                            yes      yes                                                  yes
  history                         yes      yes                                                  yes
  image                           yes      yes      yes              yes                        yes
  link                            yes      yes      yes                                         yes
  menu_link_content               yes      yes      yes                                         yes
  menu_ui                         yes      yes      yes                                         yes
  migmag                          yes      yes                                                  yes
  migmag_menu_link_migrate        yes      yes      yes              yes                        yes
  migmag_process                  yes      yes      yes                                         yes
  migmag_process_lookup_replace   yes      yes                       yes                        yes
  migmag_rollbackable             yes      yes      yes                                         yes
  migmag_rollbackable_replace     yes      yes                       yes                        yes
  migrate                         yes      yes      yes                                         yes
  migrate_drupal                  yes      yes      yes                                         yes
  migrate_drupal_ui               yes      yes                                                  yes
  migrate_plus                    yes      yes      yes              yes                        yes
  node                            yes      yes      yes                                         yes
  options                         yes      yes      yes                                         yes
  page_cache                      yes      yes                                                  yes
  path                            yes      yes      yes                                         yes
  path_alias                      yes      yes                                                  yes
  search                          yes      yes      yes                                         yes
  shortcut                        yes      yes      yes                                         yes
  standard                                 yes                                                  yes
  syslog                          yes      yes      yes                                         yes
  system                          yes      yes      yes                                         yes
  taxonomy                        yes      yes      yes                                         yes
  text                            yes      yes      yes                                         yes
  toolbar                         yes      yes                                                  yes
  tour                            yes      yes                                                  yes
  user                            yes      yes      yes                                         yes
  views                           yes      yes                                                  yes
  views_ui                        yes      yes                                                  yes
 ------------------------------- -------- -------- ---------------- ------------------- ------ -----------

OUTPUT,
      // Ignore the used database driver-providing module, because this test
      // must pass regardless of the database used when running the test.
      '/\W+(mysql|sqlite)\W+.*/'
);

    // Wow that was overwhelming! Let's instead get a sense of the most crucial
    // package version: Drupal core. Note that we can request multiple module
    // versions at the same time: `--filter="module~=/(acquia_migrate|system)/".
    $this->drush('ama:module-audit --fields=module,version --filter="module=system"');
    $this->assertEmpty($this->getErrorOutput());
    $this->assertOutputEquals(str_replace('9.4.5', \Drupal::VERSION, <<<OUTPUT
 -------- ---------
  Module   Version
 -------- ---------
  system   9.4.5
 -------- ---------

OUTPUT
    ));

    // Alright, we've got a sense of what's been going on! Let's view the AMA
    // dashboard in ASCII form — this is the same as you'd see when visiting
    // /acquia-migrate-accelerate/migrations with a browser!
    $this->drush('ama:status', [], [], NULL, NULL, 1);
    $this->assertErrorOutputEquals(<<<OUTPUT
 [error]  ⛔️ Please use the UI first to select which data to migrate: go to /acquia-migrate-accelerate/start-page.

OUTPUT);

    // Woah! The customer didn't even visit their AMA dashboard at all — they
    // should do that first using a browser, and they will be asked to select
    // which data they want to migrate. Let's simulate that.
    $this->drupalLogin($this->rootUser);
    $this->performMigrationPreselection();

    // Now let's check again.
    $this->drush('ama:status');
    $this->assertErrorOutputEquals('');
    $this->assertOutputEquals(<<<OUTPUT
⚠️️  If you want to be able to inspect the migrated data at arbitrary points in time, it is strongly recommended to first let the initial import finish, by visiting /acquia-migrate-accelerate/start-page — it will start automatically there.

 ------------------------------------- ------------- -------- ------- -------- -------- ------- ---------- ---------------- ----------- ----------
  Migration                             UI tab        Proc #   Imp #   Tot #    Proc %   Imp %   Messages   M (validation)   M (other)   Activity
 ------------------------------------- ------------- -------- ------- -------- -------- ------- ---------- ---------------- ----------- ----------
  Filter format configuration           in-progress        0                5     0%                  0                                  idle
  Shared structure for comments         in-progress        0                3     0%                  0                                  idle
  Shared structure for content items    in-progress        0               55     0%                  0                                  idle
  Shared structure for taxonomy terms   in-progress        0                6     0%                  0                                  idle
  Shared structure for menus            in-progress        0                6     0%                  0                                  idle
  Custom blocks                         in-progress        0                1     0%                  0                                  idle
  User accounts                         in-progress        0                3     0%                  0                                  idle
  Public files                          in-progress        0                2     0%                  0                                  idle
  Tags taxonomy terms                   in-progress        0               10     0%                  0                                  idle
  VocabLocalized taxonomy terms         in-progress        0                2     0%                  0                                  idle
  VocabTranslate taxonomy terms         in-progress        0                3     0%                  0                                  idle
  VocabFixed taxonomy terms             in-progress        0                1     0%                  0                                  idle
  Article                               in-progress        0               18     0%                  0                                  idle
  Sujet de discussion taxonomy terms    in-progress        0                5     0%                  0                                  idle
  Blog entry                            in-progress        0                3     0%                  0                                  idle
  Entity translation test               in-progress        0                8     0%                  0                                  idle
  Forum topic                           in-progress        0                2     0%                  0                                  idle
  Basic page                            in-progress        0                1     0%                  0                                  idle
  Private files                         in-progress        0                1     0%                  0                                  idle
  Test Vocabulary taxonomy terms        in-progress        0                4     0%                  0                                  idle
  Test content type                     in-progress        0                1     0%                  0                                  idle
  Article comments                      in-progress        0                2     0%                  0                                  idle
  Test content type comments            in-progress        0                2     0%                  0                                  idle
  Other Menu links                      in-progress        0                8     0%                  0                                  idle
  Shortcut links                        in-progress        0                4     0%                  0                                  idle
  VocabLocalized2 taxonomy terms        in-progress        0                1     0%                  0                                  idle
  URL aliases (remaining)               in-progress        0                1     0%                  0                                  idle
  Block placements                      in-progress        0               10     0%                  0                                  idle
  Site configuration                    in-progress        0               55     0%                  0                                  idle
 ------------------------------------- ------------- -------- ------- -------- -------- ------- ---------- ---------------- ----------- ----------

OUTPUT
);

    // 0 rows imported for every listed migration! 🤔 Oh, and a warning above
    // the table! This is something one should never see in reality because
    // immediately after selecting the data to migrate, the browser-based UI
    // also automatically triggers the "initial migration". That imports
    // supporting configuration for *all* migrations, for example the node type
    // config entity and the field config entities for each content type
    // migration.
    $this->performInitialMigration();

    // Now let's re-check `drush ama:status` 🤓. This is similar to the "most
    // empty AMA environment imaginable" one can find in reality.
    $this->drush('ama:status');
    $this->assertErrorOutputEquals('');
    $this->assertOutputEquals(<<<OUTPUT
 ------------------------------------ ------------- -------- ------- -------- -------- ------- ---------- ---------------- ----------- ----------
  Migration                            UI tab        Proc #   Imp #   Tot #    Proc %   Imp %   Messages   M (validation)   M (other)   Activity
 ------------------------------------ ------------- -------- ------- -------- -------- ------- ---------- ---------------- ----------- ----------
  Custom blocks                        in-progress        0                1     0%                  0                                  idle
  User accounts                        in-progress        0                3     0%                  0                                  idle
  Public files                         in-progress        0                2     0%                  0                                  idle
  Tags taxonomy terms                  in-progress        0               10     0%                  0                                  idle
  VocabLocalized taxonomy terms        in-progress        0                2     0%                  0                                  idle
  VocabTranslate taxonomy terms        in-progress        0                3     0%                  0                                  idle
  VocabFixed taxonomy terms            in-progress        0                1     0%                  0                                  idle
  Article                              in-progress        0               18     0%                  0                                  idle
  Sujet de discussion taxonomy terms   in-progress        0                5     0%                  0                                  idle
  Blog entry                           in-progress        0                3     0%                  0                                  idle
  Entity translation test              in-progress        0                8     0%                  0                                  idle
  Forum topic                          in-progress        0                2     0%                  1          0                1      idle
  Basic page                           in-progress        0                1     0%                  0                                  idle
  Private files                        in-progress        0                1     0%                  0                                  idle
  Test Vocabulary taxonomy terms       in-progress        0                4     0%                  0                                  idle
  Test content type                    in-progress        0                1     0%                  2          0                2      idle
  Article comments                     in-progress        0                2     0%                  0                                  idle
  Test content type comments           in-progress        0                2     0%                  0                                  idle
  Other Menu links                     in-progress        0                8     0%                  0                                  idle
  Shortcut links                       in-progress        0                4     0%                  0                                  idle
  VocabLocalized2 taxonomy terms       in-progress        0                1     0%                  0                                  idle
  URL aliases (remaining)              in-progress        0                1     0%                  0                                  idle
  Block placements                     in-progress        0               10     0%                  0                                  idle
  Site configuration                   in-progress        0               55     0%                  0                                  idle
 ------------------------------------ ------------- -------- ------- -------- -------- ------- ---------- ---------------- ----------- ----------

OUTPUT
);

    // Many migrations disappeared! At quick glance, at least the first five.
    // The "Forum topic" migration has 1 migration message, and "Test content
    // type" has 2. All 3 must be due to that "initial migration" — that imports
    // supporting configuration for *all* migrations, which is necessary for
    // features like the "preview" functionality in the browser-based UI). Let's
    // figure out which UI tab the disappeared migrations are listed on, and we
    // will also see how many migration messages were generated for it (which
    // must be evaluated by a human).
    $this->drush('ama:status --all');
    $this->assertErrorOutputEquals('');
    $this->assertOutputEquals(<<<OUTPUT
 ------------------------------------------------ -------------- -------- -------- -------- -------- ------- ---------- ---------------- ----------- ----------
  Migration                                        UI tab         Proc #   Imp #    Tot #    Proc %   Imp %   Messages   M (validation)   M (other)   Activity
 ------------------------------------------------ -------------- -------- -------- -------- -------- ------- ---------- ---------------- ----------- ----------
  Filter format configuration                      needs-review        5        5        5   100%     100%         1          0                1      idle
  Shared structure for comments                    completed           3        3        3   100%     100%         0                                  idle
  Shared structure for content items               needs-review       55       52       55   100%      94%         3          0                3      idle
  Shared structure for taxonomy terms              completed           6        6        6   100%     100%         0                                  idle
  Shared structure for menus                       completed           6        6        6   100%     100%         0                                  idle
  Custom blocks                                    in-progress         0                 1     0%                  0                                  idle
  User accounts                                    in-progress         0                 3     0%                  0                                  idle
  Public files                                     in-progress         0                 2     0%                  0                                  idle
  Test long name                                   skipped             0                 0   100%                  0                                  idle
  Tags taxonomy terms                              in-progress         0                10     0%                  0                                  idle
  VocabLocalized taxonomy terms                    in-progress         0                 2     0%                  0                                  idle
  VocabTranslate taxonomy terms                    in-progress         0                 3     0%                  0                                  idle
  VocabFixed taxonomy terms                        in-progress         0                 1     0%                  0                                  idle
  Article                                          in-progress         0                18     0%                  0                                  idle
  Sujet de discussion taxonomy terms               in-progress         0                 5     0%                  0                                  idle
  Blog entry                                       in-progress         0                 3     0%                  0                                  idle
  Book page                                        skipped             0                 0   100%                  0                                  idle
  Entity translation test                          in-progress         0                 8     0%                  0                                  idle
  Forum topic                                      in-progress         0                 2     0%                  1          0                1      idle
  Basic page                                       in-progress         0                 1     0%                  0                                  idle
  Private files                                    in-progress         0                 1     0%                  0                                  idle
  Test Vocabulary taxonomy terms                   in-progress         0                 4     0%                  0                                  idle
  Test content type                                in-progress         0                 1     0%                  2          0                2      idle
  Test long name comments                          skipped             0                 0   100%                  0                                  idle
  Article comments                                 in-progress         0                 2     0%                  0                                  idle
  Blog entry comments                              skipped             0                 0   100%                  0                                  idle
  Book page comments                               skipped             0                 0   100%                  0                                  idle
  Entity translation test comments                 skipped             0                 0   100%                  0                                  idle
  Forum topic comments                             skipped             0                 0   100%                  0                                  idle
  Basic page comments                              skipped             0                 0   100%                  0                                  idle
  Test content type comments                       in-progress         0                 2     0%                  0                                  idle
  Other Menu links                                 in-progress         0                 8     0%                  0                                  idle
  Shortcut links                                   in-progress         0                 4     0%                  0                                  idle
  VocabLocalized2 taxonomy terms                   in-progress         0                 1     0%                  0                                  idle
  vocabulary name clearly different than machine   skipped             0                 0   100%                  0                                  idle
  name and much longer than thirty two
  characters taxonomy terms
  URL aliases (remaining)                          in-progress         0                 1     0%                  0                                  idle
  Block placements                                 in-progress         0                10     0%                  0                                  idle
  Site configuration                               in-progress         0                55     0%                  0                                  idle
 ------------------------------------------------ -------------- -------- -------- -------- -------- ------- ---------- ---------------- ----------- ----------

OUTPUT
);

    // So another 4 migration messages were found for  "Filter format
    // configuration" and "Shared structure for content items". That's why they
    // appear on the "needs-review" tab in the UI. They need review from a
    // human.
    // Since this is a test not involving humans, the best we can do is dig into
    // the verbose details of this migration.
    $this->drush('ama:status "Shared structure for content items" --verbose');
    $this->assertStringContainsString('[info] Drush bootstrap phase: bootstrapDrupalRoot()', $this->getErrorOutput());
    $this->assertOutputEquals(<<<OUTPUT
 ------------------------------------ -------------- -------- -------- -------- -------- ------- ---------- ---------------- ----------- ----------
  Migration                            UI tab         Proc #   Imp #    Tot #    Proc %   Imp %   Messages   M (validation)   M (other)   Activity
 ------------------------------------ -------------- -------- -------- -------- -------- ------- ---------- ---------------- ----------- ----------
  Shared structure for content items   needs-review       55       52       55   100%      94%         3          0                3      idle
      d7_field:node                                       52       49       52   100%      94%
      d7_view_modes:node                                   3        3        3   100%     100%
 ------------------------------------ -------------- -------- -------- -------- -------- ------- ---------- ---------------- ----------- ----------

OUTPUT
);

    // We can apply interesting filters, such as filtering to only those
    // migrations that have had *some* rows imported! Since the dashboard only
    // shows only "data" rows for each Migration, we should see nothing listed,
    // except for those migrations that are *purely* supporting configuration.
    // (This was a conscious UX choice because the user cares more about the
    // actual data they interact with than the supporting configuration, such as
    // configured fields.)
    // ⚠️ Hence the absence of "User accounts", despite 2 migration messages!
    $this->drush('ama:status --all --filter="imported_count~=/\d+/"');
    $this->assertErrorOutputEquals('');
    $this->assertOutputEquals(<<<OUTPUT
 ------------------------------------- -------------- -------- -------- -------- -------- ------- ---------- ---------------- ----------- ----------
  Migration                             UI tab         Proc #   Imp #    Tot #    Proc %   Imp %   Messages   M (validation)   M (other)   Activity
 ------------------------------------- -------------- -------- -------- -------- -------- ------- ---------- ---------------- ----------- ----------
  Filter format configuration           needs-review        5        5        5   100%     100%         1          0                1      idle
  Shared structure for comments         completed           3        3        3   100%     100%         0                                  idle
  Shared structure for content items    needs-review       55       52       55   100%      94%         3          0                3      idle
  Shared structure for taxonomy terms   completed           6        6        6   100%     100%         0                                  idle
  Shared structure for menus            completed           6        6        6   100%     100%         0                                  idle
 ------------------------------------- -------------- -------- -------- -------- -------- ------- ---------- ---------------- ----------- ----------

OUTPUT
  );

    // Let's zoom in on one particular migration, f.e. a migration for a
    // taxonomy terms vocabulary. A "Migration" as presented by AMA consists of
    // many migration plugins under the hood. In this example, it's the terms in
    // the vocabulary, plus the associated URL aliases. This means the customer
    // is able to — at every step during the migration process — to inspect the
    // data on the migrated Drupal site and verify that it looks good.
    $this->drush('ama:status "Test Vocabulary taxonomy terms" --verbose');
    $this->assertStringContainsString('[info] Drush bootstrap phase: bootstrapDrupalRoot()', $this->getErrorOutput());
    $this->assertOutputEquals(<<<OUTPUT
 ------------------------------------------------ ------------- -------- ------- -------- -------- ------- ---------- ---------------- ----------- ----------
  Migration                                        UI tab        Proc #   Imp #   Tot #    Proc %   Imp %   Messages   M (validation)   M (other)   Activity
 ------------------------------------------------ ------------- -------- ------- -------- -------- ------- ---------- ---------------- ----------- ----------
  Test Vocabulary taxonomy terms                   in-progress        0                4     0%                  0                                  idle
      d7_taxonomy_term:test_vocabulary                                0                3     0%
      d7_url_alias:taxonomy_term:test_vocabulary                      0                1     0%
 ------------------------------------------------ ------------- -------- ------- -------- -------- ------- ---------- ---------------- ----------- ----------

OUTPUT
);

    // 0 rows were imported. Let's simulate the customer being active. This uses
    // Drush's built-in `migrate:import` command, which can only execute one
    // migration plugin at a time; it is not aware of AMA's "migration" concept,
    // which presents semantically connected migration plugins as a single
    // "migration" to the customer. We artificially limit it to 1 of the 2 items
    // to simulate an in-progress migration — in reality it'd more likely be 200
    // of the 1000 rows, for example.
    $this->drush('migrate:import d7_taxonomy_term:test_vocabulary --limit=1');
    $this->assertStringContainsString('Processed 1 item', $this->getErrorOutput());
    $this->assertEmpty(trim($this->getOutput()));

    // Let's see what the customer would see if they were to click on the
    // "Details" tab for this migration in the browser-based UI.
    $this->drush('ama:status "Test Vocabulary taxonomy terms" --verbose');
    $this->assertStringContainsString('[info] Drush bootstrap phase: bootstrapDrupalRoot()', $this->getErrorOutput());
    $this->assertOutputEquals(<<<OUTPUT
 ------------------------------------------------ ------------- -------- -------- -------- -------- ------- ---------- ---------------- ----------- ----------
  Migration                                        UI tab        Proc #   Imp #    Tot #    Proc %   Imp %   Messages   M (validation)   M (other)   Activity
 ------------------------------------------------ ------------- -------- -------- -------- -------- ------- ---------- ---------------- ----------- ----------
  Test Vocabulary taxonomy terms                   in-progress        1        1        4    25%      25%         1          1                0      idle
      d7_taxonomy_term:test_vocabulary                                1        1        3    33%      33%
      d7_url_alias:taxonomy_term:test_vocabulary                      0                 1     0%
 ------------------------------------------------ ------------- -------- -------- -------- -------- ------- ---------- ---------------- ----------- ----------

OUTPUT
);

    // That makes sense! The URL aliases have not yet been imported, but the
    // terms were. This means 100% of one of the migration plugins in this
    // migration was imported, but 0% of another, making in this case for a
    // total progress of all "data" rows of 75%.
    // But … we can see that 2 migration messages have appeared, both of the
    // "validation" category. What do they look like?
    // TRICKY: for testing purposes only, we add `--fields=…`, to omit the
    // timestamp which would be painful to assert.
    $omit_timestamp = ' --fields=msgid,sourceMigration,sourceMigrationPlugin,source_id,messageCategory,severity,message';
    $this->drush('ama:messages:export "Test Vocabulary taxonomy terms"' . $omit_timestamp);
    $this->assertErrorOutputEquals('');
    $this->assertOutputEquals(<<<OUTPUT
 ------------ ----------------------------- ------------------ ----------- ------------------- ---------- -------------------------------------------------
  Message ID   Migration                     Migration plugin   Source ID   Message category    Severity   Message
 ------------ ----------------------------- ------------------ ----------- ------------------- ---------- -------------------------------------------------
  8            858158392a46f33c9e9bbbb36e7   d7_taxonomy_te     tid=2       entity_validation   Error      [taxonomy_term: 2, revision: 1]:
               abd1f-Test Vocabulary         rm:test_vocabu                                                field_term_reference.0.target_id=The referenced
               taxonomy terms                lary                                                          entity (taxonomy_term: 3) does not exist.
 ------------ ----------------------------- ------------------ ----------- ------------------- ---------- -------------------------------------------------

OUTPUT
);

    // Taxonomy term 2 apparently has a "field_term_reference" field that points
    // another entity (another taxonomy term, actually!) which does not exist.
    // This may be an invalid entity in Drupal 7. Or, perhaps, this is an edge
    // case that AMA is unable to handle.
    // So the customer SHOULD inspect their Drupal 7 site. If they didn't, you
    // can inspect their Drupal 7 database and … turns out that the referenced
    // taxonomy term *does* exist (in the taxonomy_term_data table), even in the
    // same vocabulary. So it's a only "invalid" because terms are imported in
    // the sequence they were created, and the 2nd term references the 3rd,
    // which simply does not exist yet.
    // Based on this, we could either:
    // 1. ignore it, because we've concluded it is harmless: it will be valid as
    //    soon as the 3rd term is imported (most pragmatic)
    // 2. harden the migration logic in the relevant Drupal module, to prevent
    //    this problem from occurring again in the future (best, but costs time)
    // Generally speaking, it's best to work around a problem manually if
    // there's only a few occurrences, and to work on hardening the migration
    // logic in relevant places when it's a widespread problem.
    //
    // Here, we choose to go with the "ignore it" strategy. To be able to do
    // this, we must verify our theory is correct. First, verify we can
    // reproduce the validation error.
    $this->drush(<<<'COMMAND'
ev '$term = \Drupal\taxonomy\Entity\Term::load(2);
$violations = $term->validate();
$messages = array_map(function ($v) {return (string) $v->getMessage();}, iterator_to_array($violations));
var_dump($messages);'
COMMAND);
    $this->assertOutputEquals(<<<OUTPUT
array(1) {
  [0]=>
  string(114) "The referenced entity (<em class="placeholder">taxonomy_term</em>: <em class="placeholder">3</em>) does not exist."
}

OUTPUT);

    // For any migration, we can easily list the remaining rows. Usually we'd
    // use this when a migration largely completed, but a few rows were somehow
    // not processed — this makes it easy to list those rows!
    $this->drush('ama:remaining-rows "Test Vocabulary taxonomy terms"');
    $this->assertStringContainsString('Scan complete, found 2 of 2 unprocessed rows.', $this->getErrorOutput());
    $this->assertOutputEquals(<<<OUTPUT
>  Analyzing 2 data migration plugins for remaining rows. Unprocessed rows require a complete scan, to cross-reference the complete set of source rows against the migrate ID mapping.
>  [1/2] d7_taxonomy_term:test_vocabulary has 2 unprocessed rows, scanning…


>  ℹ️  d7_taxonomy_term:test_vocabulary DOES use high_water_property. It is currently at 0.
>  [2/2] d7_url_alias:taxonomy_term:test_vocabulary has no processed rows, no need to scan.



 ---------------------------------- ----------- ------------- ------------------
  Migration Plugin ID                Source ID   Assessment    Below High Water
 ---------------------------------- ----------- ------------- ------------------
  d7_taxonomy_term:test_vocabulary   3           unprocessed   1
  d7_taxonomy_term:test_vocabulary   4           unprocessed   1
 ---------------------------------- ----------- ------------- ------------------

OUTPUT
);

    // Import the remaining rows of the migration plugin … and actually of *all*
    // migration plugins in this migration. This is *exactly* what happens when
    // the customer selects a migration in the browser-based UI and imports it.
    // AMA just provides you with a CLI equivalent 😊. This may enable one
    // more efficiently assist the customer.
    $this->drush('ama:import "Test Vocabulary taxonomy terms"');
    $this->assertErrorOutputEquals('');
    $this->assertStringContainsString('📈 4 processed, 4 imported of 4 total rows', $this->getOutput());

    // Verify that the migration is complete.
    $this->drush('ama:status "Test Vocabulary taxonomy terms" --verbose');
    $this->assertStringContainsString('[info] Drush bootstrap phase: bootstrapDrupalRoot()', $this->getErrorOutput());
    $this->assertOutputEquals(<<<OUTPUT
 ------------------------------------------------ -------------- -------- -------- -------- -------- ------- ---------- ---------------- ----------- ----------
  Migration                                        UI tab         Proc #   Imp #    Tot #    Proc %   Imp %   Messages   M (validation)   M (other)   Activity
 ------------------------------------------------ -------------- -------- -------- -------- -------- ------- ---------- ---------------- ----------- ----------
  Test Vocabulary taxonomy terms                   needs-review        4        4        4   100%     100%         2          2                0      idle
      d7_taxonomy_term:test_vocabulary                                 3        3        3   100%     100%
      d7_url_alias:taxonomy_term:test_vocabulary                       1        1        1   100%     100%
 ------------------------------------------------ -------------- -------- -------- -------- -------- ------- ---------- ---------------- ----------- ----------

OUTPUT
);

    // Verify that the validation error no longer occurs.
    $this->drush(<<<'COMMAND'
ev '$term = \Drupal\taxonomy\Entity\Term::load(2);
$violations = $term->validate();
$messages = array_map(function ($v) {return (string) $v->getMessage();}, iterator_to_array($violations));
var_dump($messages);'
COMMAND);
    $this->assertOutputEquals(<<<OUTPUT
array(0) {
}

OUTPUT);

    // Success! We used the AMA drush commands to analyze a migration problem 🙌
    // that was surfaced by a message. Now it's your turn, dear reader!
    //
    // When starting to analyze any given AMA site, ALWAYS begin with
    // investigating non-entity validation messages, specifically:
    //
    // 1. ALWAYS FIRST investigate & solve "Critical" migration messages. These
    //    are extremely disruptive, and cause migrations to be unreliable.
    $this->drush(' ama:messages:export --filter="messageCategory=other" --filter="severity=Critical"' . $omit_timestamp);
    $this->assertErrorOutputEquals('');
    $this->assertOutputEquals(<<<OUTPUT
 ------------ ----------- ------------------ ----------- ------------------ ---------- ---------
  Message ID   Migration   Migration plugin   Source ID   Message category   Severity   Message
 ------------ ----------- ------------------ ----------- ------------------ ---------- ---------

OUTPUT);
    // 2. Great, zero Criticals! Next, look at the "Error" migration messages.
    $this->drush(' ama:messages:export --filter="messageCategory=other" --filter="severity=Error"' . $omit_timestamp);
    $this->assertErrorOutputEquals('');
    // 3. Finally, look at the "Warning" ones.
    $this->drush(' ama:messages:export --filter="messageCategory=other" --filter="severity=Warning"' . $omit_timestamp);
    $this->assertErrorOutputEquals('');

    // Finally: one *can* import all of the migrations with a single command:
    //
    // @code
    //   drush ama:import --all
    // @endcode
    //
    // … but as shown above: messages must be reviewed by humans 🧑‍🔬.
    //
    // Happy migrating 🤖!

    // phpcs:enable
  }

  /**
   * {@inheritdoc}
   *
   * Better version of CliTestTrait::assertOutputEquals(), which respects
   * table formatting.
   *
   * @see \Drush\TestTraits\CliTestTrait::assertOutputEquals
   */
  private function assertOutputEquals(string $expected, string $filter = ''): void {
    self::outputEqualsHelper($expected, $this->getOutputRaw(), $filter);
  }

  /**
   * {@inheritdoc}
   *
   * Better version of CliTestTrait::assertErrorOutputEquals(), which respects
   * table formatting.
   *
   * @see \Drush\TestTraits\CliTestTrait::assertErrorOutputEquals
   */
  private function assertErrorOutputEquals(string $expected, string $filter = ''): void {
    self::outputEqualsHelper($expected, $this->getErrorOutputRaw(), $filter);
  }

  /**
   * Helper for ::assertOutputEquals() and ::assertErrorOutputEquals().
   *
   * @param string $expected
   *   The expected output.
   * @param string $actual
   *   The actual output.
   * @param string $filter
   *   Regular expression that should be ignored in the error output.
   */
  private static function outputEqualsHelper(string $expected, string $actual, string $filter): void {
    if (!empty($filter)) {
      $actual = preg_replace($filter, '', $actual);
    }
    $lines = preg_split('/\r\n|\r|\n/', $actual);
    $output_without_trailing_spaces = implode("\n", array_map('rtrim', $lines));
    // DX: when writing tests, specify `'dump'` as the expected string, and you
    // will get a handy dump of the actual output, ready for copy/pasting.
    if ($expected === 'dump') {
      var_dump($output_without_trailing_spaces);
    }
    self::assertSame($expected, $output_without_trailing_spaces, $filter);
  }

}
