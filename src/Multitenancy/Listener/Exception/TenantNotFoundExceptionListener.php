<?php

namespace App\Multitenancy\Listener\Exception;

use App\Multitenancy\TenantNotFoundException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;

class TenantNotFoundExceptionListener
{
    /**
     * @var Session
     */
    private $session;

    private $requestStack;

    public function __construct(SessionInterface $session, RequestStack $requestStack)
    {
        $this->session = $session;
        $this->requestStack = $requestStack;
    }

    /**
     * @param ExceptionEvent $event
     * @throws \Exception
     */
    public function onKernelException(ExceptionEvent $event)
    {
        $exception = $event->getThrowable();
        if (!$exception instanceof TenantNotFoundException) {
            return;
        }

        throw new \Exception($exception->getMessage());

//        $this->session->getFlashBag()->add('error', $exception->getUserMessage());
//
//        $request = $event->getRequest()->duplicate(null, null, ['_controller' => DefaultController::class . '::index']);
//        $request->setMethod('GET');
//        $response = $event->getKernel()->handle($request, HttpKernelInterface::SUB_REQUEST, false);
//        $response->setStatusCode(Response::HTTP_BAD_REQUEST);
//        $event->setResponse($response);
    }
}
