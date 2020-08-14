<?php

namespace AppBundle\Form\Base;

use AppBundle\Entity\ComponenteNucleoFamiliare;
use AppBundle\Entity\CPSUser;
use AppBundle\Entity\DematerializedFormAllegatiContainer;
use AppBundle\Entity\Pratica;
use AppBundle\Entity\PraticaRepository;
use AppBundle\Form\Extension\TestiAccompagnatoriProcedura;
use AppBundle\FormIO\SchemaFactoryInterface;
use AppBundle\Logging\LogConstants;
use AppBundle\Services\DematerializedFormAllegatiAttacherService;
use AppBundle\Services\ModuloPdfBuilderService;
use AppBundle\Services\PraticaStatusService;
use Craue\FormFlowBundle\Form\FormFlow;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\Request;

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

  protected $formIOFactory;

  protected $em;

  /**
   * PraticaFlow constructor.
   * @param LoggerInterface $logger
   * @param TranslatorInterface $translator
   * @param PraticaStatusService $statusService
   * @param ModuloPdfBuilderService $pdfBuilder
   * @param DematerializedFormAllegatiAttacherService $dematerializer
   * @param $prefix
   * @param SchemaFactoryInterface $formIOFactory
   * @param EntityManagerInterface $em
   *
   */
  public function __construct(
    LoggerInterface $logger,
    TranslatorInterface $translator,
    PraticaStatusService $statusService,
    ModuloPdfBuilderService $pdfBuilder,
    DematerializedFormAllegatiAttacherService $dematerializer,
    $prefix,
    SchemaFactoryInterface $formIOFactory,
    EntityManagerInterface $em
  )
  {
    $this->logger = $logger;
    $this->translator = $translator;
    $this->statusService = $statusService;
    $this->pdfBuilder = $pdfBuilder;
    $this->dematerializer = $dematerializer;
    $this->prefix = $prefix;
    $this->formIOFactory = $formIOFactory;
    $this->em = $em;
  }

  public function getFormOptions($step, array $options = array())
  {
    $options = parent::getFormOptions($step, $options);

    /** @var Pratica $pratica */
    $pratica = $this->getFormData();
    $options["helper"] = new TestiAccompagnatoriProcedura($this->translator, $this->prefix);

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

  public function getResumeUrl(Request $request)
  {
    return $request->getUri()
      . '?instance=' . $this->getInstanceId()
      . '&step=' . $this->getCurrentStepNumber();
  }

  public function onFlowCompleted(Pratica $pratica)
  {
    if ($pratica instanceof DematerializedFormAllegatiContainer) {
      $this->dematerializer->attachAllegati($pratica);
    }
    /** @var PraticaRepository $repo */
    $repo = $this->em->getRepository(Pratica::class);

    // Per non sovrascrivere comportamento in formio flow
    if ($pratica->getFolderId() == null) {
      $pratica->setServiceGroup($pratica->getServizio()->getServiceGroup());
      $pratica->setFolderId($repo->getFolderForApplication($pratica));
    }

    if ($pratica->getStatus() == Pratica::STATUS_DRAFT) {
      $pratica->setSubmissionTime(time());
      $this->statusService->setNewStatus($pratica, Pratica::STATUS_PRE_SUBMIT);

    } elseif ($pratica->getStatus() == Pratica::STATUS_DRAFT_FOR_INTEGRATION) {
      $this->statusService->setNewStatus($pratica, Pratica::STATUS_SUBMITTED_AFTER_INTEGRATION);
    }
  }


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
