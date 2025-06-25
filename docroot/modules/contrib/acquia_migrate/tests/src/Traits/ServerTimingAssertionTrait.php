<?php

namespace Drupal\Tests\acquia_migrate\Traits;

use Drupal\acquia_migrate\Timers;
use Psr\Http\Message\MessageInterface;

/**
 * Provides methods to assert the Server-Timing response headers.
 *
 * @internal
 */
trait ServerTimingAssertionTrait {

  /**
   * Asserts that the response contains a Server-Timing header for an AMA timer.
   *
   * @param \Psr\Http\Message\MessageInterface $response
   *   A  response whose Server-Timing response headers to assert.
   * @param string $timer_name
   *   A timer; one of the constants on \Drupal\acquia_migrate\Timers.
   * @param array $expected_parameters
   *   The parameters to expect in the timer's Server-Timing response header's
   *   description.
   * @param string|null $hit_or_miss
   *   If not a HIT/MISS timer, NULL (default), otherwise 'HIT' or 'MISS.
   *
   * @throws \PHPUnit\Framework\ExpectationFailedException
   *
   * @see \Drupal\acquia_migrate\Timers
   * @see https://w3c.github.io/server-timing/#the-server-timing-header-field
   */
  protected function assertServerTiming(MessageInterface $response, string $timer_name, array $expected_parameters, ?string $hit_or_miss = NULL) {
    assert(in_array($hit_or_miss, [NULL, 'HIT', 'MISS'], TRUE));
    $header_values = $response->getHeader('Server-Timing');
    foreach ($header_values as $header_value) {
      [$metric_name,, $description] = explode(';', $header_value);

      if ($metric_name === $timer_name) {
        $expected_description = vsprintf(Timers::getDescription($timer_name), $expected_parameters);
        if ($hit_or_miss) {
          $expected_description = str_replace('HIT_OR_MISS', $hit_or_miss, $expected_description);
        }
        // Omit the 'desc=' prefix and the enclosing quotes.
        $description = trim(substr($description, 6), '"');
        $this->assertSame($expected_description, $description);
      }
    }
  }

}
