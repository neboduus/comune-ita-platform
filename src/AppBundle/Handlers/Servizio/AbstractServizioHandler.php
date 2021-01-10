<?php


namespace AppBundle\Handlers\Servizio;

use AppBundle\Entity\CPSUser;
use AppBundle\Entity\Ente;
use AppBundle\Entity\Servizio;
use AppBundle\Utils\BrowserParser;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\User\UserInterface;
use WhichBrowser\Parser;

abstract class AbstractServizioHandler implements ServizioHandlerInterface
{
  /**
   * @var string
   */
  protected $callToActionText = '';

  /**
   * @var TokenStorage
   */
  protected $tokenStorage;

  /**
   * @var LoggerInterface
   */
  protected $logger;

  /**
   * @var UrlGeneratorInterface
   */
  protected $router;


  /**
   * AbstractServizioHandler constructor.
   * @param TokenStorage $tokenStorage
   * @param LoggerInterface $logger
   * @param UrlGeneratorInterface $router
   */
  public function __construct(TokenStorage $tokenStorage, LoggerInterface $logger, UrlGeneratorInterface $router)
  {
    $this->tokenStorage = $tokenStorage;
    $this->logger = $logger;
    $this->router = $router;
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

  /**
   * @return CPSUser|UserInterface|null
   */
  protected function getUser()
  {
    if (null === $token = $this->tokenStorage->getToken()) {
      return null;
    }

    if (!is_object($user = $token->getUser())) {
      return null;
    }

    return $user;
  }

  /**
   * @param Servizio $servizio
   * @param Ente $ente
   * @throws ForbiddenAccessException
   */
  public function canAccess(Servizio $servizio, Ente $ente)
  {
    if ($servizio->getStatus() === Servizio::STATUS_CANCELLED){
      throw new ForbiddenAccessException('servizio.cancellato');
    }

    if ($servizio->getStatus() === Servizio::STATUS_SUSPENDED){
      throw new ForbiddenAccessException('servizio.sospeso');
    }

    if ($servizio->getStatus() === Servizio::STATUS_SCHEDULED){
      if (!$servizio->getScheduledFrom() instanceof \DateTime || !$servizio->getScheduledTo() instanceof \DateTime){
        throw new \RuntimeException('Invalid schedule configuration for service ' . $servizio->getSlug());
      }

      if ($servizio->getScheduledTo() < $servizio->getScheduledFrom()){
        throw new \RuntimeException('Invalid schedule configuration for service ' . $servizio->getSlug());
      }

      $now = new \DateTime();
      $format = 'd/m/Y H:i';
      if ($now < $servizio->getScheduledFrom()){
        throw new ForbiddenAccessException('servizio.schedulato', ['%from%' => $servizio->getScheduledFrom()->format($format), '%to%' => $servizio->getScheduledTo()->format($format)]);
      }
      if ($now > $servizio->getScheduledTo()){
        throw new ForbiddenAccessException('servizio.schedulato', ['%from%' => $servizio->getScheduledFrom()->format($format), '%to%' => $servizio->getScheduledTo()->format($format)]);
      }
    }


    // il servizio ha un servizio parent? controlla le pratiche di $this->getUser() per $servizio->getParent() (o getParents()??)

    // il servizio ha una constraint specifica? user.getEta > 18 && user.getIsee <= 40000
    // foreach(servizio->getConstrints() as $label => $expression){
    // throw new ForbiddenAccessException('Il servizio ' . $servizio->getName() . ' non è accessibile per ' . $label);
    // }

  }
}
