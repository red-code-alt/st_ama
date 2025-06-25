<?php

namespace Drupal\blockify\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;

/**
 * Provides block that displays the site's slogan.
 *
 * @Block(
 *   id = "theme_logo",
 *   admin_label = @Translation("Theme logo")
 * )
 */
class ThemeLogoBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = \Drupal::service('config.factory')
      ->get('system.theme.global');

    // Work out which setting to use for the logo.
    $url = $config->get('logo.url');
    if (empty($url)) {
      $path = $config->get('logo.path');
      $path = '/' . ltrim($path, '/');
      $url = Url::fromUserInput($path)->toString();
    }

    return [
      '#type' => 'html_tag',
      '#tag' => 'img',
      '#attributes' => [
        'src' => $url,
        'class' => ['theme-logo'],
      ],
    ];
  }

}
