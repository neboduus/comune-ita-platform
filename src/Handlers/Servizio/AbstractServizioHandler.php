<?php


namespace App\Handlers\Servizio;

use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

abstract class AbstractServizioHandler implements ServizioHandlerInterface
{
    /**
     * @var string
     */
    protected $callToActionText = '';

    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var LoggerInterface
     */
    protected $logger;


    public function __construct(TokenStorageInterface $tokenStorage, LoggerInterface $logger)
    {
        $this->tokenStorage = $tokenStorage;
        $this->logger = $logger;
    }

    /**
     * @return mixed
     */
    public function getCallToActionText()
    {
        return $this->callToActionText;
    }

    /**
     * @param $callToActionText
     * @return $this
     */
    public function setCallToActionText($callToActionText)
    {
        $this->callToActionText = $callToActionText;
        return $this;
    }
}
