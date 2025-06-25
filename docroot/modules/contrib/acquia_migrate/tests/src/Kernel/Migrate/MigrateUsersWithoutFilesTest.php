<?php

namespace Drupal\Tests\acquia_migrate\Kernel\Migrate;

use Drupal\acquia_migrate\MigrationFingerprinter;
use Drupal\Core\Logger\RfcLoggerTrait;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Plugin\Migration;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Psr\Log\LoggerInterface;

/**
 * Verifies that executing user migration without files does not logs SQL error.
 *
 * @group acquia_migrate
 * @group acquia_migrate__core
 */
class MigrateUsersWithoutFilesTest extends MigrateDrupal7TestBase {

  use UserCreationTrait {
    createUser as drupalCreateUser;
  }

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'acquia_migrate',
    'image',
    'node',
    'syslog',
    'migmag_process',
  ];

  /**
   * @var \Drupal\Tests\acquia_migrate\Kernel\Migrate\TestLogger
   */
  protected $testLogger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('file');
    $this->installSchema('acquia_migrate', [MigrationFingerprinter::FLAGS_TABLE]);
    $this->installSchema('file', ['file_usage']);

    $this->testLogger = new TestLogger();
    $this->container->get('logger.factory')->addLogger($this->testLogger);

    // Whenever a migraton is started using AM:A, user one already exists. If we
    // don't do this, then
    // \Drupal\acquia_migrate\Plugin\migrate\destination\AcquiaMigrateUser would
    // get in the way: it'd cause user one to be created without name/mail/init.
    $this->drupalCreateUser([], NULL, FALSE, ['uid' => 1]);
  }

  /**
   * Tests user migration with a user picture, without executed file migration.
   */
  public function testUserMigration() {
    $this->sourceDatabase->insert('file_managed')
      ->fields([
        'fid' => 100,
        'uid' => 3,
        'filename' => 'cube_0.jpeg',
        'uri' => 'public://cube.jpeg',
        'filemime' => 'image/jpeg',
        'filesize' => 3620,
        'status' => 1,
        'timestamp' => '1500000000',
      ])
      ->execute();
    $this->sourceDatabase->insert('file_usage')
      ->fields([
        'fid' => 100,
        'module' => 'file',
        'type' => 'user',
        'id' => 3,
        'count' => 1,
      ])
      ->execute();
    $this->sourceDatabase->update('users')
      ->condition('uid', 3)
      ->fields(['picture' => 100])
      ->execute();

    $this->executeMigrations([
      'd7_user_role',
      'd7_view_modes:user',
      'd7_field:user',
      'd7_field_instance:user:user',
      'd7_field_instance_widget_settings:user:user',
      'd7_field_formatter_settings:user:user',
      'user_picture_field',
      'user_picture_field_instance',
      'user_picture_entity_display',
      'user_picture_entity_form_display',
    ]);

    $this->startCollectingMessages();
    $this->testLogger->setLogging(TRUE);
    $this->executeMigration('d7_user');
    $this->assertNoMigrationWarningOrErrorMessages();
    $this->assertNoDangerousLogsPresent([RfcLogLevel::INFO]);

    // Tests that the file stub has been created.
    $migration_manager = $this->container->get('plugin.manager.migration');
    assert($migration_manager instanceof MigrationPluginManagerInterface);
    $file_migration = $migration_manager->createInstance('d7_file');
    assert($file_migration instanceof Migration);
    $destination_row = $file_migration->getIdMap()->getRowBySource(['fid' => 100]);
    $this->assertEquals([
      'source_ids_hash' => 'b5cac242e7eee9f595334f606f380398e615f3fb9d281b63bb13c6f7fce3abcd',
      'sourceid1' => 100,
      'destid1' => 100,
      'source_row_status' => MigrateIdMapInterface::STATUS_NEEDS_UPDATE,
      'rollback_action' => MigrateIdMapInterface::ROLLBACK_DELETE,
      'last_imported' => 0,
      'hash' => '',
    ], $destination_row);
  }

  /**
   * Asserts that no migration messages were displayed.
   */
  protected function assertNoMigrationWarningOrErrorMessages() {
    $migrate_messages = [];
    foreach ($this->migrateMessages as $type => $messages) {
      foreach ($messages as $message) {
        $migrate_messages[$type][] = (string) $message;
      }
    }

    foreach (['warning', 'error'] as $message_type) {
      $this->assertEmpty(
        $this->migrateMessages[$message_type] ?? [],
        sprintf('Unexpected %s migration messages logged during the migration: "%s"', $message_type, implode('", "', $migrate_messages[$message_type] ?? [])));
    }
  }

  /**
   * Asserts that no "dangerous" log entries are present.
   *
   * Automatically ignores messages sent to acquia_migrate_profiling_statistics.
   *
   * @param array $log_levels_to_check
   *   Log levels to check: see RfcLogLevel.
   */
  protected function assertNoDangerousLogsPresent(array $log_levels_to_check = []) {
    $log_levels_to_check = !empty($log_levels_to_check)
      ? $log_levels_to_check
      : [
        RfcLogLevel::EMERGENCY,
        RfcLogLevel::ALERT,
        RfcLogLevel::CRITICAL,
        RfcLogLevel::ERROR,
        RfcLogLevel::WARNING,
        RfcLogLevel::NOTICE,
      ];
    krsort($log_levels_to_check);
    $all_levels = [];
    foreach (RfcLogLevel::getLevels() as $level => $level_label) {
      $all_levels[$level] = (string) $level_label;
    }

    $log_filtered = [];
    foreach ($this->testLogger->getLog() as $loglevel => $messages_and_contexts) {
      if (!in_array($loglevel, $log_levels_to_check, TRUE)) {
        continue;
      }
      foreach ($messages_and_contexts as $delta => $message_and_context) {
        // AMA intentionally logs profiling statistics: that can never be a
        // reason to fail a test.
        if ($message_and_context['context']['channel'] === 'acquia_migrate_profiling_statistics') {
          continue;
        }
        $context = print_r($message_and_context['context'], TRUE);
        $log_filtered[$loglevel][$delta] = "Message: {$message_and_context['message']}; Context: $context";
      }
    }

    foreach ($log_levels_to_check as $level) {
      $level_label = $all_levels[$level] ?? 'Uknown level';
      $this->assertEmpty(
        $log_filtered[$level] ?? [],
        sprintf('Unexpected "%s" messages logged during the migration: "%s"', $level_label, implode('", "', $log_filtered[$level] ?? [])));

    }
  }

}

/**
 * TestLogger for user migration test.
 */
class TestLogger implements LoggerInterface {

  use RfcLoggerTrait;

  /**
   * The log.
   *
   * @var mixed[][][]
   */
  protected $logs = [];

  /**
   * The state of this logger.
   *
   * @var bool
   */
  protected $active = FALSE;

  /**
   * Sets the state of the logger.
   *
   * @param bool $state
   *   The new state of the test logger.
   */
  public function setLogging(bool $state): void {
    $this->active = $state;
  }

  /**
   * Clears the log.
   */
  public function clearLog(): void {
    $this->logs = [];
  }

  /**
   * Returns the log.
   *
   * @return mixed[][][]
   *   The log entries grouped (and keyed by) their log level. An entry is an
   *   array of a message (string) and a context (which is an array).
   */
  public function getLog() {
    return $this->logs;
  }

  /**
   * {@inheritdoc}
   */
  public function log($level, $message, array $context = []) {
    if ($this->active) {
      $this->logs[$level][] = [
        'message' => $message,
        'context' => $context,
      ];
    }
  }

}
