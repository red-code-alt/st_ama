<?php

namespace Drupal\securelogin;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Listens for login to the 403 page and redirects to destination.
 *
 * This class is now unused because related core bugs have been fixed.
 */
class SecureLoginResponseSubscriber implements EventSubscriberInterface {

  /**
   * Redirects login attempts on already-logged-in session to the destination.
   */
  public function onRespond(ResponseEvent $event): void {
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[KernelEvents::RESPONSE][] = ['onRespond', 2];
    return $events;
  }

}
