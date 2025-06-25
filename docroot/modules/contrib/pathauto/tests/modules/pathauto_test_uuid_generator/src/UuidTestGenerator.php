<?php

namespace Drupal\pathauto_test_uuid_generator;

use Drupal\Component\Uuid\Php as DefaultGenerator;
use Drupal\Core\State\StateInterface;

/**
 * A predictable UUID generator.
 */
class UuidTestGenerator extends DefaultGenerator {

  /**
   * Key of the state storing how many times a predictable UUID was generated.
   *
   * @const string
   */
  const LAST_SUFFIX_STATE_KEY = 'pathauto_test_uuid_generator.last';

  /**
   * Key of the state where the watches classes are stored.
   *
   * @const string
   */
  const WATCHED_CLASSES_STATE_KEY = 'pathauto_test_uuid_generator.watch';

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs a UuidTestGenerator instance.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   */
  public function __construct(StateInterface $state) {
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public function generate() {
    if (empty($watch = $this->state->get(self::WATCHED_CLASSES_STATE_KEY, []))) {
      return parent::generate();
    }

    $watched_classes = array_reduce((array) $watch, function (array $carry, string $fqcn) {
      $name_parts = explode('\\', $fqcn);
      $carry[end($name_parts)] = $fqcn;
      return $carry;
    }, []);

    $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
    $trace_name_parts = explode(DIRECTORY_SEPARATOR, $trace[0]['file'] ?? '');
    $trace_name = basename(end($trace_name_parts), '.php');

    if (!in_array($trace_name, array_keys($watched_classes))) {
      return parent::generate();
    }

    try {
      $suspicious_class_location = (new \ReflectionClass($watched_classes[$trace_name]))->getFileName();
    }
    catch (\ReflectionException $e) {
      return parent::generate();
    }

    if ($suspicious_class_location === $trace[0]['file']) {
      $current = $this->state->get(self::LAST_SUFFIX_STATE_KEY, 0);
      $current++;
      $this->state->set(self::LAST_SUFFIX_STATE_KEY, $current);
      return 'uuid' . $current;
    }

    return parent::generate();
  }

}
