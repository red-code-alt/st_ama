<?php

namespace Drupal\test_database_drivers;

/**
 * Trait for alternative database drivers extending other drivers.
 */
trait TestDatabaseDriverTrait {

  /**
   * The namespace of the current connection's parent class.
   *
   * @var string
   */
  protected $parentNamespace;

  /**
   * {@inheritdoc}
   */
  public function getDriverClass($class) {
    $original_namespace = $this->connectionOptions['namespace'];
    $this->connectionOptions['namespace'] = $this->getParentNamespace();
    $driver_class = parent::getDriverClass($class);
    $this->connectionOptions['namespace'] = $original_namespace;
    return $driver_class;
  }

  /**
   * Returns the namespace of the current connection's parent class.
   *
   * @return string
   *   The namespace of the current connection's parent class.
   */
  private function getParentNamespace(): string {
    if (!isset($this->parentNamespace) || !is_string($this->parentNamespace)) {
      $parent_class = get_parent_class();
      $this->parentNamespace = implode(
        '\\',
        explode('\\', $parent_class, -1)
      );
    }
    return $this->parentNamespace;
  }

}
