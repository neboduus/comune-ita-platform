<?php

namespace App\Handlers\Servizio;

use App\Entity\Ente;
use App\Entity\Pratica;
use App\Entity\Servizio;
use App\Logging\LogConstants;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class DefaultAnonymousHandler extends DefaultHandler
{
  /**
   * @param Servizio $servizio
   * @param Ente $ente
   * @return Response
   * @throws \Exception
   */
  public function execute(Servizio $servizio, Ente $ente)
  {
    $pratica = $this->createNewPratica($servizio);
    $pratica->setEnte($ente);

    foreach ($servizio->getErogatori() as $erogatore) {
      if ($erogatore->getEnti()->contains($ente)) {
        $pratica->setErogatore($erogatore);
      }
    }

    if (!$this->session->isStarted()) {
      $this->session->start();
    }

    $praticaFlowService = $this->flowRegistry->getByName($servizio->getPraticaFlowServiceName());
    $praticaFlowService->setInstanceKey($this->session->getId());
    $praticaFlowService->bind($pratica);

    if ($pratica->getInstanceId() == null) {
      $pratica->setInstanceId($praticaFlowService->getInstanceId());
    }
    $form = $praticaFlowService->createForm();

    if ($praticaFlowService->isValid($form)) {
      $currentStep = $praticaFlowService->getCurrentStepNumber();
      $praticaFlowService->saveCurrentStepData($form);
      $pratica->setLastCompiledStep($currentStep);

      if ($praticaFlowService->nextStep()) {
        $form = $praticaFlowService->createForm();

      } else {
        $this->em->persist($pratica);
        $this->em->flush();
        $praticaFlowService->onFlowCompleted($pratica);

        $this->logger->info(
          LogConstants::PRATICA_UPDATED,
          ['id' => $pratica->getId(), 'pratica' => $pratica]
        );

        $praticaFlowService->getDataManager()->drop($praticaFlowService);
        $praticaFlowService->reset();

        return new RedirectResponse(
          $this->router->generate(
            'pratiche_anonime_show',
            [
              'pratica' => $pratica->getId(),
              'hash' => $pratica->getHash(),
            ]
          )
        );
      }
    }

    return (new Response())->setContent(
      $this->templating->render(
        'App:PraticheAnonime:new.html.twig',
        [
          'form' => $form->createView(),
          'pratica' => $praticaFlowService->getFormData(),
          'flow' => $praticaFlowService,
          'formserver_url' => $this->formServerPublicUrl,
        ]
      )
    );
  }

  /**
   * @param Servizio $servizio
   *
   * @return Pratica
   * @throws \Exception
   */
  private function createNewPratica(Servizio $servizio)
  {
    $praticaClassName = $servizio->getPraticaFCQN();
    $pratica = new $praticaClassName();
    if (!$pratica instanceof Pratica) {
      throw new \RuntimeException("Wrong Pratica FCQN for servizio {$servizio->getName()}");
    }
    $pratica
      ->setServizio($servizio)
      ->setStatus(Pratica::STATUS_DRAFT)
      ->setHash(hash('sha256', $pratica->getId()).'-'.(new \DateTime())->getTimestamp());

    $this->logger->info(
      LogConstants::PRATICA_CREATED,
      ['type' => $pratica->getType(), 'pratica' => $pratica]
    );

    return $pratica;
  }
}
