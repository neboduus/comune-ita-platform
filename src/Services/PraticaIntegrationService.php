<?php

namespace App\Services;

use App\Entity\IntegrabileInterface;
use App\Entity\Message;
use App\Entity\RichiestaIntegrazioneDTO;
use App\Entity\RichiestaIntegrazioneRequestInterface;
use App\Entity\Pratica;
use App\Entity\RispostaOperatoreDTO;
use App\Entity\StatusChange;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class PraticaIntegrationService
{
    /**
     * @var $em EntityManagerInterface
     */
    private $em;

    /**
     * @var $logger LoggerInterface
     */
    private $logger;

    /**
     * @var PraticaStatusService
     */
    private $statusService;

    /**
     * @var ModuloPdfBuilderService
     */
    private $pdfBuilder;

    /**
     * @var TranslatorInterface
     */
    private $translator;

  public function __construct(
        EntityManagerInterface $em,
        LoggerInterface $logger,
        PraticaStatusService $statusService,
        ModuloPdfBuilderService $pdfBuilder,
        TranslatorInterface $translator
    ) {
        $this->em = $em;
        $this->logger = $logger;
        $this->statusService = $statusService;
        $this->pdfBuilder = $pdfBuilder;
        $this->translator = $translator;
    }

    /**
     * @param Pratica $pratica
     * @param RichiestaIntegrazioneDTO $integration
     *
     * @throws \Exception
     */
    public function requestIntegration(Pratica $pratica, RichiestaIntegrazioneDTO $integration)
    {
        if ($pratica instanceof IntegrabileInterface) {

          $this->statusService->validateChangeStatus($pratica, Pratica::STATUS_REQUEST_INTEGRATION);

          $message = new Message();
          $message->setApplication($pratica);
          $message->setProtocolRequired(false);
          $message->setVisibility(Message::VISIBILITY_APPLICANT);
          $message->setMessage($integration->getMessage());
          $message->setSubject($this->translator->trans('pratica.messaggi.oggetto', ['%pratica%' => $message->getApplication()]));

          $this->em->persist($message);

            $integration = $this->pdfBuilder->creaModuloProtocollabilePerRichiestaIntegrazione($pratica, $integration);
            $pratica->addRichiestaIntegrazione($integration);
            $pratica->setInstanceId(null);

            $this->em->persist($pratica);
            $this->em->flush();

            $statusChange = new StatusChange();
            $statusChange->setMessageId($message->getId());
            $this->statusService->setNewStatus($pratica, Pratica::STATUS_REQUEST_INTEGRATION, $statusChange);
        } else {
            throw new \InvalidArgumentException("Pratica must be implements " . IntegrabileInterface::class . " interface");
        }
    }


    /**
     * @param Pratica $pratica
     * @param RichiestaIntegrazioneDTO $integration
     *
     * @throws \Exception
     */
    public function createRispostaOperatore(Pratica $pratica, RispostaOperatoreDTO $risposta)
    {
        $risposta = $this->pdfBuilder->creaRispostaOperatore($pratica, $risposta);
        $pratica->addRispostaOperatore($risposta);
        $pratica->setInstanceId(null);

        $this->em->persist($pratica);
        $this->em->flush();
    }
}
