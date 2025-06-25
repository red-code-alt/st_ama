<?php

namespace Drupal\test_database_drivers\Driver\sqlite;

use Drupal\Core\Database\Driver\sqlite\Connection as SqliteConnectionDeprecated;
use Drupal\mysql\Driver\Database\sqlite\Connection as SqliteConnection;
use Drupal\test_database_drivers\TestDatabaseDriverTrait;

if (class_exists(SqliteConnection::class)) {
  class Connection extends SqliteConnection {

    use TestDatabaseDriverTrait;

  }
}
else {
  class Connection extends SqliteConnectionDeprecated {

    use TestDatabaseDriverTrait;

  }
}
