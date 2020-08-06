<?php

namespace AppBundle\Handlers\Servizio;

use AppBundle\Entity\CPSUser;
use AppBundle\Entity\Ente;
use AppBundle\Entity\Servizio;
use AppBundle\Form\PraticaFlowRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Http\Discovery\Exception\NotFoundException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Templating\EngineInterface;

class DocumentsSharingHandler extends AbstractServizioHandler
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

  /** @var EngineInterface */
  protected $templating;

  protected $formServerPublicUrl;

  public function __construct(
    TokenStorage $tokenStorage,
    LoggerInterface $logger,
    UrlGeneratorInterface $router,
    EntityManagerInterface $em,
    PraticaFlowRegistry $flowRegistry,
    SessionInterface $session,
    EngineInterface $templating,
    $formServerPublicUrl
  ) {
    $this->em = $em;
    $this->flowRegistry = $flowRegistry;
    $this->session = $session;
    $this->templating = $templating;
    $this->formServerPublicUrl = $formServerPublicUrl;

    parent::__construct($tokenStorage, $logger, $router);
  }

  /**
   * @param Servizio $servizio
   * @param Ente $ente
   * @throws \Exception
   */
  public function execute(Servizio $servizio, Ente $ente)
  {

    if ($this->getUser() instanceof CPSUser) {
      $folderName = $servizio->getAdditionalData()['folder_name'];
      $folder = $this->em->getRepository('AppBundle:Folder')->findOneBy(['title'=>$folderName, 'owner'=>$this->getUser()]);
      if (!$folder) {
        $error = 'La cartella ' . $folderName . ' non esiste';
        $this->errorMessage = $error;
        throw new NotFoundException($error);
      }
      $response = new Response('', Response::HTTP_MOVED_PERMANENTLY);
      $response->headers->set('Location', $this->router->generate('documenti_list_cpsuser', ['folderSlug'=>$folder->getSlug()]));
      return $response;

    } else {
      // L'accesso ai documenti è consentito solo ad utenti autenticati
      $error = 'Il servizio ' . $servizio->getName() . ' è disponibile solo per gli utenti autenticati.';
      $this->errorMessage = $error;
      throw new ForbiddenAccessException($error);
    }
  }

  public function getCallToActionText()
  {
    return 'servizio.accedi_ai_documenti';
  }

  public function getErrorMessage()
  {
    return $this->errorMessage;
  }
}
