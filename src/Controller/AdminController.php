<?php

namespace App\Controller;

use App\Controller\Rest\ServicesAPIController;
use App\Dto\Service;
use App\Entity\AuditLog;
use App\Entity\Categoria;
use App\Entity\Erogatore;
use App\Entity\OperatoreUser;
use App\Entity\Servizio;
use App\Form\Admin\ServiceFlow;
use App\Model\FlowStep;
use App\Multitenancy\Annotations\MustHaveTenant;
use App\Multitenancy\TenantAwareController;
use App\Repository\UserRepository;
use App\Services\FormServerApiAdapterService;
use App\Services\InstanceService;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use GuzzleHttp\Client;
use Omines\DataTablesBundle\Adapter\Doctrine\ORMAdapter;
use Omines\DataTablesBundle\Column\DateTimeColumn;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\DataTableFactory;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/**
 * Class AdminController
 * @Route("/admin")
 * @MustHaveTenant()
 */
class AdminController extends TenantAwareController
{
    private $formserver;

    private $dataTableFactory;

    private $instanceService;

    private $tokenGenerator;

    public function __construct(
        FormServerApiAdapterService $formserver,
        DataTableFactory $dataTableFactory,
        InstanceService $instanceService,
        TokenGeneratorInterface $tokenGenerator
    )
    {
        $this->formserver = $formserver;
        $this->dataTableFactory = $dataTableFactory;
        $this->instanceService = $instanceService;
        $this->tokenGenerator = $tokenGenerator;
    }

    /**
     * @Route("/", name="admin_index")
     */
    public function index()
    {
        return $this->render('Admin/index.html.twig', [
            'user' => $this->getUser()
        ]);
    }

    /**
     * @Route("/ente", name="admin_edit_ente")
     * @param Request $request
     * @return Response
     */
    public function editEnte(Request $request)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $ente = $this->instanceService->getCurrentInstance();
        $form = $this->createForm('App\Form\Admin\Ente\EnteType', $ente);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // $form->getData() holds the submitted values
            // but, the original `$task` variable has also been updated
            $ente = $form->getData();

            // ... perform some action, such as saving the task to the database
            // for example, if Task is a Doctrine entity, save it!

            $entityManager->persist($ente);
            $entityManager->flush();

