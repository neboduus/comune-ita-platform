<?php

namespace App\Form\Base;

use App\Entity\ComponenteNucleoFamiliare;
use App\Entity\CPSUser;
use App\Entity\DematerializedFormAllegatiContainer;
use App\Entity\Pratica;
use App\Form\Extension\TestiAccompagnatoriProcedura;
use App\Services\DematerializedFormAllegatiAttacherService;
use App\Services\InstanceService;
use App\Services\ModuloPdfBuilderService;
use App\Services\PraticaStatusService;
use Craue\FormFlowBundle\Form\FormFlow;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class PraticaFlow extends FormFlow implements PraticaFlowInterface
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var PraticaStatusService
     */
    protected $statusService;

    /**
     * @var ModuloPdfBuilderService
     */
    protected $pdfBuilder;

    /**
     * @var DematerializedFormAllegatiAttacherService
     */
    protected $dematerializer;

    /**
     * @var bool
     */
    protected $revalidatePreviousSteps = false;

    protected $handleFileUploads = false;

    protected $paymentRequired = false;

    protected $prefix;

    protected $instanceService;

    /**
     * PraticaFlow constructor.
     *
     * @param LoggerInterface $logger
     * @param TranslatorInterface $translator
     */
    public function __construct(
        LoggerInterface $logger,
        TranslatorInterface $translator,
        PraticaStatusService $statusService,
        ModuloPdfBuilderService $pdfBuilder,
        DematerializedFormAllegatiAttacherService $dematerializer,
        InstanceService $instanceService
    )
    {
        $this->logger = $logger;
        $this->translator = $translator;
        $this->statusService = $statusService;
        $this->pdfBuilder = $pdfBuilder;
        $this->dematerializer = $dematerializer;
        $this->instanceService = $instanceService;
        $this->prefix = $instanceService->getPrefix();
    }

    public function getFormOptions($step, array $options = array())
    {
        $options = parent::getFormOptions($step, $options);

        /** @var Pratica $pratica */
        $pratica = $this->getFormData();
        $options["helper"] = new TestiAccompagnatoriProcedura($this->translator, $this->instanceService); //@todo make in DI

        return $options;
    }

    /**
     * @param CPSUser $user
     * @param Pratica $pratica
     */
    public function populatePraticaFieldsWithUserValues(CPSUser $user, $pratica)
    {
        $pratica->setRichiedenteNome($user->getNome());
        $pratica->setRichiedenteCognome($user->getCognome());
        $pratica->setRichiedenteCodiceFiscale($user->getCodiceFiscale());
        $pratica->setRichiedenteLuogoNascita($user->getLuogoNascita());
        $pratica->setRichiedenteDataNascita($user->getDataNascita());
        $pratica->setRichiedenteIndirizzoResidenza($user->getIndirizzoResidenza());
        $pratica->setRichiedenteCapResidenza($user->getCapResidenza());
        $pratica->setRichiedenteCittaResidenza($user->getCittaResidenza());
        $pratica->setRichiedenteTelefono($user->getCellulare() ?? $user->getTelefono());
        $pratica->setRichiedenteEmail($user->getEmail());
    }

    /**
     * @param Pratica $lastPratica
     * @param Pratica $pratica
     */
    public function populatePraticaFieldsWithLastPraticaValues($lastPratica, $pratica)
    {
        foreach ($lastPratica->getNucleoFamiliare() as $oldComponente) {
            $this->addNewComponenteToPraticaFromOldComponente($oldComponente, $pratica);
        }
    }

    /**
     * @param ComponenteNucleoFamiliare $componente
     * @param Pratica $pratica
     */
    /**
     * @param ComponenteNucleoFamiliare $componente
     * @param Pratica $pratica
     */
    private function addNewComponenteToPraticaFromOldComponente(ComponenteNucleoFamiliare $componente, Pratica $pratica)
    {
        $cloneComponente = new ComponenteNucleoFamiliare();
        $cloneComponente->setNome($componente->getNome());
        $cloneComponente->setCognome($componente->getCognome());
        $cloneComponente->setCodiceFiscale($componente->getCodiceFiscale());
        $cloneComponente->setRapportoParentela($componente->getRapportoParentela());
        $pratica->addComponenteNucleoFamiliare($cloneComponente);
    }

    public function getResumeUrl(Request $request)
    {
        return $request->getUri()
            . '?instance=' . $this->getInstanceId()
            . '&step=' . $this->getCurrentStepNumber();
    }

    /**
     * @param Pratica $pratica
     * @throws \ReflectionException
     * @throws \Exception
     */
    public function onFlowCompleted(Pratica $pratica)
    {
        if ($pratica instanceof DematerializedFormAllegatiContainer) {
            $this->dematerializer->attachAllegati($pratica);
        }

        if ($pratica->getStatus() == Pratica::STATUS_DRAFT) {
            $pratica->setSubmissionTime(time());
            $this->statusService->setNewStatus($pratica, Pratica::STATUS_PRE_SUBMIT);

        } elseif ($pratica->getStatus() == Pratica::STATUS_DRAFT_FOR_INTEGRATION) {
            $this->statusService->setNewStatus($pratica, Pratica::STATUS_SUBMITTED_AFTER_INTEGRATION);
        }
    }

    /**
     * @return bool
     */
    public function isPaymentRequired(): bool
    {
        return $this->paymentRequired;
    }

    /**
     * @param bool $paymentRequired
     */
    public function setPaymentRequired(bool $paymentRequired)
    {
        $this->paymentRequired = $paymentRequired;
    }
}
