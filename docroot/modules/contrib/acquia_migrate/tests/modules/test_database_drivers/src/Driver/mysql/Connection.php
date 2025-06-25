<?php

namespace Drupal\test_database_drivers\Driver\mysql;

use Drupal\Core\Database\Driver\mysql\Connection as MysqlConnectionDeprecated;
use Drupal\mysql\Driver\Database\mysql\Connection as MysqlConnection;
use Drupal\test_database_drivers\TestDatabaseDriverTrait;

if (class_exists(MysqlConnection::class)) {
  class Connection extends MysqlConnection {

    use TestDatabaseDriverTrait;

  }
}
else {
  class Connection extends MysqlConnectionDeprecated {

    use TestDatabaseDriverTrait;

  }
}
