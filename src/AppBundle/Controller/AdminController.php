<?php

namespace AppBundle\Controller;

use AppBundle\Controller\Rest\ServicesAPIController;
use AppBundle\Dto\Service;
use AppBundle\Entity\AuditLog;
use AppBundle\Entity\Categoria;
use AppBundle\Entity\Erogatore;
use AppBundle\Entity\OperatoreUser;
use AppBundle\Entity\Pratica;
use AppBundle\Entity\Servizio;
use AppBundle\Form\Admin\ServiceFlow;
use AppBundle\FormIO\SchemaFactoryInterface;
use AppBundle\Model\FlowStep;
use AppBundle\Services\FormServerApiAdapterService;
use AppBundle\Services\InstanceService;
use AppBundle\Services\IOService;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use FOS\UserBundle\Util\TokenGeneratorInterface;
use GuzzleHttp\Client;
use Omines\DataTablesBundle\Adapter\Doctrine\ORMAdapter;
use Omines\DataTablesBundle\Column\DateTimeColumn;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\Controller\DataTablesTrait;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;


/**
 * Class AdmninController
 * @Route("/admin")
 */
class AdminController extends Controller
{
  use DataTablesTrait;

  /** @var InstanceService */
  private $instanceService;

  /** @var FormServerApiAdapterService */
  private $formServer;

  /**@var TokenGeneratorInterface */
  private $tokenGenerator;

  /** @var TranslatorInterface */
  private $translator;

  /** @var ServiceFlow */
  private $serviceFlow;

  /** @var SchemaFactoryInterface */
  private $schemaFactory;
  /**
   * @var IOService
   */
  private $ioService;
  /**
   * @var RouterInterface
   */
  private $router;

  /**
   * AdminController constructor.
   * @param InstanceService $instanceService
   * @param FormServerApiAdapterService $formServer
   * @param TokenGeneratorInterface $tokenGenerator
   * @param TranslatorInterface $translator
   * @param ServiceFlow $serviceFlow
   * @param SchemaFactoryInterface $schemaFactory
   */
  public function __construct(
    InstanceService $instanceService,
    FormServerApiAdapterService $formServer,
    TokenGeneratorInterface $tokenGenerator,
    TranslatorInterface $translator,
    ServiceFlow $serviceFlow,
    SchemaFactoryInterface $schemaFactory,
    IOService $ioService,
    RouterInterface $router
  )
  {
    $this->instanceService = $instanceService;
    $this->formServer = $formServer;
    $this->tokenGenerator = $tokenGenerator;
    $this->translator = $translator;
    $this->serviceFlow = $serviceFlow;
    $this->schemaFactory = $schemaFactory;
    $this->ioService = $ioService;
    $this->router = $router;
  }


  /**
   * @Route("/", name="admin_index")
   * @Template()
   * @param Request $request
   * @return array
   */
  public function indexAction(Request $request)
  {
    return array(
      'user' => $this->getUser()
    );
  }

  /**
   * @Route("/ente", name="admin_edit_ente")
   * @Template()
   * @param Request $request
   * @return array|RedirectResponse
   */
  public function editEnteAction(Request $request)
  {
    $entityManager = $this->getDoctrine()->getManager();
    $ente = $this->instanceService->getCurrentInstance();
    $form = $this->createForm('AppBundle\Form\Admin\Ente\EnteType', $ente);
    $form->handleRequest($request);
    if ($form->isSubmitted() && $form->isValid()) {
      $ente = $form->getData();

      $entityManager->persist($ente);
      $entityManager->flush();

      return $this->redirectToRoute('admin_edit_ente');
    }

    return array(
      'user' => $this->getUser(),
      'ente' => $ente,
      'form' => $form->createView()
    );
  }


  /**
   * Lists all operatoreUser entities.
   * @Template()
   * @Route("/operatore", name="admin_operatore_index")
   * @Method("GET")
   */
  public function indexOperatoreAction()
  {
    $em = $this->getDoctrine()->getManager();

    $operatoreUsers = $em->getRepository('AppBundle:OperatoreUser')->findAll();

    return array(
      'user' => $this->getUser(),
      'operatoreUsers' => $operatoreUsers,
    );
  }

