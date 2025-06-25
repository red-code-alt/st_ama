# About

This document is about how to maintain and extend the Drupal 7 database fixture
and the related file assets that are used by Workbench Moderation Migrate's
PHPUnit tests.


## Requirements

- A Drupal 8|9 codebase for the database import-export script.
- (Recommended for bulding the Drupal 7 codebase) Drush `8`. Drush 8 is the last
  version compatible with Drupal 7.
- [Smart DB Tools][1]


## Create the Drupal 7 instance that represents the db and file fixtures

### Codebase

- Change directory to the module's root and build the source Drupal 7 project:
  ```
  drush make ./tests/fixtures/wm.make.yml ./tests/fixtures/d7
  ```

- Still from module's root, copy
  `./tests/fixtures/d7/sites/default/default.settings.php` to
  `./tests/fixtures/d7/sites/default/settings.php`, add some hash salt and
  define the database connection.


### Database

The next steps are almost the same as in the
[Generating database fixtures for D8 Migrate tests][2] documentation and require
a Drupal 8|9 instance. You can skip the _Set up Drupal 6 / 7 installation that
uses your test database_ section since it is replaced by the make files
we provide.

- Make sure that the source database is empty.
- Install the destination Drupal site. This is required for using the DB Tools
  application (even for the one built in Drupal core).
- [Define a database connection to your empty database][3] in your Drupal 8|9
  `settings.php`:
  ```
    $databases['fixture_connection']['default'] = array (
      'database' => 'wb_source',
      'username' => 'devuser',
      'password' => 'devpassword',
      'prefix' => '',
      'host' => 'localhost',
      'port' => '3306',
      'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
      'driver' => 'mysql',
    );
    ```

- Import the source fixture into this database.
  From your Drupal 8|9 project root, run:
  ```
  php modules/contrib/smart_db_tools/scripts/smart-db-tools.php import\
   --database fixture_connection\
   [path-to-workbench_moderation_migrate]/tests/fixtures/wm-drupal7.php
  ```

- [Add a row for uid 0 to {users} table manually][4].
  ```
  drush -u 1 sql-query\
   "INSERT INTO users\
   (name, pass, mail, theme, signature, language, init, timezone)\
   VALUES ('', '', '', '', '', '', '', '')" && \
  drush -u 1 sql-query "UPDATE users SET uid = 0 WHERE name = ''"
  ```


##  Log in to your test site and make the necessary changes

These necessary changes could be for instance:
- Someone found a bug that can be reproduced with a well-prepared source data,
  thus while we fix it, we also are able to create a test:

  In this case, you need to add a new node with the body text that causes the
  error.

- Drupal 7 core, or one of the contrib modules that the Drupal 7 fixture uses
  got a new release, and we have to update the fixture database (and even the
  Drush make file).

  In this case, after that the corresponding component was updated, we have to
  run the database updates.

### Admin (uid = 1) user's credentials:

- Username is `admin`
- Password is `x`

If you need to add or update a contrib module, or update core: please don't
forget to update the drush make file as well!


## Export the modifications you made

- Export the Drupal 7 database to the fixture file:
  From your Drupal 8|9 project root, run:
  ```
  php modules/contrib/smart_db_tools/scripts/smart-db-tools.php dump\
   --database fixture_connection\
   --split-destination [path-to-media_migration]/tests/fixtures/wm-drupal7.php
  ```

- You can remove the untracked and ignored files if you think so:

  `git clean -fdx ./tests/fixtures/`


[1]: https://www.drupal.org/project/smart_db_tools
[2]: https://www.drupal.org/node/2583227
[3]: https://www.drupal.org/node/2583227#s-importing-data-from-the-fixture-to-your-testdatabase
[4]: https://www.drupal.org/node/1029506
