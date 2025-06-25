const path = require("path");

const urls = {
  migrations: "/acquia-migrate-accelerate/migrations",
  migrationsNeedsReview: "/acquia-migrate-accelerate/migrations/needs-review",
  migrationsCompleted: "/acquia-migrate-accelerate/migrations/completed",
  messagesUrl: "/acquia-migrate-accelerate/messages",
  preselect: "/acquia-migrate-accelerate/preselect",
  siteConfigDetail: "/acquia-migrate-accelerate/migrations/migration/b11ec035a0ea55f7bf0af42f84083be8-Site%20configuration",
  sharedStructureDetail:
    "/acquia-migrate-accelerate/migrations/migration/ef08b5721f28f83bc0f418fc5ae937a6-Shared%20structure%20for%20content%20items",
  articlesDetail:
    "/acquia-migrate-accelerate/migrations/migration/5e2f8ee473fdebc99fef4dc9e7ee3146-Article",
  usersDetail:
    "/acquia-migrate-accelerate/migrations/migration/dbdd6377389228728e6ab594c50ad011-User%20accounts"
};

const elements = {
  siteConfig: '[id^="b11ec035a0ea55f7bf0af42f84083be8"]',
  sharedContent: '[id^="ef08b5721f28f83bc0f418fc5ae937a6"]',
  filterFormats: '[id^="6aa6b4bd50a2b501f0c761ccf2a08227"]',
  users: '[id^="dbdd6377389228728e6ab594c50ad011"]',
  articles: '[id^="5e2f8ee473fdebc99fef4dc9e7ee3146"]',
  tagsTerms: '[id$="Tags taxonomy terms"]',
  vocabLocalizedTerms: '[id$="VocabLocalized taxonomy terms"]',
  vocabTranslateTerms: '[id$="VocabTranslate taxonomy terms"]',
  vocabFixedTerms: '[id$="VocabFixed taxonomy terms"]',
};

const common = {
  detailLink: "td .migration__title > a",
  detail: ".migrate-ui__migration_detail",
  msgCount: ".migration_row__message_count",
  // Waitforelement[...] commands only work with exactly specified args.
  // @see https://github.com/nightwatchjs/nightwatch/issues/2194
  mappingTabLink: {
    locateStrategy: 'xpath',
    selector: "//ul[contains(concat(' ', normalize-space(@class), ' '), ' tabs ')]//a[contains(text(), 'Mapping')]"
  },
  previewTabLink: {
    locateStrategy: 'xpath',
    selector: "//ul[contains(concat(' ', normalize-space(@class), ' '), ' tabs ')]//a[contains(text(), 'Preview')]"
  },
  detailsTabLink: {
    locateStrategy: 'xpath',
    selector: "//ul[contains(concat(' ', normalize-space(@class), ' '), ' tabs ')]//a[contains(text(), 'Details')]"
  },
  previewLinks: ".migration__preview__links",
  detailPreview: ".migration__preview",
  detailMapping: ".migration__mapping"
};

