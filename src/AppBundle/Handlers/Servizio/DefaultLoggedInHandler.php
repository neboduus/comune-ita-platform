<?php

namespace AppBundle\Handlers\Servizio;

use AppBundle\Entity\CPSUser;
use AppBundle\Entity\DematerializedFormPratica;
use AppBundle\Entity\Ente;
use AppBundle\Entity\Pratica;
use AppBundle\Entity\Servizio;
use AppBundle\Form\Base\PraticaFlow;
use AppBundle\Logging\LogConstants;
use Symfony\Component\HttpFoundation\RedirectResponse;

class DefaultLoggedInHandler extends DefaultHandler
{
  public function execute(Servizio $servizio, Ente $ente)
  {
    $user = $this->getUser();

    $praticaFQCN = $servizio->getPraticaFCQN();
    $praticaInstance = new $praticaFQCN();

    $repo = $this->em->getRepository('AppBundle:Pratica');
    $pratiche = $repo->findBy(
      array(
        'user' => $user,
        'servizio' => $servizio,
        'status' => Pratica::STATUS_DRAFT,
      ),
      array('creationTime' => 'ASC')
    );

    if (!$praticaInstance instanceof DematerializedFormPratica && !empty($pratiche)) {
      return new RedirectResponse(
        $this->router->generate('pratiche_list_draft', ['servizio' => $servizio->getSlug()]),
        302
      );
    }

    $pratica = $this->createNewPratica($servizio, $user);
    $pratica->setEnte($ente);
    foreach ($servizio->getErogatori() as $erogatore) {
      if ($erogatore->getEnti()->contains($ente)) {
        $pratica->setErogatore($erogatore);
      }
    }

    $this->em->flush();

    return new RedirectResponse(
      $this->router->generate('pratiche_compila', ['pratica' => $pratica->getId()]),
      302
    );
  }

  /**
   * @param Servizio $servizio
   * @param CPSUser $user
   *
   * @return Pratica
   */
  private function createNewPratica(Servizio $servizio, CPSUser $user)
  {
    $praticaClassName = $servizio->getPraticaFCQN();
    /** @var PraticaFlow $praticaFlowService */
    $praticaFlowService = $this->flowRegistry->getByName($servizio->getPraticaFlowServiceName());

    $pratica = new $praticaClassName();
    if (!$pratica instanceof Pratica) {
      throw new \RuntimeException("Wrong Pratica FCQN for servizio {$servizio->getName()}");
    }
    $pratica
      ->setServizio($servizio)
      ->setUser($user)
      ->setAuthenticationData($this->userSessionService->getCurrentUserAuthenticationData($user))
      ->setSessionData($this->userSessionService->getCurrentUserSessionData($user))
      ->setStatus(Pratica::STATUS_DRAFT);

    $repo = $this->em->getRepository('AppBundle:Pratica');
    $lastPraticaList = $repo->findBy(
      array(
        'user' => $user,
        'servizio' => $servizio,
        'status' => [
          Pratica::STATUS_COMPLETE,
          Pratica::STATUS_SUBMITTED,
          Pratica::STATUS_PENDING,
          Pratica::STATUS_REGISTERED,
        ],
      ),
      array('creationTime' => 'DESC'),
      1
    );
    $lastPratica = null;
    if ($lastPraticaList) {
      $lastPratica = $lastPraticaList[0];
    }
    if ($lastPratica instanceof Pratica) {
      $praticaFlowService->populatePraticaFieldsWithLastPraticaValues($lastPratica, $pratica);
    }

    $user = $this->getUser();
    $praticaFlowService->populatePraticaFieldsWithUserValues($user, $pratica);

    $this->em->persist($pratica);
    $this->em->flush();

    $this->logger->info(
      LogConstants::PRATICA_CREATED,
      ['type' => $pratica->getType(), 'pratica' => $pratica]
    );

    return $pratica;
  }
}
