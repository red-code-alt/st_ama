<?php

namespace Drupal\acquia_migrate\DecoupledPages;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\decoupled_pages\DataProviderInterface;
use Drupal\decoupled_pages\Dataset;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Dynamically sets the base path data attribute for the loaded decoupled page.
 */
class DataProvider implements DataProviderInterface {

  /**
   * The tracking API key.
   *
   * @var string
   */
  protected $key;

  /**
   * Path to the installed module.
   *
   * @var string
   */
  protected $modulePath;

  /**
   * The config.factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $configFactory;

  /**
   * DataProvider constructor.
   *
   * @param string $tracking_api_key
   *   The tracking API key.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config.factory service.
   */
  public function __construct(string $tracking_api_key, ModuleHandlerInterface $module_handler, ConfigFactoryInterface $config_factory) {
    $this->modulePath = $module_handler->getModule('acquia_migrate')->getPath();
  }

  /**
   * {@inheritdoc}
   */
  public function getData(Route $route, Request $request): Dataset {
    $cacheability = new CacheableMetadata();
    return Dataset::cacheVariable($cacheability->setCacheMaxAge(86400), [
      'module-path' => $this->modulePath,
    ]);
  }

}