module.exports = {
  '@tags': ['acquia_migrate'],
  beforeEach(browser) {
    browser.globals.acquiaMigrateUrls = urls;
    browser.resizeWindow(1280, 800);
    browser.drupalInstall({
      setupFile: path.join(__dirname, '..', 'NightwatchTestSetupFile.php'),
    });
  },
  afterEach(browser) {
    browser.drupalUninstall();
  },
  'Ensure that site installs and is running without PHP fatals, etc.': function (browser) {
    browser.drupalRelativeURL('/').waitForElementVisible('body', 1000).assert.containsText('body', 'Log in');
  },
  'Ensure that the fixture exists by using the HTTP API to process a migration.': function (browser) {
    const migrationsURL = `${process.env.DRUPAL_TEST_BASE_URL}/acquia-migrate-accelerate/api/migrations`;
    const fetchOptions = {
      headers: {
        'accept': 'application/vnd.api+json',
      },
    };
    browser.drupalLoginAsAdmin(() => {
      // Before.
      browser
        .drupalRelativeURL('/admin/people')
        .waitForElementVisible('body', 1000)
        .expect.element('body').text.to.not.contain('Bob');
      // Process user migration using the HTTP API.
      browser.executeAsync(function (migrationsURL, fetchOptions, done) {
        /**
         * Follow the next links until the batch completes.
         *
         * @param {Response} response
         */
        function getNextResponse(response) {
          return response.json().then(json => (
            json.links.hasOwnProperty("next")
              ? fetch(json.links.next.href, fetchOptions).then(getNextResponse)
              : json.data.attributes.progressRatio
            ));
        }

        fetch(migrationsURL, fetchOptions)
          .then(res => res.json())
          .then(doc => doc.data.find(migration => migration.attributes.label == 'User accounts').links.import.href)
          .then(startUrl => fetch(startUrl, Object.assign({method: 'POST'}, fetchOptions)))
          .then(getNextResponse)
          .then(done);
      }, [migrationsURL, fetchOptions], function(result) {
        browser.assert.equal(result.value, 1, 'progressRatio is 1');
      });
      // After.
      browser
        .drupalRelativeURL('/admin/people')
        .waitForElementVisible('body', 1000)
        .expect.element('body').text.to.contain('Bob');

    });
  },
  'Ensure that the fixture exists by using the UI to process a migration.': function (browser) {
    browser.drupalLoginAsAdmin(() => {
      const { migrations } = urls;
      const { users } = elements;

      // Before.
      browser
        .drupalRelativeURL('/admin/people')
        .waitForElementVisible('body', 1000)
        .expect.element('body').text.to.not.contain('Bob');

      // Select default migrations.
      browser.selectDefaultMigrations();

      // Process user migration using the UI.
      browser
        .drupalRelativeURL(migrations)
        .waitForInitialImport()
        .waitForElementPresent(users)
        .startMigrationImport(users)
        .waitForElementNotPresent(users, 10000);
      // After.
      browser
        .drupalRelativeURL('/admin/people')
        .waitForElementVisible('body', 1000)
        .expect.element('body').text.to.contain('Bob');
    });
  },
  'Can mark a migration as complete': function (browser) {
    browser.drupalLoginAsAdmin(() => {
      const { migrations, migrationsNeedsReview, migrationsCompleted } = urls;
      const { sharedContent } = elements;
    // Select default migrations.
    browser.selectDefaultMigrations();

    // The initial migration should cause the "Shared structure for content items" migration to disappear from the "In
    // Progress" tab.
    browser
      .drupalRelativeURL(migrations)
      .waitForInitialImport()
      .waitForElementNotPresent(sharedContent);

    // That migration should now appear on the "Needs Review" tab.
    browser.drupalRelativeURL(migrationsNeedsReview)
      .assert.urlContains(migrationsNeedsReview)
      .waitForElementPresent(sharedContent);

    // Marking that migration as "Completed" should make it disappear from the "Needs Review" tab.
    const migrationPage = browser.page.MigrationPage();
    const sharedContentRow = migrationPage.section.sharedContentRow;
    migrationPage
      .waitForElementPresent(sharedContent)
      .clickOperation(sharedContentRow, '@dropdownComplete')
      .waitForModal('Mark as completed', 'ok')
      .waitForElementNotPresent(sharedContent);

    // That migration should now appear on the "Completed" tab.
    browser.drupalRelativeURL(migrationsCompleted)
      .assert.urlContains(migrationsCompleted)
      .waitForElementPresent(sharedContent);
    });
  },
  'Can click a migration in the list to see correct default tab': function (browser) {
    browser.drupalLoginAsAdmin(() => {
      const { migrations, articlesDetail, siteConfigDetail } = urls;
      const { articles, siteConfig } = elements;
      const { detailLink } = common;

      // Select default migrations.
      browser.selectDefaultMigrations();

      // Click on Shared structure for content items migration link.
      browser
        .drupalRelativeURL(migrations)
        .waitForInitialImport()
        .waitForElementPresent(siteConfig)
        .click(`${siteConfig} ${detailLink}`);

      browser
        .assert.urlContains(`${siteConfigDetail}`)
        .expect.url().to.not.contain('preview');

      // Click on Node Article migration link.
      browser
        .drupalRelativeURL(migrations)
        .waitForElementPresent(articles)
        .click(`${articles} ${detailLink}`);

      browser
        .assert.urlContains(`${articlesDetail}/preview`)
    });
  },
  'Can click a migration in the list to view details': function (browser) {
    browser.drupalLoginAsAdmin(() => {
      const { migrations, articlesDetail } = urls;
      const { articles } = elements;
      const { detail, detailLink, detailsTabLink } = common;

      // Select default migrations.
      browser.selectDefaultMigrations();

      // Click on Node Article migration link.
      browser
        .drupalRelativeURL(migrations)
        .waitForInitialImport()
        .waitForElementPresent(articles)
        .click(`${articles} ${detailLink}`);
      // On Article detail page.
      browser
        .assert.urlContains(`${articlesDetail}/preview`)
        // No specific indication found that can tell us that the page content
        // is fully loaded, so we'll wait for the page title instead of just
        // simply checking it.
        .waitForLoading()
        .waitForElementPresent(detail)
        .waitForElementPresent({
          locateStrategy: 'xpath',
          selector: "//h1[contains(text(), 'Migration: Article')]"
        })
        .waitForElementPresent(detailsTabLink)
        .click(detailsTabLink)
        // Dependency and reason for dependency are present.
        .assert.containsText('.migration__details_dependencies', 'User accounts')
        .assert.containsText('.migration__details_dependencies', 'd7_user')
        // Underlying Article migration is present.
        .assert.containsText('.migration__details_underlying', 'd7_field_instance:node:article');
    });
  },
  'Can view a migration mapping': function(browser) {
    browser.drupalLoginAsAdmin(() => {
      const { detailMapping } = common;
      const { articlesDetail, sharedStructureDetail } = urls;

      // Select default migrations.
      browser.selectDefaultMigrations();

      // Shared structure has no mapping available.
      browser
        .drupalRelativeURL(sharedStructureDetail)
        .waitForLoading()
        .waitForElementPresent(detailMapping)
        .assert.containsText(
          detailMapping,
          'No Mapping information available.',
        );

      browser
        .drupalRelativeURL(articlesDetail)
        .waitForLoading()
        .waitForElementPresent(detailMapping)
        .waitForElementPresent(`${detailMapping} table`)
        .assert.containsText(`${detailMapping} thead`, 'Source Field')
        .assert.containsText(`${detailMapping} tbody tr:first-child`, 'tnid');
      });
    },
    'Can view a migration preview after importing supporting configuration, can still preview after importing': function(browser) {
      browser.drupalLoginAsAdmin(() => {
        const { users, articles, tagsTerms, vocabFixedTerms, vocabLocalizedTerms, vocabTranslateTerms } = elements;
        const { detail, detailPreview, previewTabLink, previewLinks } = common;
        const {
          migrations,
          articlesDetail,
          sharedStructureDetail,
          usersDetail,
        } = urls;

      // Select default migrations.
      browser.selectDefaultMigrations();

      // Shared structure is not previewable.
      browser
        .drupalRelativeURL(sharedStructureDetail)
        .waitForLoading()
        .waitForElementPresent(detail)
        .assert.containsText(
          'h1',
          'Migration: Shared structure for content items',
        )
        .waitForElementPresent('.tabs')
        .expect.element('.tabs')
        .text.to.not.contain('Preview');

      // Articles cannot be previewed because of unmet dependencies.
      browser
        .drupalRelativeURL(articlesDetail)
        .waitForLoading()
        .waitForElementPresent(detail)
        .assert.containsText('h1', 'Migration: Article')
        .assert.containsText(previewTabLink, 'Preview')
        .assert.containsText('.tabs li:first-child', 'Preview')
        .click(previewTabLink)
        .assert.containsText(
          detailPreview,
          'Not all supporting configuration has been processed yet',
        );

      // Users cannot be previewed because of unmet dependencies.
      browser
        .drupalRelativeURL(usersDetail)
        .waitForLoading()
        .waitForElementPresent(detail)
        .assert.containsText('h1', 'Migration: User accounts')
        .assert.containsText(previewTabLink, 'Preview')
        .click(previewTabLink)
        .assert.containsText(
          detailPreview,
          'Not all supporting configuration has been processed yet',
        );

      browser
        .drupalRelativeURL(migrations)
        // Visiting the dashboard for the first time will trigger the initial
        // import, which amongst others will import the "Shared structure for
        // content items" and "Filter format configuration" migrations, but
        // also supporting configuration migration plugins within the
        // "Article" and "User accounts" migrations.
        .waitForInitialImport()
        // Completed migration can no longer be previewed.
        .waitForElementPresent(users)
        .startMigrationImport(users)
        .waitForElementNotPresent(users, 10000);

      // Users can now be previewed.
      browser
        .drupalRelativeURL(`${usersDetail}/preview`)
        .waitForLoading()
        .waitForElementPresent(detail)
        .assert.containsText(detailPreview, 'admin')
        .assert.elementPresent(`${previewLinks} .next`)
        .assert.not.elementPresent(`${previewLinks} .prev`)
        .click(`${previewLinks} .next`)
        .assert.elementPresent(`${previewLinks} .prev`)
        .assert.containsText(detailPreview, 'Odo');

      // Article now has preview.
      // @todo: these assertions only assert the raw data. That is good, but better would be to also assert the <iframe>
      // because that'd allow us to assert the 'Necessary data not yet migrate' string in there for e.g. Tags.
      browser
        .drupalRelativeURL(`${articlesDetail}/preview`)
        .waitForLoading()
        .waitForElementPresent(detail)
        .assert.containsText(
          detailPreview,
          'The thing about Deep Space 9',
        );

      // But article is not yet importable.
      // @todo Assert that the "import" action does not appear in the dropdown. We need waitForOperation ~ clickOperation.

      // Import dependencies of articles.
      browser
        .drupalRelativeURL(migrations)
        .waitForLoading()
        // All four vocabularies that Articles depends on; users have already been imported.
        .waitForElementPresent(tagsTerms)
        .waitForElementPresent(vocabLocalizedTerms)
        .waitForElementPresent(vocabTranslateTerms)
        .waitForElementPresent(vocabFixedTerms)
        .startMigrationImport(tagsTerms)
        .waitForElementNotPresent(tagsTerms, 10000)
        .startMigrationImport(vocabLocalizedTerms)
        .waitForElementNotPresent(vocabLocalizedTerms, 10000)
        .startMigrationImport(vocabTranslateTerms)
        .waitForElementNotPresent(vocabTranslateTerms, 10000)
        .startMigrationImport(vocabFixedTerms)
        .waitForElementNotPresent(vocabFixedTerms, 10000);

      // Migrate articles.
      browser
        .drupalRelativeURL(migrations)
        .waitForLoading()
        .waitForElementPresent(articles)
        .startMigrationImport(articles)
        .waitForElementNotPresent(articles, 10000);

      // Articles can still be previewed after the migration has been completed.
      browser
        .drupalRelativeURL(`${articlesDetail}/preview`)
        .waitForLoading()
        .waitForElementPresent(detail)
        .assert.containsText(
          detailPreview,
          'The thing about Deep Space 9',
      );
    });
  },
  'Message app loads separately from dashboard': function (browser) {
    browser.drupalLoginAsAdmin(() => {
      const { migrations, messagesUrl } = urls;

      browser
        .drupalRelativeURL(migrations)
        .assert.containsText('h1', 'Migrations')
        .assert.attributeContains('#decoupled-page-root', 'data-basepath', migrations);

      browser
        .drupalRelativeURL(messagesUrl)
        .assert.containsText('h1', 'Messages')
        .assert.attributeContains('#decoupled-page-root', 'data-basepath', messagesUrl);
    });
  },
  'Message app displays error message': function (browser) {
    const { migrations, messagesUrl } = urls;

    browser.drupalLoginAsAdmin(() => {
      browser.drupalRelativeURL(messagesUrl)
        .waitForLoading()
        .assert.containsText('.messages__list', 'No Messages')
        .expect.element('.messages__list').text.to.not.contain('Filter format configuration');

      // Select default migrations.
      browser.selectDefaultMigrations();

      // We only have to wait for the initial import to complete.
      // Filter format configurations will emit an error because of php_filter;
      // and that won't be fixed (ever?..).
      browser.drupalRelativeURL(migrations)
        .waitForInitialImport();

      browser.drupalRelativeURL(messagesUrl)
        .waitForLoading()
        .assert.containsText('.messages__list', 'Filter format configuration')
        .expect.element('.messages__list').text.to.not.contain('No Messages');
    });
  },
  'Message app can filter errors': function (browser) {
    const { filterFormats, sharedContent } = elements;
    const { msgCount } = common;
    const { migrations, migrationsNeedsReview } = urls;

    browser.drupalLoginAsAdmin(() => {
      // Select default migrations.
      browser.selectDefaultMigrations();

      browser.drupalRelativeURL(migrations)
        // We only have to wait for the initial import to complete, and we will
        // have a Filter format configuration error.
        .waitForLoading()
        .assert.containsText(`${filterFormats} ${msgCount}`, '0')
        .assert.containsText(`${sharedContent} ${msgCount}`, '0')
        .waitForInitialImport()
        // .assert.containsText(`${filterFormats} ${msgCount}`, '1')
        .waitForElementNotPresent(sharedContent);

      // Assert that migrations are on Needs Review tab and display errors.
      browser.drupalRelativeURL(migrationsNeedsReview)
        .assert.urlContains(migrationsNeedsReview)
        .waitForElementPresent(sharedContent)
        .getText(`${sharedContent} ${msgCount}`, function(result) {
          this.assert.equal(result.value, '4')
        });

      // Visit messages app with Shared structure filter applied.
      browser
        .click(`${sharedContent} ${msgCount} a`)
        .waitForLoading()
        .assert.value('select[name=sourceMigration]', 'ef08b5721f28f83bc0f418fc5ae937a6-Shared structure for content items')
        .assert.containsText('.messages__list', 'Shared structure for content items')
        .expect.element('.messages__list').text.to.not.contain('Filter format configuration');

      // Set filterFormat filter.
      browser
        .click('select[name=sourceMigration] option[value^="6aa6b4bd50a2b501f0c761ccf2a08227"]')
        .waitForElementNotPresent({
          locateStrategy: 'xpath',
          selector: "//*[contains(concat(' ', normalize-space(@class), ' '), ' messages__list ')]//*[contains(text(), 'Shared structure for content items')]"
        })
        .assert.containsText('.messages__list', 'Filter format configuration');

      // Clear filters.
      browser
        .click('.messages__filters .button')
        .waitForElementPresent({
          locateStrategy: 'xpath',
          selector: "//*[contains(concat(' ', normalize-space(@class), ' '), ' messages__list ')]//*[contains(text(), 'Filter format configuration')]"
        })
        .assert.containsText('.messages__list', 'Shared structure for content items');
    });
  }
};
