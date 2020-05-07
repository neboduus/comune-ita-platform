<?php

namespace App\Controller;

use App\Entity\Allegato;
use App\Entity\Ente;
use App\Entity\OperatoreUser;
use App\Entity\Pratica;
use App\Entity\User;
use App\Event\ChangePasswordCompletedEvent;
use App\Event\ChangePasswordInitializeEvent;
use App\Event\ChangePasswordSuccessEvent;
use App\Form\Base\MessageType;
use App\Form\ChangePasswordFormType;
use App\Form\ResetPasswordFormType;
use App\Form\Operatore\Base\PraticaOperatoreFlow;
use App\Logging\LogConstants;
use App\Multitenancy\Annotations\MustHaveTenant;
use App\Multitenancy\TenantAwareController;
use App\Repository\PraticaRepository;
use App\Services\InstanceService;
use App\Services\MessagesAdapterService;
use App\Services\ModuloPdfBuilderService;
use App\Services\PraticaFlowRegistry;
use App\Services\PraticaStatusService;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Core\Security as CoreSecurity;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class OperatoriController
 * @Route("/operatori")
 * @MustHaveTenant
 */
class OperatoriController extends TenantAwareController
{
    private $pdfBuilderService;

    private $logger;

    private $translator;

    public function __construct(
        ModuloPdfBuilderService $pdfBuilderService,
        LoggerInterface $logger,
        TranslatorInterface $translator
    )
    {
        $this->pdfBuilderService = $pdfBuilderService;
        $this->logger = $logger;
        $this->translator = $translator;
    }

    /**
     * @Route("/",name="operatori_index")
     * @return Response
     */
    public function index()
    {
        /** @var PraticaRepository $praticheRepo */
        $praticheRepo = $this->getDoctrine()->getRepository('App:Pratica');
        /** @var OperatoreUser $user */
        $user = $this->getUser();
        /** @var Ente $ente */
        $ente = $user->getEnte();

        $praticheMie = $praticheRepo->findPraticheAssignedToOperatore($user);
        $praticheLibere = $praticheRepo->findPraticheUnAssignedByEnte($ente);
        $praticheConcluse = $praticheRepo->findPraticheCompletedByOperatore($user);
        $praticheEnte = $praticheRepo->findPraticheByEnte($ente);

        return $this->render('Operatori/index.html.twig', [
            'pratiche_mie' => $praticheMie,
            'pratiche_libere' => $praticheLibere,
            'pratiche_concluse' => $praticheConcluse,
            'pratiche_ente' => $praticheEnte,
            'user' => $this->getUser(),
        ]);
    }

    /**
     * @Route("/usage",name="operatori_usage")
     * @param InstanceService $instanceService
     * @return Response
     */
    public function usage(InstanceService $instanceService)
    {
        /** @var PraticaRepository $repo */
        $repo = $this->getDoctrine()->getRepository(Pratica::class);
        $pratiche = $repo->findSubmittedPraticheByEnte($instanceService->getCurrentInstance());

        $serviziRepository = $this->getDoctrine()->getRepository('App:Servizio');
        $servizi = $serviziRepository->findBy(
            [
                'status' => [1]
            ]
        );

        $count = array_reduce($pratiche, function ($acc, Pratica $el) {
            $year = (new \DateTime())->setTimestamp($el->getSubmissionTime())->format('Y');
            try {
                $acc[$year]++;
            } catch (\Exception $e) {
                $acc[$year] = 1;
            }

            return $acc;
        }, []);

        return $this->render('Operatori/usage.html.twig', [
            'servizi' => count($servizi),
            'pratiche' => $count,
            'user' => $this->getUser()
        ]);
    }

