<?php

namespace Drupal\paragraphs_migration\Plugin\migrate\source\d7;

use Drupal\field\Plugin\migrate\source\d7\Field;
use Drupal\migrate\Plugin\migrate\source\SourcePluginBase;

/**
 * Base class for working around SQL plugin count cacheability issues.
 *
 * Drupal core 9.1 is the oldest minor which got the fix - but every release
 * prior to 9.1.9 does not have cacheable source plugin counts. Core 9.2
 * alpha2 was the first release with the fix.
 *
 * @todo Remove after Drupal core 8.x, and 9.0.x and 9.1.x are out of support.
 *
 * @see https://drupal.org/i/3190815
 */
if (
  (
    version_compare(\Drupal::VERSION, '9.1.9', 'ge') &&
    version_compare(\Drupal::VERSION, '9.2', 'lt')
  ) ||
  version_compare(\Drupal::VERSION, '9.2.0-alpha2', 'ge')
) {
  /**
   * Sql source plugin count caching is supported.
   */
  abstract class MultifieldTypeSqlSourceBase extends Field {

    /**
     * {@inheritdoc}
     */
    protected function doCount() {
      return (int) $this->initializeIterator()->count();
    }

  }
}
else {
  /**
   * Sql source plugin count caching is broken.
   */
  abstract class MultifieldTypeSqlSourceBase extends Field {

    /**
     * {@inheritdoc}
     */
    public function count($refresh = FALSE) {
      return SourcePluginBase::count($refresh);
    }

    /**
     * {@inheritdoc}
     */
    protected function doCount() {
      return SourcePluginBase::doCount();
    }

  }
}
