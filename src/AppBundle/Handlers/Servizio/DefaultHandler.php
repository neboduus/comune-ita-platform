<?php

namespace AppBundle\Handlers\Servizio;

use AppBundle\Entity\CPSUser;
use AppBundle\Entity\Ente;
use AppBundle\Entity\Servizio;
use AppBundle\Form\PraticaFlowRegistry;
use AppBundle\Services\UserSessionService;
use AppBundle\Utils\BrowserParser;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;

class DefaultHandler extends AbstractServizioHandler
{
  protected $errorMessage = "Errore inatteso: contattare il supporto";

  /** @var $em EntityManagerInterface */
  protected $em;

  /** @var PraticaFlowRegistry */
  protected $flowRegistry;

  /** @var SessionInterface */
  protected $session;

  /** @var EngineInterface */
  protected $templating;

  /** @var string */
  protected $formServerPublicUrl;

  /** @var UserSessionService */
  protected $userSessionService;

  /** @var  */
  protected $browserRestrictions;

  /**
   * DefaultHandler constructor.
   * @param TokenStorage $tokenStorage
   * @param LoggerInterface $logger
   * @param UrlGeneratorInterface $router
   * @param EntityManagerInterface $em
   * @param PraticaFlowRegistry $flowRegistry
   * @param SessionInterface $session
   * @param EngineInterface $templating
   * @param $formServerPublicUrl
   * @param UserSessionService $userSessionService
   * @param $browserRestrictions
   */
  public function __construct(
    TokenStorage $tokenStorage,
    LoggerInterface $logger,
    UrlGeneratorInterface $router,
    EntityManagerInterface $em,
    PraticaFlowRegistry $flowRegistry,
    SessionInterface $session,
    EngineInterface $templating,
    $formServerPublicUrl,
    UserSessionService $userSessionService,
    $browserRestrictions
  ) {
    $this->em = $em;
    $this->flowRegistry = $flowRegistry;
    $this->session = $session;
    $this->templating = $templating;
    $this->formServerPublicUrl = $formServerPublicUrl;
    $this->userSessionService = $userSessionService;
    $this->browserRestrictions = $browserRestrictions;

    parent::__construct($tokenStorage, $logger, $router);

  }

  public function canAccess(Servizio $servizio, Ente $ente)
  {
    parent::canAccess($servizio, $ente);

    // Check Browser
    if ($this->browserRestrictions != null) {
      $browserParser = new BrowserParser($this->browserRestrictions);
      if ($browserParser->isBrowserRestricted()) {
        throw new ForbiddenAccessException('servizio.browser_restricted');
      }
    }
  }


  /**
   * @param Servizio $servizio
   * @param Ente $ente
   * @return Response
   * @throws \Exception
   */
  public function execute(Servizio $servizio, Ente $ente)
  {
    if ($this->getUser() instanceof CPSUser) {

      return (new DefaultLoggedInHandler(
        $this->tokenStorage,
        $this->logger,
        $this->router,
        $this->em,
        $this->flowRegistry,
        $this->session,
        $this->templating,
        $this->formServerPublicUrl,
        $this->userSessionService,
        $this->browserRestrictions
      ))->execute($servizio, $ente);

    } else {

      if (!$this->getUser() instanceof CPSUser
        && ($servizio->getAccessLevel() > 0 || $servizio->getAccessLevel() === null)) {

        $error = 'Il servizio '.$servizio->getName().' è disponibile solo per gli utenti autenticati.';
        $this->errorMessage = $error;
        throw new ForbiddenAccessException($error);
      }

      return (new DefaultAnonymousHandler(
        $this->tokenStorage,
        $this->logger,
        $this->router,
        $this->em,
        $this->flowRegistry,
        $this->session,
        $this->templating,
        $this->formServerPublicUrl,
        $this->userSessionService,
        $this->browserRestrictions
      ))->execute($servizio, $ente);
    }
  }

  public function getCallToActionText()
  {
    return 'servizio.accedi_al_servizio';
  }

  public function getErrorMessage()
  {
    return $this->errorMessage;
  }
}
