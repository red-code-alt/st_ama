<?php

namespace Drupal\blockify\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides block that displays the site's slogan.
 *
 * @Block(
 *   id = "site_slogan",
 *   admin_label = @Translation("Site slogan")
 * )
 */
class SiteSloganBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = \Drupal::service('config.factory')
      ->get('system.site');

    return [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $config->get('slogan'),
      '#attributes' => [
        'class' => ['site-slogan'],
      ],
    ];
  }

}
