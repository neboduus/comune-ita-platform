<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Allegato;
use AppBundle\Entity\CPSUser;
use AppBundle\Entity\DematerializedFormAllegatiContainer;
use AppBundle\Entity\DematerializedFormPratica;
use AppBundle\Entity\GiscomPratica;
use AppBundle\Entity\Pratica;
use AppBundle\Entity\Servizio;
use AppBundle\Entity\User;
use AppBundle\Form\Base\MessageType;
use AppBundle\Form\Base\PraticaFlow;
use AppBundle\Logging\LogConstants;
use AppBundle\Services\DematerializedFormAllegatiAttacherService;
use AppBundle\Services\ModuloPdfBuilderService;
use AppBundle\Services\PraticaStatusService;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class PraticheAnonimeController
 *
 * @package AppBundle\Controller
 * @Route("/pratiche-anonime")
 */
class PraticheAnonimeController extends Controller
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


  const ENTE_SLUG_QUERY_PARAMETER = 'ente';

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
    DematerializedFormAllegatiAttacherService $dematerializer
  )
  {
    $this->logger = $logger;
    $this->translator = $translator;
    $this->statusService = $statusService;
    $this->pdfBuilder = $pdfBuilder;
    $this->dematerializer = $dematerializer;
  }

  /**
   * @Route("/{servizio}/new", name="pratiche_anonime_new")
   * @ParamConverter("servizio", class="AppBundle:Servizio", options={"mapping": {"servizio": "slug"}})
   * @Template()
   * @param Pratica $pratica
   *
   * @return array|RedirectResponse
   */
  public function newAction(Request $request, Servizio $servizio)
  {
    if ($servizio->getAccessLevel() > 0 || $servizio->getAccessLevel() === null) {
      $this->addFlash('warning', 'Il servizio ' . $servizio->getName() . ' è disponibile solo per gli utenti loggati.');
      return $this->redirectToRoute('servizi_list');
    }

    $pratica = $this->createNewPratica($servizio);

    $instanceService = $this->container->get('ocsdc.instance_service');
    $pratica->setEnte($instanceService->getCurrentInstance());
    $this->infereErogatoreFromEnteAndServizio($pratica);
    $flow = $this->get('ocsdc.form.flow.formioanonymous');

    $flow->setInstanceKey($this->get('session')->getId());
    $flow->bind($pratica);
    if ($pratica->getInstanceId() == null) {
      $pratica->setInstanceId($flow->getInstanceId());
    }
    $form = $flow->createForm();

    if ($flow->isValid($form)) {
      $em = $this->getDoctrine()->getManager();
      $currentStep = $flow->getCurrentStepNumber();
      $flow->saveCurrentStepData($form);
      $pratica->setLastCompiledStep($currentStep);

      if ($flow->nextStep()) {
        $form = $flow->createForm();
      } else {

        $user = $this->checkUser($pratica->getDematerializedForms());
        $pratica->setUser($user);
        $em->persist($pratica);
        $em->flush();

        if ($pratica instanceof DematerializedFormAllegatiContainer) {
          $this->dematerializer->attachAllegati($pratica);
        }

        if ($pratica->getStatus() == Pratica::STATUS_DRAFT) {
          $pratica->setSubmissionTime(time());
          $moduloCompilato = $this->pdfBuilder->createForPratica($pratica);
          $pratica->addModuloCompilato($moduloCompilato);
          $this->statusService->setNewStatus($pratica, Pratica::STATUS_SUBMITTED);
        }

        $this->get('logger')->info(
          LogConstants::PRATICA_UPDATED,
          ['id' => $pratica->getId(), 'pratica' => $pratica]
        );

        $this->addFlash('feedback', $this->get('translator')->trans('pratica_ricevuta'));
        $flow->getDataManager()->drop($flow);
        $flow->reset();

        return $this->redirectToRoute(
          'pratiche_anonime_show',
          ['pratica' => $pratica->getId()]
        );
      }
    }

    return [
      'form' => $form->createView(),
      'pratica' => $flow->getFormData(),
      'flow' => $flow,
      'formserver_url' => $this->getParameter('formserver_public_url'),
    ];
  }

  /**
   * @Route("/{pratica}", name="pratiche_anonime_show")
   * @ParamConverter("pratica", class="AppBundle:Pratica")
   * @Template()
   * @param Pratica $pratica
   *
   * @return array
   */
  public function showAction(Request $request, Pratica $pratica)
  {
    $result = [
      'pratica' => $pratica
    ];
    return $result;
  }

  /**
   * @Route("/{pratica}/pdf", name="pratiche_anonime_show_pdf")
   * @ParamConverter("pratica", class="AppBundle:Pratica")
   * @param Pratica $pratica
   *
   * @return BinaryFileResponse
   */
  public function showPdfAction(Request $request, Pratica $pratica)
  {
    $user = $pratica->getUser();
    $allegato = $this->container->get('ocsdc.modulo_pdf_builder')->showForPratica($pratica);


    return new BinaryFileResponse(
      $allegato->getFile()->getPath() . '/' . $allegato->getFile()->getFilename(),
      200,
      [
        'Content-type' => 'application/octet-stream',
        'Content-Disposition' => sprintf('attachment; filename="%s"', $allegato->getOriginalFilename() . '.' . $allegato->getFile()->getExtension()),
      ]
    );
  }

  /**
   * @Route("/formio/validate", name="formio_validate")
   *
   */
  public function formioValidateAction(Request $request)
  {
    // Todo: validazione base del form
    $user = $this->getUser();
    $response = array('status' => 'OK');
    return JsonResponse::create($response, Response::HTTP_OK);
  }

  /**
   * @param Servizio $servizio
   *
   * @return Pratica
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
      //->setType($servizio->getSlug())
      //->setUser($user)
      ->setStatus(Pratica::STATUS_DRAFT);

    $this->get('logger')->info(
      LogConstants::PRATICA_CREATED,
      ['type' => $pratica->getType(), 'pratica' => $pratica]
    );

    return $pratica;
  }

  private function checkUser(array $data): CPSUser
  {
    $em = $this->getDoctrine()->getManager();
    $cf = $data['flattened']['applicant.data.fiscal_code.data.fiscal_code'] ? $data['flattened']['applicant.data.fiscal_code.data.fiscal_code'] : false;

    if ( $cf ) {
      $result = $em->createQueryBuilder()
        ->select('user.id')
        ->from('AppBundle:User', 'user')
        ->where('upper(user.username) = upper(:username)')
        ->setParameter('username', $cf)
        ->getQuery()->getResult();

      if ( !empty($result)) {
        $repository = $this->getDoctrine()->getRepository('AppBundle:CPSUser');
        $user =  $repository->find($result[0]['id']);
        return $user;
      }
    }

    $user = new CPSUser();
    $user
      ->setUsername($cf ? $cf : $user->getId() )
      ->setCodiceFiscale( $cf ? $cf : '' )
      ->setEmail( $data['flattened']['applicant.data.email_address'] ? $data['flattened']['applicant.data.email_address'] : $user->getId() . '@' . CPSUser::FAKE_EMAIL_DOMAIN )
      ->setNome($data['flattened']['applicant.data.completename.data.name'] ? $data['flattened']['applicant.data.completename.data.name'] : '' )
      ->setCognome($data['flattened']['applicant.data.completename.data.surname'] ? $data['flattened']['applicant.data.completename.data.surname'] : '' )
      ->setDataNascita(isset($data['flattened']['applicant.data.Born.data.natoAIl']) && !empty($data['flattened']['applicant.data.Born.data.natoAIl']) ? new \DateTime($data['flattened']['applicant.data.Born.data.natoAIl']) : null )
      ->setLuogoNascita( isset($data['flattened']['applicant.data.Born.data.place_of_birth']) && !empty($data['flattened']['applicant.data.Born.data.place_of_birth']) ? $data['flattened']['applicant.data.Born.data.place_of_birth'] : '' )
      ->setSdcIndirizzoResidenza( isset($data['flattened']['applicant.data.Born.data.address']) && !empty($data['flattened']['applicant.data.Born.data.address']) ? $data['flattened']['applicant.data.address.data.address'] : '' )
      ->setSdcCittaResidenza( isset($data['flattened']['applicant.data.Born.data.municipality']) && !empty($data['flattened']['applicant.data.Born.data.municipality']) ? $data['flattened']['applicant.data.address.data.municipality'] : '' )
      ->setSdcCapResidenza( isset($data['flattened']['applicant.data.Born.data.postal_code']) && !empty($data['flattened']['applicant.data.Born.data.postal_code']) ? $data['flattened']['applicant.data.address.data.postal_code'] : '' );

    $user->addRole('ROLE_USER')
      ->addRole('ROLE_CPS_USER')
      ->setEnabled(true)
      ->setPassword('');

    $em->persist($user);
    $em->flush();

    return $user;
  }


  /**
   * @param Pratica $pratica
   */
  private function infereErogatoreFromEnteAndServizio(Pratica $pratica)
  {
    $ente = $pratica->getEnte();
    $servizio = $pratica->getServizio();
    $erogatori = $servizio->getErogatori();
    foreach ($erogatori as $erogatore) {
      if ($erogatore->getEnti()->contains($ente)) {
        $pratica->setErogatore($erogatore);

        return;
      }
    }
    //FIXME: testme
    throw new \Error('Missing erogatore for service ');
  }
}
