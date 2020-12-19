<?php

namespace App\Handlers\Servizio;

use App\Entity\CPSUser;
use App\Entity\Ente;
use App\Entity\Servizio;
use App\Form\PraticaFlowRegistry;
use App\Services\UserSessionService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Twig\Environment;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;


class DefaultHandler extends AbstractServizioHandler
{
  protected $errorMessage = "Errore inatteso: contattare il supporto";

  /**
   * @var $em EntityManagerInterface
   */
  protected $em;

  /**
   * @var PraticaFlowRegistry
   */
  protected $flowRegistry;

  /**
   * @var SessionInterface
   */
  protected $session;

  /** @var Environment */
  protected $templating;

  protected $formServerPublicUrl;

  /**
   * @var UserSessionService
   */
  protected $userSessionService;

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
    UserSessionService $userSessionService
  ) {
    $this->em = $em;
    $this->flowRegistry = $flowRegistry;
    $this->session = $session;
    $this->templating = $templating;
    $this->formServerPublicUrl = $formServerPublicUrl;
    $this->userSessionService = $userSessionService;

    parent::__construct($tokenStorage, $logger, $router);
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
        $this->userSessionService
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
        $this->userSessionService
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