    /**
     * @Route("/{pratica}/protocollo", name="operatori_pratiche_show_protocolli")
     * @param Request $request
     * @param Pratica $pratica
     * @param MessagesAdapterService $messagesAdapterService
     * @return Response
     * @throws \ReflectionException
     */
    public function showProtocolli(Request $request, Pratica $pratica, MessagesAdapterService $messagesAdapterService)
    {
        /** @var OperatoreUser $user */
        $user = $this->getUser();
        $this->checkUserCanAccessPratica($user, $pratica);
        $threads = $this->createThreadElementsForOperatoreAndPratica($user, $pratica, $messagesAdapterService);

        $allegati = [];
        foreach ($pratica->getNumeriProtocollo() as $protocollo) {
            $allegato = $this->getDoctrine()->getRepository('App:Allegato')->find($protocollo->id);
            if ($allegato instanceof Allegato) {
                $allegati[] = [
                    'allegato' => $allegato,
                    'tipo' => (new \ReflectionClass(get_class($allegato)))->getShortName(),
                    'protocollo' => $protocollo->protocollo
                ];
            }
        }

        return $this->render('Operatori/showProtocolli.html.twig', [
            'pratica' => $pratica,
            'allegati' => $allegati,
            'user' => $user,
            'threads' => $threads,
        ]);
    }

    /**
     * @param OperatoreUser $user
     * @param Pratica $pratica
     */
    private function checkUserCanAccessPratica(OperatoreUser $user, Pratica $pratica)
    {
        $operatore = $pratica->getOperatore();
        if (!$operatore instanceof OperatoreUser || $operatore->getId() !== $user->getId()) {
            throw new UnauthorizedHttpException("User can not read pratica {$pratica->getId()}");
        }
    }

    /**
     * @param OperatoreUser $operatore
     * @param Pratica $pratica
     * @param MessagesAdapterService $messagesAdapterService
     * @return array
     */
    private function createThreadElementsForOperatoreAndPratica(OperatoreUser $operatore, Pratica $pratica, MessagesAdapterService $messagesAdapterService)
    {
        $threadId = $pratica->getUser()->getId() . '~' . $operatore->getId();
        $form = $this->createForm(
            MessageType::class,
            ['thread_id' => $threadId, 'sender_id' => $operatore->getId()],
            [
                'action' => $this->generateUrl('messages_controller_enqueue_for_operatore', ['threadId' => $threadId]),
                'method' => 'PUT',
            ]
        );

        $threads[] = [
            'threadId' => $threadId,
            'title' => $pratica->getUser()->getFullName(),
            'messages' => $messagesAdapterService->getDecoratedMessagesForThread($threadId, $operatore),
            'form' => $form->createView(),
        ];

        return $threads;
    }

    /**
     * @Route("/parametri-protocollo", name="operatori_impostazioni_protocollo_list")
     * @param InstanceService $instanceService
     * @return Response
     */
    public function impostazioniProtocolloList(InstanceService $instanceService)
    {
        return $this->render('Operatori/impostazioniProtocollo.html.twig', [
            'parameters' => $instanceService->getCurrentInstance()->getProtocolloParameters()
        ]);
    }

    /**
     * @Route("/{pratica}/autoassign",name="operatori_autoassing_pratica")
     * @param Pratica $pratica
     * @param PraticaStatusService $praticaStatusService
     * @return RedirectResponse
     * @throws \Exception
     */
    public function autoAssignPratica(Pratica $pratica, PraticaStatusService $praticaStatusService)
    {
        if ($pratica->getOperatore() !== null) {
            throw new BadRequestHttpException("Pratica {$pratica->getId()} already assigned to {$pratica->getOperatore()->getFullName()}");
        }

        if ($pratica->getNumeroProtocollo() === null) {
            throw new BadRequestHttpException("Pratica {$pratica->getId()} does not have yet a protocol number");
        }

        /** @var OperatoreUser $user */
        $user = $this->getUser();
        $pratica->setOperatore($user);
        $praticaStatusService->setNewStatus($pratica, Pratica::STATUS_PENDING);

        $this->logger->info(
            LogConstants::PRATICA_ASSIGNED,
            [
                'pratica' => $pratica->getId(),
                'user' => $pratica->getUser()->getId(),
            ]
        );

        return $this->redirectToRoute('operatori_show_pratica', ['pratica' => $pratica]);
    }