            return $this->redirectToRoute('admin_edit_ente');
        }

        return $this->render('Admin/editEnte.html.twig', [
            'user' => $this->getUser(),
            'ente' => $ente,
            'form' => $form->createView()
        ]);
    }


    /**
     * Lists all operatoreUser entities.
     * @Route("/operatore", name="admin_operatore_index", methods={"GET"})
     */
    public function indexOperatore()
    {
        $em = $this->getDoctrine()->getManager();

        $operatoreUsers = $this->getOperatoreRepository()->findAll();

        return $this->render('Admin/indexOperatore.html.twig', [
            'user' => $this->getUser(),
            'operatoreUsers' => $operatoreUsers,
        ]);
    }

    /**
     * Creates a new operatoreUser entity.
     * @Route("/operatore/new", name="admin_operatore_new", methods={"GET","POST"})
     * @param Request $request
     * @param InstanceService $instanceService
     * @return RedirectResponse|Response
     * @throws \Exception
     */
    public function newOperatore(Request $request, InstanceService $instanceService)
    {
        $operatoreUser = new OperatoreUser();
        $form = $this->createForm('App\Form\OperatoreUserType', $operatoreUser);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $ente = $instanceService->getCurrentInstance();

            $operatoreUser
                ->setEnte($ente)
                ->setPlainPassword(md5(time()))
                ->setConfirmationToken($this->tokenGenerator->generateToken())
                ->setPasswordRequestedAt(new \DateTime())
                ->setEnabled(true);

            $this->getOperatoreRepository()->updateUser($operatoreUser);

            $this->addFlash('feedback', 'Operatore creato con successo');
            return $this->redirectToRoute('admin_operatore_show', array('id' => $operatoreUser->getId()));
        }

        return $this->render('Admin/newOperatore.html.twig', [
            'user' => $this->getUser(),
            'operatoreUser' => $operatoreUser,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Finds and displays a operatoreUser entity.
     * @Route("/operatore/{id}", name="admin_operatore_show", methods={"GET"})
     * @param OperatoreUser $operatoreUser
     * @return Response
     */
    public function showOperatore(OperatoreUser $operatoreUser)
    {
        return $this->render('Admin/showOperatore.html.twig', [
            'user' => $this->getUser(),
            'operatoreUser' => $operatoreUser
        ]);
    }

    /**
     * Displays a form to edit an existing operatoreUser entity.
     * @Route("/operatore/{id}/edit", name="admin_operatore_edit", methods={"GET","POST"})
     * @param Request $request
     * @param OperatoreUser $operatoreUser
     * @return RedirectResponse|Response
     */
    public function editOperatore(Request $request, OperatoreUser $operatoreUser)
    {
        $editForm = $this->createForm('App\Form\OperatoreUserType', $operatoreUser);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('admin_operatore_edit', array('id' => $operatoreUser->getId()));
        }

        return $this->render('Admin/editOperatore.html.twig', [
            'user' => $this->getUser(),
            'operatoreUser' => $operatoreUser,
            'edit_form' => $editForm->createView()
        ]);
    }

    /**
     * Send password reset hash to user.
     * @Route("/operatore/{id}/resetpassword", name="admin_operatore_reset_password", methods={"GET","POST"})
     * @param OperatoreUser $operatoreUser
     * @return RedirectResponse
     * @throws \Exception
     */
    public function resetPasswordOperatore(OperatoreUser $operatoreUser)
    {
        $em = $this->getDoctrine()->getManager();
        $operatoreUser
            ->setConfirmationToken($this->tokenGenerator->generateToken())
            ->setPasswordRequestedAt(new \DateTime());

        $this->getOperatoreRepository()->updateUser($operatoreUser);

        return $this->redirectToRoute('admin_operatore_edit', array('id' => $operatoreUser->getId()));
    }

    /**
     * Deletes a operatoreUser entity.
     * @Route("/operatore/{id}/delete", name="admin_operatore_delete", methods={"GET","POST","DELETE"})
     * @param OperatoreUser $operatoreUser
     * @return RedirectResponse
     */
    public function deleteOperatore(OperatoreUser $operatoreUser)
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
     * @Route("/logs", name="admin_logs_index", methods={"GET","POST"})
     * @param Request $request
     * @return JsonResponse|Response
     */
    public function indexLogs(Request $request)
    {
        $table = $this->dataTableFactory->create()
            ->add('type', TextColumn::class, ['label' => 'Evento'])
            ->add('eventTime', DateTimeColumn::class, ['label' => 'Data', 'format' => 'd-m-Y H:i'])
            ->add('user', TextColumn::class, ['label' => 'Utente'])
            ->add('ip', TextColumn::class, ['label' => 'Ip'])
            ->createAdapter(ORMAdapter::class, [
                'entity' => AuditLog::class,
            ])
            ->handleRequest($request);

        if ($table->isCallback()) {
            return $table->getResponse();
        }

        return $this->render('Admin/indexLogs.html.twig', [
            'user' => $this->getUser(),
            'datatable' => $table
        ]);
    }

    /**
     * Lists all operatoreUser entities.
     * @Route("/servizio", name="admin_servizio_index", methods={"GET"})
     * @return Response
     */
    public function indexServizio()
    {
        $statuses = [
            Servizio::STATUS_CANCELLED => 'Bozza',
            Servizio::STATUS_AVAILABLE => 'Pubblicato',
            Servizio::STATUS_SUSPENDED => 'Sospeso'
        ];

        $em = $this->getDoctrine()->getManager();
        $items = $em->getRepository('App:Servizio')->findBy([], ['name' => 'ASC']);

        return $this->render('Admin/indexServizio.html.twig', [
            'user' => $this->getUser(),
            'items' => $items,
            'statuses' => $statuses
        ]);
    }

    /**
     * Lists all operatoreUser entities.
     * @Route("/servizio/list", name="admin_servizio_list", methods={"GET"})
     * @return JsonResponse
     */
    public function listServizio()
    {
        $em = $this->getDoctrine()->getManager();
        /** @var Servizio[] $items */
        $items = $em->getRepository('App:Servizio')->findBy(['praticaFCQN' => '\App\Entity\FormIO'], ['name' => 'ASC']);

        $data = [];
        foreach ($items as $s) {
            $data [] = [
                'id' => $s->getId(),
                'title' => $s->getName(),
                'description' => $s->getDescription()
            ];
        }

        return new JsonResponse($data);
    }

    /**
     * @Route("/servizio/import", name="admin_servizio_import")
     * @param Request $request
     * @param InstanceService $instanceService
     * @param ServicesAPIController $serviceApi
     * @return RedirectResponse
     */
    public function importServizio(Request $request, InstanceService $instanceService, ServicesAPIController $serviceApi)
    {
        $em = $this->getDoctrine()->getManager();
        $ente = $instanceService->getCurrentInstance();

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
                $form = $this->createForm('App\Form\ServizioFormType', $serviceDto);
                unset($responseBody['id'], $responseBody['slug']);

                $data = $serviceApi->normalizeData($responseBody);
                $form->submit($data, true);

                if (!$form->isValid()) {
                    $this->addFlash('error', 'Si è verificato un problema in fase di importazione.');

                    return $this->redirectToRoute('admin_servizio_index');
                }

                $category = $em->getRepository('App:Categoria')->findOneBy(['slug' => $serviceDto->getTopics()]);
                if ($category instanceof Categoria) {
                    $serviceDto->setTopics($category);
                }

                $service = $serviceDto->toEntity();
                $service->setName($service->getName() . ' (importato ' . date('d/m/Y H:i:s') . ')');
                $service->setPraticaFCQN('\App\Entity\FormIO');
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
                    $response = $this->formserver->cloneFormFromRemote($service, $remoteUrl . '/form');
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
     * @ParamConverter("servizio", class="App:Servizio")
     * @param Servizio $servizio
     * @param ServiceFlow $flowService
     * @return RedirectResponse|Response
     */
    public function editServizio(Servizio $servizio, ServiceFlow $flowService)
    {
        $user = $this->getUser();
        $flowService->setInstanceKey($user->getId());
        $flowService->bind($servizio);

        $form = $flowService->createForm();
        if ($flowService->isValid($form)) {
            $flowService->saveCurrentStepData($form);

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

                $this->addFlash('feedback', 'Servizio modificato correttamente');

                return $this->redirectToRoute('admin_servizio_index', ['servizio' => $servizio]);
            }
        }

        return $this->render('Admin/editServizio.html.twig', [
            'form' => $form->createView(),
            'servizio' => $flowService->getFormData(),
            'flow' => $flowService,
            'formserver_url' => $this->getParameter('formserver_public_url'),
            'user' => $user
        ]);
    }

    /**
     * Creates a new Service entity.
     * @Route("/servizio/new", name="admin_service_new", methods={"GET", "POST"})
     * @param InstanceService $instanceService
     * @param ServiceFlow $flowService
     * @return RedirectResponse|Response
     */
    public function newService(InstanceService $instanceService, ServiceFlow $flowService)
    {
        $servizio = new Servizio();
        $ente = $instanceService->getCurrentInstance();

        $servizio->setName('Nuovo Servizio ' . time());
        $servizio->setPraticaFCQN('\App\Entity\FormIO');
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

        return $this->render('Admin/editServizio.html.twig', [
            'form' => $form->createView(),
            'servizio' => $flowService->getFormData(),
            'flow' => $flowService,
            'formserver_url' => $this->getParameter('formserver_public_url'),
            'user' => $user
        ]);
    }

    /**
     * Deletes a service entity.
     * @Route("/servizio/{id}/delete", name="admin_servizio_delete", methods={"GET"})
     * @param Servizio $servizio
     * @return RedirectResponse
     */
    public function deleteService(Servizio $servizio)
    {
        try {
            if ($servizio->getPraticaFCQN() == '\App\Entity\FormIO') {
                $this->formserver->deleteForm($servizio);
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
     * @ParamConverter("servizio", class="App:Servizio")
     * @param Request $request
     * @return JsonResponse
     */
    public function formioValidate(Request $request)
    {
        $data = $request->get('schema');
        if (!empty($data)) {
            $schema = \json_decode($data, true);
            try {
                $response = $this->formserver->editForm($schema);
                return JsonResponse::create($response, Response::HTTP_OK);
            } catch (\Exception $exception) {
                return JsonResponse::create($exception->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }

        return null; //@todo ??
    }

    /**
     * @Route("/login", name="admin_login")
     * @param AuthenticationUtils $authenticationUtils
     * @return Response
     */
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // if ($this->getUser()) {
        //     return $this->redirectToRoute('target_path');
        // }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('Admin/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    /**
     * @Route("/logout", name="admin_logout")
     */
    public function logout()
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    /**
     * @Route("/profile", name="admin_user_profile_show")
     */
    public function profile()
    {
        throw new \LogicException('This method can be blank');
    }

    /**
     * @param FormInterface $form
     * @return array
     */
    private function getErrorsFromForm(FormInterface $form)
    {
        $errors = array();
        foreach ($form->getErrors() as $error) {
            dump($error);
            $errors[] = $error->getMessage();
        }
        foreach ($form->all() as $childForm) {
            if ($childForm instanceof FormInterface) {
                if ($childErrors = $this->getErrorsFromForm($childForm)) {
                    dump($childForm);
                    $errors[] = $childErrors;
                }
            }
        }
        return $errors;
    }

    /**
     * @return \Doctrine\Persistence\ObjectRepository|UserRepository
     */
    private function getOperatoreRepository()
    {
        return $this->getDoctrine()->getRepository(OperatoreUser::class);
    }
}
