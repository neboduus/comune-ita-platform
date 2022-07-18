<?php

namespace AppBundle\EventListener;

use AppBundle\Services\InstanceService;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Symfony\Component\HttpFoundation\RequestStack;

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
    $payload['id'] = $event->getUser()->getId();
    $payload['tenant_id'] = $this->instanceService->getCurrentInstance()->getId();
    $event->setData($payload);
  }
}