    /**
     * @Route("/{pratica}/detail",name="operatori_show_pratica")
     * @param Pratica $pratica
     * @param Request $request
     * @param MessagesAdapterService $messagesAdapterService
     * @return RedirectResponse|Response
     */
    public function showPratica(Pratica $pratica, Request $request, MessagesAdapterService $messagesAdapterService)
    {
        /** @var OperatoreUser $user */
        $user = $this->getUser();
        $this->checkUserCanAccessPratica($user, $pratica);

        $form = $this->setupCommentForm()->handleRequest($request);

        if ($form->isSubmitted()) {
            $commento = $form->getData();
            $pratica->addCommento($commento);
            $this->getDoctrine()->getManager()->flush();

            $this->logger->info(
                LogConstants::PRATICA_COMMENTED,
                [
                    'pratica' => $pratica->getId(),
                    'user' => $pratica->getUser()->getId(),
                ]
            );

            return $this->redirectToRoute('operatori_show_pratica', ['pratica' => $pratica]);
        }

        $threads = $this->createThreadElementsForOperatoreAndPratica($user, $pratica, $messagesAdapterService);

        return $this->render('Operatori/showPratica.html.twig', [
            'form' => $form->createView(),
            'pratica' => $pratica,
            'user' => $this->getUser(),
            'threads' => $threads,
        ]);
    }

    /**
     * @return FormInterface
     */
    private function setupCommentForm()
    {
        $data = array();
        return $this->createFormBuilder($data)
            ->add('text', TextareaType::class, [
                'label' => false,
                'required' => true,
                'attr' => [
                    'rows' => '5',
                    'class' => 'form-control input-inline',
                ],
            ])
            ->add('createdAt', HiddenType::class, ['data' => time()])
            ->add('creator', HiddenType::class, [
                'data' => $this->getUser()->getFullName(),
            ])
            ->add('save', SubmitType::class, [
                'label' => $this->translator->trans('operatori.aggiungi_commento'),
                'attr' => [
                    'class' => 'btn btn-info',
                ],
            ])->getForm();
    }

    /**
     * @Route("/{pratica}/elabora",name="operatori_elabora_pratica")
     * @param Pratica $pratica
     * @param PraticaFlowRegistry $praticaFlowRegistry
     * @param PraticaStatusService $praticaStatusService
     * @return RedirectResponse|Response
     * @throws \Exception
     */
    public function elaboraPratica(Pratica $pratica, PraticaFlowRegistry $praticaFlowRegistry, PraticaStatusService $praticaStatusService)
    {
        if ($pratica->getStatus() == Pratica::STATUS_COMPLETE || $pratica->getStatus() == Pratica::STATUS_COMPLETE_WAITALLEGATIOPERATORE) {
            return $this->redirectToRoute('operatori_show_pratica', ['pratica' => $pratica]);
        }

        /** @var OperatoreUser $user */
        $user = $this->getUser();

        $this->checkUserCanAccessPratica($user, $pratica);
        $user = $this->getUser();

        $praticaFlowService = null;
        $praticaFlowServiceName = $pratica->getServizio()->getPraticaFlowOperatoreServiceName();

        if ($praticaFlowServiceName) {
            /** @var PraticaOperatoreFlow $praticaFlowService */
            $praticaFlowService = $praticaFlowRegistry->getByName($praticaFlowServiceName);
        } else {
            // Default pratica flow
            $praticaFlowService = $praticaFlowRegistry->getByName('ocsdc.form.flow.standardoperatore');
        }

        $praticaFlowService->setInstanceKey($user->getId());

        $praticaFlowService->bind($pratica);

        if ($pratica->getInstanceId() == null) {
            $pratica->setInstanceId($praticaFlowService->getInstanceId());
        }

        $form = $praticaFlowService->createForm();
        if ($praticaFlowService->isValid($form)) {
            $praticaFlowService->saveCurrentStepData($form);
            $pratica->setLastCompiledStep($praticaFlowService->getCurrentStepNumber());

            if ($praticaFlowService->nextStep()) {
                $this->getDoctrine()->getManager()->flush();
                $form = $praticaFlowService->createForm();
            } else {
                $this->completePraticaFlow($pratica, $praticaStatusService);

                $praticaFlowService->getDataManager()->drop($praticaFlowService);
                $praticaFlowService->reset();

                return $this->redirectToRoute('operatori_show_pratica', ['pratica' => $pratica]);
            }
        }

        return $this->render('Operatori/elaboraPratica.html.twig', [
            'form' => $form->createView(),
            'pratica' => $praticaFlowService->getFormData(),
            'flow' => $praticaFlowService,
            'user' => $user,
        ]);
    }