  /**
   * Creates a new operatoreUser entity.
   * @Template()
   * @Route("/operatore/new", name="admin_operatore_new")
   * @Method({"GET", "POST"})
   * @param Request $request
   * @return array|RedirectResponse
   */
  public function newOperatoreAction(Request $request)
  {
    $operatoreUser = new Operatoreuser();
    $form = $this->createForm('AppBundle\Form\OperatoreUserType', $operatoreUser);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      $em = $this->getDoctrine()->getManager();
      $ente = $this->instanceService->getCurrentInstance();

      $operatoreUser
        ->setEnte($ente)
        ->setPlainPassword(md5(time()))
        ->setConfirmationToken($this->tokenGenerator->generateToken())
        ->setPasswordRequestedAt(new \DateTime())
        ->setEnabled(true);
      $em->persist($operatoreUser);
      $em->flush();

      $mailer = $this->get('fos_user.mailer');
      $mailer->sendResettingEmailMessage($operatoreUser);

      $this->addFlash('feedback', 'Operatore creato con successo');
      return $this->redirectToRoute('admin_operatore_show', array('id' => $operatoreUser->getId()));
    }

    return array(
      'user' => $this->getUser(),
      'operatoreUser' => $operatoreUser,
      'form' => $form->createView(),
    );
  }

  /**
   * Finds and displays a operatoreUser entity.
   * @Template()
   * @Route("/operatore/{id}", name="admin_operatore_show")
   * @Method("GET")
   */
  public function showOperatoreAction(OperatoreUser $operatoreUser)
  {
    if ($operatoreUser->getServiziAbilitati()->count() > 0) {
      $serviziAbilitati = $this->getDoctrine()
        ->getRepository(Servizio::class)
        ->findBy(['id' => $operatoreUser->getServiziAbilitati()->toArray()]);
    }else{
      $serviziAbilitati = [];
    }

    return array(
      'user' => $this->getUser(),
      'operatoreUser' => $operatoreUser,
      'servizi_abilitati' => $serviziAbilitati
    );
  }

  /**
   * Displays a form to edit an existing operatoreUser entity.
   * @Template()
   * @Route("/operatore/{id}/edit", name="admin_operatore_edit")
   * @Method({"GET", "POST"})
   */
  public function editOperatoreAction(Request $request, OperatoreUser $operatoreUser)
  {
    $editForm = $this->createForm('AppBundle\Form\OperatoreUserType', $operatoreUser);
    $editForm->handleRequest($request);

    if ($editForm->isSubmitted() && $editForm->isValid()) {
      $this->getDoctrine()->getManager()->flush();

      return $this->redirectToRoute('admin_operatore_edit', array('id' => $operatoreUser->getId()));
    }

    return array(
      'user' => $this->getUser(),
      'operatoreUser' => $operatoreUser,
      'edit_form' => $editForm->createView()
    );
  }

  /**
   * Send password reset hash to user.
   * @Route("/operatore/{id}/resetpassword", name="admin_operatore_reset_password")
   * @Method({"GET", "POST"})
   */
  public function resetPasswordOperatoreAction(Request $request, OperatoreUser $operatoreUser)
  {
    $em = $this->getDoctrine()->getManager();
    $operatoreUser
      ->setConfirmationToken($this->tokenGenerator->generateToken())
      ->setPasswordRequestedAt(new \DateTime());
    $em->persist($operatoreUser);
    $em->flush();

    $mailer = $this->get('fos_user.mailer');
    $mailer->sendResettingEmailMessage($operatoreUser);

    return $this->redirectToRoute('admin_operatore_edit', array('id' => $operatoreUser->getId()));
  }

  /**
   * Deletes a operatoreUser entity.
   * @Route("/operatore/{id}/delete", name="admin_operatore_delete")
   * @Method({"GET", "POST", "DELETE"})
   */
  public function deleteOperatoreAction(Request $request, OperatoreUser $operatoreUser)
  {
    try {
      $em = $this->getDoctrine()->getManager();
      $em->remove($operatoreUser);
      $em->flush();
      $this->addFlash('feedback', 'Operatore eliminato correttamente');
      return $this->redirectToRoute('admin_operatore_index');

    } catch (ForeignKeyConstraintViolationException $exception) {
      $this->addFlash('warning', 'Impossibile eliminare l\'operatore, ci sono delle pratiche collegate.');
      return $this->redirectToRoute('admin_servizio_index');
    }
  }


  /**
   * Lists all operatoreLogs entities.
   * @Template()
   * @Route("/logs", name="admin_logs_index")
   * @Method({"GET", "POST"})
   */
  public function indexLogsAction(Request $request)
  {
    $table = $this->createDataTable()
      ->add('type', TextColumn::class, ['label' => 'Evento'])
      ->add('eventTime', DateTimeColumn::class, ['label' => 'Data', 'format' => 'd-m-Y H:i', 'searchable' => false])
      ->add('user', TextColumn::class, ['label' => 'Utente'])
      ->add('description', TextColumn::class, ['label' => 'Descrizione'])
      ->add('ip', TextColumn::class, ['label' => 'Ip'])
      ->createAdapter(ORMAdapter::class, [
        'entity' => AuditLog::class,
      ])
      ->handleRequest($request);

    if ($table->isCallback()) {
      return $table->getResponse();
    }

    return array(
      'user' => $this->getUser(),
      'datatable' => $table
    );
  }


  /**
   * Lists all operatoreUser entities.
   * @Template()
   * @Route("/servizio", name="admin_servizio_index")
   * @Method("GET")
   */
  public function indexServizioAction()
  {
    $statuses = [
      Servizio::STATUS_CANCELLED => $this->translator->trans('servizio.statutes.bozza'),
      Servizio::STATUS_AVAILABLE => $this->translator->trans('servizio.statutes.pubblicato'),
      Servizio::STATUS_SUSPENDED => $this->translator->trans('servizio.statutes.sospeso'),
      Servizio::STATUS_PRIVATE => $this->translator->trans('servizio.statutes.privato'),
      Servizio::STATUS_SCHEDULED => $this->translator->trans('servizio.statutes.schedulato'),
    ];

    $accessLevels = [
      Servizio::ACCESS_LEVEL_ANONYMOUS => 'Anonimo',
      Servizio::ACCESS_LEVEL_SOCIAL => 'Social',
      Servizio::ACCESS_LEVEL_SPID_L1 => 'Spid livello 1',
      Servizio::ACCESS_LEVEL_SPID_L2 => 'Spid livello 2',
      Servizio::ACCESS_LEVEL_CIE => 'Cie',
    ];

    $em = $this->getDoctrine()->getManager();
    $items = $em->getRepository('AppBundle:Servizio')->findBy([], ['name' => 'ASC']);

    return array(
      'user' => $this->getUser(),
      'items' => $items,
      'statuses' => $statuses,
      'access_levels' => $accessLevels,
    );
  }

  /**
   * Lists all operatoreUser entities.
   * @Route("/servizio/list", name="admin_servizio_list")
   * @Method("GET")
   */
  public function listServizioAction()
  {

    $em = $this->getDoctrine()->getManager();
    $items = $em->getRepository('AppBundle:Servizio')->findBy(['praticaFCQN' => '\AppBundle\Entity\FormIO'], ['name' => 'ASC']);

    $data = [];
    foreach ($items as $s) {
      $descLimit = 150;
      $description = strip_tags($s->getDescription());
      if (strlen($description) > $descLimit) {
        $description = substr($description, 0, $descLimit) . '...';
      }
      $data [] = [
        'id' => $s->getId(),
        'title' => $s->getName(),
        'description' => $description
      ];
    }

    return new JsonResponse($data);
  }

  /**
   * @Route("/servizio/import", name="admin_servizio_import")
   * @param Request $request
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function importServizioAction(Request $request)
  {
    $em = $this->getDoctrine()->getManager();
    $ente = $this->instanceService->getCurrentInstance();

    $remoteUrl = $request->get('url');
    $client = new Client();
    $request = new \GuzzleHttp\Psr7\Request(
      'GET',
      $remoteUrl,
      ['Content-Type' => 'application/json']
    );

    try {
      $response = $client->send($request);

      if ($response->getStatusCode() == 200) {
        $responseBody = json_decode($response->getBody(), true);
        $responseBody['tenant'] = $ente->getId();

        $serviceDto = new Service();
        $form = $this->createForm('AppBundle\Form\ServizioFormType', $serviceDto);
        unset($responseBody['id'], $responseBody['slug']);

        $data = Service::normalizeData($responseBody);
        $form->submit($data, true);

        if ($form->isSubmitted() && !$form->isValid()) {
          $this->addFlash('error', 'Si è verificato un problema in fase di importazione.');
          return $this->redirectToRoute('admin_servizio_index');
        }

        $category = $em->getRepository('AppBundle:Categoria')->findOneBy(['slug' => $serviceDto->getTopics()]);
        if ($category instanceof Categoria) {
          $serviceDto->setTopics($category);
        }

        $service = $serviceDto->toEntity();
        $service->setName($service->getName() . ' (importato ' . date('d/m/Y H:i:s') . ')');
        $service->setPraticaFCQN('\AppBundle\Entity\FormIO');
        $service->setPraticaFlowServiceName('ocsdc.form.flow.formio');
        $service->setEnte($ente);
        // Erogatore
        $erogatore = new Erogatore();
        $erogatore->setName('Erogatore di ' . $service->getName() . ' per ' . $ente->getName());
        $erogatore->addEnte($ente);
        $em->persist($erogatore);
        $service->activateForErogatore($erogatore);
        $em->persist($service);
        $em->flush();

        if (!empty($service->getFormIoId())) {
          $response = $this->formServer->cloneFormFromRemote( $service, $remoteUrl .'/form');
          if ($response['status'] == 'success') {
            $formId = $response['form_id'];
            $flowStep = new FlowStep();
            $flowStep
              ->setIdentifier($formId)
              ->setType('formio')
              ->addParameter('formio_id', $formId);
            $service->setFlowSteps([$flowStep]);
            // Backup
            $additionalData = $service->getAdditionalData();
            $additionalData['formio_id'] = $formId;
            $service->setAdditionalData($additionalData);
          } else {
            $em->remove($service);
            $em->flush();
            $this->addFlash('error', 'Si è verificato un problema in fase creazione del form.');
            return $this->redirectToRoute('admin_servizio_index');
          }
        }

        $em->persist($service);
        $em->flush();

        $this->addFlash('success', 'Servizio importato corettamente');
        return $this->redirectToRoute('admin_servizio_index');

      }
    } catch (\Exception $e) {
      $this->addFlash('error', $e->getMessage());
      $this->addFlash('error', 'Si è verificato un problema in fase creazione del form.');
    }
    return $this->redirectToRoute('admin_servizio_index');
  }

  /**
   * @Route("/servizio/{servizio}/edit", name="admin_servizio_edit")
   * @ParamConverter("servizio", class="AppBundle:Servizio")
   * @Template()
   * @param Servizio $servizio
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function editServizioAction(Servizio $servizio)
  {
    $user = $this->getUser();
    $flowService = $this->serviceFlow;
    $flowService->setInstanceKey($user->getId());
    $flowService->bind($servizio);

    $form = $flowService->createForm();
    if ($flowService->isValid($form)) {

        $flowService->saveCurrentStepData($form);

        if ($flowService->nextStep()) {
          try {
            $this->getDoctrine()->getManager()->flush();
          }catch (UniqueConstraintViolationException $e){
            $this->addFlash('error', 'Controlla se esiste un servizio con lo stesso nome e di aver inserito correttamente tutti i campi obbligatori');
            $this->addFlash('error', 'Si è verificato un problema in fase creazione del form.');
            return $this->redirectToRoute('admin_servizio_edit', ['servizio' => $servizio->getId()]);
          }
          catch (\Exception $e) {
            $this->addFlash('error', 'Si è verificato un errore, contatta il supporto tecnico');
            return $this->redirectToRoute('admin_servizio_index', ['servizio' => $servizio]);
          }
        $form = $flowService->createForm();
      } else {

        // Retrocompatibilità --> salvo i parametri dei protocollo nell'ente
        $ente = $servizio->getEnte();
        $ente->setProtocolloParametersPerServizio($servizio->getProtocolloParameters(), $servizio);

        $this->getDoctrine()->getManager()->flush();
        $flowService->getDataManager()->drop($flowService);
        $flowService->reset();

        $this->addFlash('feedback', 'Servizio modificato correttamente');

        return $this->redirectToRoute('admin_servizio_index', ['servizio' => $servizio]);
      }
    }

    return [
      'form' => $form->createView(),
      //'test_form' => $testForm->getForm()->createView(),
      'servizio' => $flowService->getFormData(),
      'flow' => $flowService,
      'formserver_url' => $this->getParameter('formserver_public_url'),
      'user' => $user
    ];
  }

  /**
   * @Route("/servizio/{servizio}/custom-validation", name="admin_servizio_custom_validation")
   * @ParamConverter("servizio", class="AppBundle:Servizio")
   * @Template()
   * @param Request $request
   * @param Servizio $servizio
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function editCustomValidationServizioAction(Request $request, Servizio $servizio)
  {
    $user = $this->getUser();

    $schema = $this->schemaFactory->createFromFormId($servizio->getFormIoId());

    $form = $this->createFormBuilder(null)->add(
      "post_submit_validation_expression", TextareaType::class, [
      "label" => 'Validazione al submit',
      'required' => false,
      'data' => $servizio->getPostSubmitValidationExpression()
    ])->add(
      "post_submit_validation_message", TextType::class, [
      "label" => 'Messaggio di errore in caso di mancata validazione',
      'required' => false,
      'data' => $servizio->getPostSubmitValidationMessage()
    ])->add(
      'Save', SubmitType::class
    )->getForm()->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      $data = $form->getData();
      $servizio->setPostSubmitValidationExpression($data['post_submit_validation_expression']);
      $servizio->setPostSubmitValidationMessage($data['post_submit_validation_message']);
      $entityManager = $this->getDoctrine()->getManager();
      $entityManager->persist($servizio);
      $entityManager->flush();
      $this->addFlash('feedback', 'Validazione salvata correttamente');

      return $this->redirectToRoute('admin_servizio_custom_validation', ['servizio' => $servizio->getId()]);
    }

    return [
      'form' => $form->createView(),
      'servizio' => $servizio,
      'user' => $user,
      'schema' => $schema,
      'statuses' => Pratica::getStatuses()
    ];
  }

  /**
   * Creates a new Service entity.
   * @Route("/servizio/new", name="admin_service_new")
   * @Method({"GET", "POST"})
   * @param Request $request
   * @return RedirectResponse|Response|null
   */
  public function newServiceAction(Request $request)
  {

    $servizio = new Servizio();
    $ente = $this->instanceService->getCurrentInstance();

    $servizio->setName('Nuovo Servizio ' . time());
    $servizio->setPraticaFCQN('\AppBundle\Entity\FormIO');
    $servizio->setPraticaFlowServiceName('ocsdc.form.flow.formio');
    $servizio->setEnte($ente);
    $servizio->setStatus(Servizio::STATUS_CANCELLED);

    // Erogatore
    $erogatore = new Erogatore();
    $erogatore->setName('Erogatore di ' . $servizio->getName() . ' per ' . $ente->getName());
    $erogatore->addEnte($ente);
    $this->getDoctrine()->getManager()->persist($erogatore);
    $servizio->activateForErogatore($erogatore);

    $this->getDoctrine()->getManager()->persist($servizio);
    $this->getDoctrine()->getManager()->flush();

    $user = $this->getUser();
    $flowService = $this->serviceFlow;

    $flowService->setInstanceKey($user->getId());

    $flowService->bind($servizio);

    $form = $flowService->createForm();
    if ($flowService->isValid($form)) {

      $flowService->saveCurrentStepData($form);
      //$servizio->setLastCompiledStep($flowService->getCurrentStepNumber());

      if ($flowService->nextStep()) {
        $this->getDoctrine()->getManager()->flush();
        $form = $flowService->createForm();
      } else {

        // Retrocompatibilità --> salvo i parametri dei protocollo nell'ente
        $ente = $servizio->getEnte();
        $ente->setProtocolloParametersPerServizio($servizio->getProtocolloParameters(), $servizio);
        $this->getDoctrine()->getManager()->flush();
        $flowService->getDataManager()->drop($flowService);
        $flowService->reset();

        $this->addFlash('feedback', 'Servizio creato correttamente');

        return $this->redirectToRoute('admin_servizio_index', ['servizio' => $servizio]);
      }
    }

    return $this->render('@App/Admin/editServizio.html.twig', [
      'form' => $form->createView(),
      'servizio' => $flowService->getFormData(),
      'flow' => $flowService,
      'formserver_url' => $this->getParameter('formserver_public_url'),
      'user' => $user
    ]);

  }

  /**
   * Deletes a service entity.
   * @Route("/servizio/{id}/delete", name="admin_servizio_delete")
   * @Method("GET")
   */
  public function deleteServiceAction(Request $request, Servizio $servizio)
  {

    try {
      if ($servizio->getPraticaFCQN() == '\AppBundle\Entity\FormIO') {
        $this->formServer->deleteForm($servizio);
      }

      $em = $this->getDoctrine()->getManager();
      $em->remove($servizio);
      $em->flush();

      $this->addFlash('feedback', 'Servizio eliminato correttamente');

      return $this->redirectToRoute('admin_servizio_index');
    } catch (ForeignKeyConstraintViolationException $exception) {
      $this->addFlash('warning', 'Impossibile eliminare il servizio, ci sono delle pratiche collegate.');
      return $this->redirectToRoute('admin_servizio_index');
    }

  }

  /**
   * @Route("/servizio/{servizio}/schema", name="admin_servizio_schema_edit")
   * @ParamConverter("servizio", class="AppBundle:Servizio")
   * @param Request $request
   * @param Servizio $servizio
   * @return JsonResponse
   */
  public function formioValidateAction(Request $request, Servizio $servizio)
  {

    $data = $request->get('schema');
    if (!empty($data)) {
      $schema = \json_decode($data, true);

      try {
        $response = $this->formServer->editForm($schema);
        return JsonResponse::create($response, Response::HTTP_OK);
      } catch (\Exception $exception) {
        return JsonResponse::create($exception->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
      }
    }
  }

  /**
   * @param FormInterface $form
   * @return array
   */
  private function getErrorsFromForm(FormInterface $form)
  {
    $errors = array();
    foreach ($form->getErrors() as $error) {
      $errors[] = $error->getMessage();
    }
    foreach ($form->all() as $childForm) {
      if ($childForm instanceof FormInterface) {
        if ($childErrors = $this->getErrorsFromForm($childForm)) {
          $errors[] = $childErrors;
        }
      }
    }
    return $errors;
  }

  /**
   * @Route("/io-test", name="test_io")
   * @Method({"POST"})
   * @param Request $request
   *
   * @return array|JsonResponse
   */
  public function testIo(Request $request)
  {
    $serviceId=$request->get('service_id');
    $primaryKey=$request->get('primary_key');
    $secondaryKey=$request->get('secondary_key');
    $fiscalCode=$request->get('fiscal_code');

    if (!($serviceId && $primaryKey && $secondaryKey && $fiscalCode)) {
      return new JsonResponse(
        ["error" => $this->translator->trans('app_io.errore.parametro_mancante')],
        Response::HTTP_BAD_REQUEST);
    }

    $response = $this->ioService->test($serviceId, $primaryKey, $secondaryKey, $fiscalCode);
    if (key_exists('error', $response)) {
      return new JsonResponse($response, Response::HTTP_BAD_REQUEST);
    } else {
      return new JsonResponse($response, Response::HTTP_OK);
    }
  }

}


