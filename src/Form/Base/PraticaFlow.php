<?php

namespace App\Form\Base;

use App\Entity\CPSUser;
use App\Entity\DematerializedFormAllegatiContainer;
use App\Entity\Pratica;
use App\Entity\PraticaRepository;
use App\Form\Extension\TestiAccompagnatoriProcedura;
use App\FormIO\SchemaFactoryInterface;
use App\Logging\LogConstants;
use App\Payment\Gateway\Bollo;
use App\Services\DematerializedFormAllegatiAttacherService;
use App\Services\ModuloPdfBuilderService;
use App\Services\PraticaStatusService;
use App\Services\UserSessionService;
use Craue\FormFlowBundle\Form\FormFlow;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
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

  /** @var bool */
  protected $revalidatePreviousSteps = false;

  protected $handleFileUploads = false;

  protected $paymentRequired = false;

  protected $prefix;

  protected $formIOFactory;

  protected $em;

  protected $userSessionService;

  private $locale;

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
   * @param UserSessionService $userSessionService
   * @param $locale
   */
  public function __construct(
    LoggerInterface $logger,
    TranslatorInterface $translator,
    PraticaStatusService $statusService,
    ModuloPdfBuilderService $pdfBuilder,
    DematerializedFormAllegatiAttacherService $dematerializer,
    $prefix,
    SchemaFactoryInterface $formIOFactory,
    EntityManagerInterface $em,
    UserSessionService $userSessionService,
    $locale
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
    $this->userSessionService = $userSessionService;
    $this->locale = $locale;
  }

  public function getFormOptions($step, array $options = array())
  {
    $options = parent::getFormOptions($step, $options);
    $options["helper"] = new TestiAccompagnatoriProcedura($this->translator, $this->prefix . '/' . $this->locale);
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

    $pratica->setAuthenticationData($this->userSessionService->getCurrentUserAuthenticationData($pratica->getUser()))
      ->setSessionData($this->userSessionService->getCurrentUserSessionData($pratica->getUser()));

    // Se la folderId non è stata già impostata dal FormioFlow (non è presente nella submissione un campo valorizzato related_applications)
    // Todo: bisogna controllare che il serviceGroup oltre ad essere presente il campo registerInFolder sia true???
    if ($pratica->getFolderId() == null) {
      $pratica->setServiceGroup($pratica->getServizio()->getServiceGroup());
      $pratica->setFolderId($repo->getFolderForApplication($pratica));
    }

    if ($pratica->getStatus() == Pratica::STATUS_DRAFT) {
      $pratica->setSubmissionTime(time());
      $this->statusService->setNewStatus($pratica, Pratica::STATUS_PRE_SUBMIT);
    } elseif($pratica->getStatus() == Pratica::STATUS_PAYMENT_PENDING && $pratica->getPaymentType() == Bollo::IDENTIFIER && $pratica->getServizio()->isPaymentDeferred()) {
      // Todo: eliminare else quando eliminiamo il bollo
      $this->statusService->setNewStatus($pratica, Pratica::STATUS_PAYMENT_SUCCESS);
    } elseif ($pratica->getStatus() == Pratica::STATUS_DRAFT_FOR_INTEGRATION) {

      // Creo il file principale per le integrazioni
      $integrationsAnswer = $this->pdfBuilder->creaModuloProtocollabilePerRispostaIntegrazione($pratica);
      $pratica->addAllegato($integrationsAnswer);

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