    /**
     * @param Pratica $pratica
     * @param PraticaStatusService $praticaStatusService
     * @throws \ReflectionException
     */
    private function completePraticaFlow(Pratica $pratica, PraticaStatusService $praticaStatusService)
    {
        if ($pratica->getRispostaOperatore() == null) {
            $signedResponse = $this->pdfBuilderService->createSignedResponseForPratica($pratica);
            $pratica->addRispostaOperatore($signedResponse);
        }

        if ($pratica->getEsito()) {
            $praticaStatusService->setNewStatus($pratica, Pratica::STATUS_COMPLETE_WAITALLEGATIOPERATORE);

            $this->logger->info(
                LogConstants::PRATICA_APPROVED,
                [
                    'pratica' => $pratica->getId(),
                    'user' => $pratica->getUser()->getId(),
                ]
            );
        } else {
            $praticaStatusService->setNewStatus($pratica, Pratica::STATUS_CANCELLED_WAITALLEGATIOPERATORE);

            $this->logger->info(
                LogConstants::PRATICA_CANCELLED,
                [
                    'pratica' => $pratica->getId(),
                    'user' => $pratica->getUser()->getId(),
                ]
            );
        }
    }

    /**
     * @Route("/list",name="operatori_list_by_ente")
     * @Security("has_role('ROLE_OPERATORE_ADMIN')")
     * @return Response
     */
    public function listOperatoriByEnte()
    {
        $operatoreRepo = $this->getDoctrine()->getRepository('App:OperatoreUser');
        $operatori = $operatoreRepo->findBy(
            [
                'ente' => $this->getUser()->getEnte(),
            ]
        );
        return $this->render('Operatori/listOperatoriByEnte.html.twig', [
            'operatori' => $operatori,
            'user' => $this->getUser(),
        ]);
    }

    /**
     * @Route("/detail/{operatore}",name="operatori_detail")
     * @Security("has_role('ROLE_OPERATORE_ADMIN')")
     * @param Request $request
     * @param OperatoreUser $operatore
     * @return RedirectResponse|Response
     */
    public function detailOperatore(Request $request, OperatoreUser $operatore)
    {
        /** @var OperatoreUser $user */
        $user = $this->getUser();
        $this->checkUserCanAccessOperatore($user, $operatore);
        $form = $this->setupOperatoreForm($operatore)->handleRequest($request);

        if ($form->isSubmitted()) {
            $data = $form->getData();
            //$this->storeOperatoreData($operatore->getId(), $data, $this->logger);
            $operatore->setAmbito($data['ambito']);
            $this->getDoctrine()->getManager()->persist($operatore);
            try {
                $this->getDoctrine()->getManager()->flush();
                $this->logger->info(LogConstants::OPERATORE_ADMIN_HAS_CHANGED_OPERATORE_AMBITO, ['operatore_admin' => $this->getUser()->getId(), 'operatore' => $operatore->getId()]);
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
            }
            return $this->redirectToRoute('operatori_detail', ['operatore' => $operatore->getId()]);
        }

        return $this->render('Operatori/detailOperatore.html.twig', [
            'operatore' => $operatore,
            'form' => $form->createView(),
            'user' => $this->getUser(),
        ]);
    }

