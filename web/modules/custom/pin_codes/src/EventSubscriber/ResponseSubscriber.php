<?php

namespace Drupal\pin_codes\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\Event;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class ResponseSubscriber.
 */
class ResponseSubscriber implements EventSubscriberInterface {

  /**
   * Drupal\user\PrivateTempStoreFactory definition.
   *
   * @var \Drupal\user\PrivateTempStoreFactory
   */
  protected $userPrivateTempstore;

  /**
   * Constructs a new ResponseSubscriber object.
   */
  public function __construct(PrivateTempStoreFactory $user_private_tempstore) {
    $this->userPrivateTempstore = $user_private_tempstore;
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = ['alterResponse'];

    return $events;
  }

  /**
   * Redirect if 403.
   *
   * @param FilterResponseEvent $event
   *   The route building event.
   */
  public function alterResponse(FilterResponseEvent $event) {
    if ($event->getResponse()->getStatusCode() == 403) {
      $event->setResponse(new RedirectResponse('/'));
    }
  }

}
