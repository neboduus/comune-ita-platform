<?php

namespace App\EventListener;

use App\Services\InstanceService;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\User as CoreUser;

class JWTCreatedListener
{
  /**
   * @var RequestStack
   */
  private $requestStack;
  /**
   * @var InstanceService
   */
  private $instanceService;

  /**
   * @param RequestStack $requestStack
   * @param InstanceService $instanceService
   */
  public function __construct(RequestStack $requestStack, InstanceService $instanceService)
  {
    $this->requestStack = $requestStack;
    $this->instanceService = $instanceService;
  }

  /**
   * @param JWTCreatedEvent $event
   *
   * @return void
   */
  public function onJWTCreated(JWTCreatedEvent $event)
  {
    $payload = $event->getData();

    // Se ho richiesto il jwt per un utente in memory non ho l'id
    if ($event->getUser() instanceof CoreUser\User) {
      $payload['id'] = null;
    } else {
      $payload['id'] = $event->getUser()->getId();
    }
    $payload['tenant_id'] = $this->instanceService->getCurrentInstance()->getId();
    $event->setData($payload);
  }
}
