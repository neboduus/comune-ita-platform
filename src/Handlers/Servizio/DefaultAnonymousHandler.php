<?php

namespace App\Handlers\Servizio;

use App\Entity\CPSUser;
use App\Entity\Pratica;
use App\Entity\Servizio;
use App\Logging\LogConstants;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class DefaultAnonymousHandler extends DefaultHandler
{

  use TargetPathTrait;

  /**
   * @param Servizio $servizio
   * @return Response
   * @throws \Exception
   */
  public function execute(Servizio $servizio)
  {

    $user = $this->cpsUserProvider->createAnonymousUser();
    $pratica = $this->createNewPratica($servizio, $user);
    $this->em->flush();

    if (!$this->session->isStarted()) {
      $this->session->start();
    }

    // Loggo l'utente appena creato
    $this->saveTargetPath($this->session, 'open_login', $this->router->generate('pratiche_compila', ['pratica' => $pratica->getId()]));

    return new RedirectResponse(
      $this->router->generate('login_token', ['token' => $user->getConfirmationToken()]),
      302
    );
  }

  /**
   * @param Servizio $servizio
   * @param CPSUser $user
   * @return Pratica
   */
  private function createNewPratica(Servizio $servizio, CPSUser $user): Pratica
  {
    $praticaClassName = $servizio->getPraticaFCQN();
    $pratica = new $praticaClassName();
    if (!$pratica instanceof Pratica) {
      throw new \RuntimeException("Wrong Pratica FCQN for servizio {$servizio->getName()}");
    }
    $pratica
      ->setServizio($servizio)
      ->setUser($user)
      ->setAuthenticationData($this->userSessionService->getCurrentUserAuthenticationData($user))
      ->setSessionData($this->userSessionService->getCurrentUserSessionData($user))
      ->setStatus(Pratica::STATUS_DRAFT)
      ->setHash(hash('sha256', $pratica->getId()).'-'.(new \DateTime())->getTimestamp());

    $ente = $servizio->getEnte();
    $pratica->setEnte($ente);
    foreach ($servizio->getErogatori() as $erogatore) {
      if ($erogatore->getEnti()->contains($ente)) {
        $pratica->setErogatore($erogatore);
      }
    }

    $this->em->persist($pratica);

    $this->logger->info(
      LogConstants::PRATICA_CREATED,
      ['type' => $pratica->getType(), 'pratica' => $pratica]
    );

    return $pratica;
  }

}
