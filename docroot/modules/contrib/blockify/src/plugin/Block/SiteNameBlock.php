<?php

namespace Drupal\blockify\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides block that displays the site's name.
 *
 * @Block(
 *   id = "site_name",
 *   admin_label = @Translation("Site name")
 * )
 */
class SiteNameBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = \Drupal::service('config.factory')
      ->get('system.site');

    return [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $config->get('name'),
      '#attributes' => [
        'class' => ['site-name'],
      ],
    ];
  }

}