    /**
     * @param OperatoreUser $user
     * @param OperatoreUser $operatore
     */
    private function checkUserCanAccessOperatore(OperatoreUser $user, OperatoreUser $operatore)
    {
        if ($user->getEnte() != $operatore->getEnte()) {
            throw new UnauthorizedHttpException("User can not read operatore {$operatore->getId()}");
        }
    }

    /**
     * @param OperatoreUser $operatore
     * @return FormInterface
     */
    private function setupOperatoreForm(OperatoreUser $operatore)
    {
        return $this->createFormBuilder()
            ->add(
                'ambito',
                TextType::class,
                ['label' => false, 'data' => $operatore->getAmbito(), 'required' => false]
            )
            ->add(
                'save',
                SubmitType::class,
                ['label' => $this->translator->trans('operatori.profile.salva_modifiche')]
            )
            ->getForm();
    }

    /**
     * @Route("/login", name="operatori_login")
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

        return $this->render('Operatori/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    /**
     * @Route("/logout", name="operatori_logout")
     */
    public function logout()
    {
        return $this->redirectToRoute('home');
    }

    /**
     * @Route("/profile", name="operatori_user_profile_show")
     */
    public function profile()
    {
        return $this->render('Operatori/profile.html.twig', ['user' => $this->getUser()]);
    }

    /**
     * @Route("/profile", name="operatori_user_profile_edit")
     */
    public function editProfile()
    {
        return $this->render('Operatori/editProfile.html.twig', ['user' => $this->getUser()]);
    }

    /**
     * Validates and process the reset URL that the user clicked in their email.
     *
     * @Route("/change-password", name="operatori_change_password")
     * @param Request $request
     * @param CoreSecurity $security
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @param EventDispatcherInterface $eventDispatcher
     * @return Response
     */
    public function changePassword(Request $request, CoreSecurity $security, UserPasswordEncoderInterface $passwordEncoder, EventDispatcherInterface $eventDispatcher): Response
    {
        $user = $security->getUser();
        if (!is_object($user) || !$user instanceof User) {
            throw new AccessDeniedException('This user does not have access to this section.');
        }

        $event = new ChangePasswordInitializeEvent($user, $request);
        $eventDispatcher->dispatch($event);

        if (null !== $event->getResponse()) {
            return $event->getResponse();
        }

        $form = $this->createForm(ChangePasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            if (!$passwordEncoder->isPasswordValid($user, $form->get('oldPassword')->getData())){
                $this->addFlash('error', 'La vecchia password non corrsiponde');
                return $this->redirectToRoute('operatori_change_password');
            }

            if ($passwordEncoder->encodePassword($user, $form->get('plainPassword')->getData()) == $user->getPassword()){
                $this->addFlash('error', 'La nuova password deve essere diversa dalla precedente');
                return $this->redirectToRoute('operatori_change_password');
            }

            $event = new ChangePasswordSuccessEvent($user, $request);
            $eventDispatcher->dispatch($event);

            // Encode the plain password, and set it.
            $encodedPassword = $passwordEncoder->encodePassword(
                $user,
                $form->get('plainPassword')->getData()
            );
            $user->setPassword($encodedPassword);
            $this->getDoctrine()->getManager()->persist($user);
            $this->getDoctrine()->getManager()->flush();

            if (null === $response = $event->getResponse()) {
                $redirectRoute = $request->query->has('r') ? $request->query->get('r') : 'operatori_user_profile_show';
                $redirectRouteParams = $request->query->has('p') ? unserialize($request->query->get('p')) : array();
                $redirectRouteQuery = $request->query->has('p') ? unserialize($request->query->get('q')) : array();
                $url = $this->generateUrl($redirectRoute, array_merge($redirectRouteParams, $redirectRouteQuery));
                $response = new RedirectResponse($url);
            }

            $eventDispatcher->dispatch(new ChangePasswordCompletedEvent($user, $request));

            return $response;
        }

        return $this->render('Operatori/changePassword.html.twig', [
            'changeForm' => $form->createView(),
        ]);

    }
}
