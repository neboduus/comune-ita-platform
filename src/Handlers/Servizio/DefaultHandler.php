<?php

namespace App\Handlers\Servizio;

use App\Entity\CPSUser;
use App\Entity\Ente;
use App\Entity\Servizio;
use App\Form\PraticaFlowRegistry;
use App\Services\CPSUserProvider;
use App\Services\UserSessionService;
use App\Utils\BrowserParser;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Twig\Environment;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class DefaultHandler extends AbstractServizioHandler
{
  protected $errorMessage = "Errore inatteso: contattare il supporto";

  /** @var $em EntityManagerInterface */
  protected $em;

  /** @var PraticaFlowRegistry */
  protected $flowRegistry;

  /** @var SessionInterface */
  protected $session;

  /** @var Environment */
  protected $templating;

  /** @var string */
  protected $formServerPublicUrl;

  /** @var UserSessionService */
  protected $userSessionService;

  /** @var  */
  protected $browserRestrictions;

  /**
   * @var CPSUserProvider
   */
  protected $cpsUserProvider;

  /**
   * DefaultHandler constructor.
   * @param TokenStorageInterface $tokenStorage
   * @param LoggerInterface $logger
   * @param UrlGeneratorInterface $router
   * @param EntityManagerInterface $em
   * @param PraticaFlowRegistry $flowRegistry
   * @param SessionInterface $session
   * @param Environment $templating
   * @param $formServerPublicUrl
   * @param UserSessionService $userSessionService
   * @param $browserRestrictions
   * @param CPSUserProvider $cpsUserProvider
   */
  public function __construct(
    TokenStorageInterface $tokenStorage,
    LoggerInterface $logger,
    UrlGeneratorInterface $router,
    EntityManagerInterface $em,
    PraticaFlowRegistry $flowRegistry,
    SessionInterface $session,
    Environment $templating,
    $formServerPublicUrl,
    UserSessionService $userSessionService,
    $browserRestrictions,
    CPSUserProvider $cpsUserProvider
  ) {
    $this->em = $em;
    $this->flowRegistry = $flowRegistry;
    $this->session = $session;
    $this->templating = $templating;
    $this->formServerPublicUrl = $formServerPublicUrl;
    $this->userSessionService = $userSessionService;
    $this->browserRestrictions = $browserRestrictions;
    $this->cpsUserProvider = $cpsUserProvider;

    parent::__construct($tokenStorage, $logger, $router);
  }

  public function canAccess(Servizio $servizio)
  {
    parent::canAccess($servizio);

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
   * @return Response
   * @throws \Exception
   */
  public function execute(Servizio $servizio)
  {
    $user = $this->getUser();

    if ($this->getUser() instanceof CPSUser && !$user->isAnonymous()) {

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
        $this->browserRestrictions,
        $this->cpsUserProvider
      ))->execute($servizio);

    } else {

      if ((!$user instanceof CPSUser || $user->isAnonymous())
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
        $this->browserRestrictions,
        $this->cpsUserProvider
      ))->execute($servizio);
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
