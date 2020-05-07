<?php

namespace App\Controller;

use App\Entity\Allegato;
use App\Entity\CPSUser;
use App\Entity\DematerializedFormPratica;
use App\Entity\GiscomPratica;
use App\Entity\Pratica;
use App\Entity\Servizio;
use App\Entity\User;
use App\Form\Base\PraticaFlow;
use App\Handlers\Servizio\DefaultHandler;
use App\Logging\LogConstants;
use App\Multitenancy\Annotations\MustHaveTenant;
use App\Multitenancy\TenantAwareController;
use App\Repository\PraticaRepository;
use App\Services\ModuloPdfBuilderService;
use App\Services\PraticaFlowRegistry;
use App\Services\PraticaStatusService;
use App\Services\ServizioHandlerRegistry;
use Doctrine\DBAL\DBALException;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class PraticheController
 *
 * @package App\Controller
 * @Route("/pratiche")
 * @MustHaveTenant
 */
class PraticheController extends TenantAwareController
{
    const ENTE_SLUG_QUERY_PARAMETER = 'ente';

    /**
     * @Route("/", name="pratiche")
     */
    public function index(LoggerInterface $logger)
    {
        $user = $this->getUser();

        /** @var PraticaRepository $repo */
        $repo = $this->getDoctrine()->getRepository('App:Pratica');
        $pratiche = $repo->findBy(
            array('user' => $user),
            array('status' => 'DESC')
        );

        $praticheDraft = $repo->findDraftPraticaForUser($user);
        $pratichePending = $repo->findPendingPraticaForUser($user);
        //$praticheProcessing = $repo->findProcessingPraticaForUser($user);
        $praticheCompleted = $repo->findCompletePraticaForUser($user);
        $praticheCancelled = $repo->findCancelledPraticaForUser($user);
        $praticheDraftForIntegration = $repo->findDraftForIntegrationPraticaForUser($user);
        try {
            $praticheRelated = $repo->findRelatedPraticaForUser($user);
        } catch (DBALException $e) {
            $praticheRelated = [];
            $logger->error($e->getMessage());
        }

        return $this->render('Pratiche/index.html.twig', [
            'user' => $user,
            'pratiche' => $pratiche,
            'title' => 'lista_pratiche',
            'tab_pratiche' => array(
                'draft' => $praticheDraft,
                'pending' => $pratichePending,
                //'processing' => $praticheProcessing,
                'completed' => $praticheCompleted,
                'cancelled' => $praticheCancelled,
                'integration' => $praticheDraftForIntegration,
                'related' => $praticheRelated,
            ),
        ]);
    }

    /**
     * @Route("/{servizio}/new", name="pratiche_new")
     * @ParamConverter("servizio", class="App:Servizio", options={"mapping": {"servizio": "slug"}})
     * @param Request $request
     * @param Servizio $servizio
     * @param PraticaFlowRegistry $praticaFlowRegistry
     * @param LoggerInterface $logger
     * @param ServizioHandlerRegistry $servizioHandlerRegistry
     * @return RedirectResponse|Response
     */
    public function new(
        Request $request,
        Servizio $servizio,
        PraticaFlowRegistry $praticaFlowRegistry,
        LoggerInterface $logger,
        ServizioHandlerRegistry $servizioHandlerRegistry
    )
    {
        if ($servizio->getStatus() != Servizio::STATUS_AVAILABLE) {
            $this->addFlash('warning', 'Il servizio ' . $servizio->getName() . ' non è disponibile.');
            return $this->redirectToRoute('servizi_list');
        }

        /** @var User $user */
        $user = $this->getUser();
        $handler = $servizioHandlerRegistry->getByName($servizio->getHandler());
        if ($handler instanceof DefaultHandler) {
            $praticaFQCN = $servizio->getPraticaFCQN();
            $praticaInstance = new $praticaFQCN();
            $isServizioScia = $praticaInstance instanceof DematerializedFormPratica;

            $repo = $this->getDoctrine()->getRepository('App:Pratica');
            $pratiche = $repo->findBy(
                array(
                    'user' => $user,
                    'servizio' => $servizio,
                    'status' => Pratica::STATUS_DRAFT,
                ),
                array('creationTime' => 'ASC')
            );

            if (!$isServizioScia && !empty($pratiche)) {
                return $this->redirectToRoute(
                    'pratiche_list_draft',
                    ['servizio' => $servizio->getSlug()]
                );
            }

            $pratica = $this->createNewPratica($servizio, $user, $praticaFlowRegistry, $logger);

            $ente = null;
            if ($this->hasTenant()) {
                $enteSlug = $this->getTenant()->getSlug();
            } else {
                $enteSlug = $request->query->get(self::ENTE_SLUG_QUERY_PARAMETER, null);
            }

            if ($enteSlug != null) {
                $ente = $this->getDoctrine()
                    ->getRepository('App:Ente')
                    ->findOneBy(['slug' => $enteSlug]);
            }

            if ($ente != null) {
                $pratica->setEnte($ente);
                $this->infereErogatoreFromEnteAndServizio($pratica);
                $this->getDoctrine()->getManager()->flush();
            } else {
                $logger->info(
                    LogConstants::PRATICA_WRONG_ENTE_REQUESTED,
                    [
                        'pratica' => $pratica,
                        'headers' => $request->headers,
                    ]
                );
            }

            return $this->redirectToRoute(
                'pratiche_compila',
                ['pratica' => $pratica->getId()]
            );
        } else {
            if ($result = $handler->execute()) {
                return $handler->execute();
            }

            return $this->render('Pratiche/serviziFeedback.html.twig', [
                'servizio' => $servizio,
                'status' => 'danger',
                'msg' => $handler->getErrorMessage()
            ]);
        }
    }

    /**
     * @param Servizio $servizio
     * @param User $user
     * @param PraticaFlowRegistry $praticaFlowRegistry
     * @param LoggerInterface $logger
     * @return Pratica
     */
    private function createNewPratica(Servizio $servizio, User $user, PraticaFlowRegistry $praticaFlowRegistry, LoggerInterface $logger)
    {
        $praticaClassName = $servizio->getPraticaFCQN();

        /** @var PraticaFlow $praticaFlowService */
        $praticaFlowService = $praticaFlowRegistry->getByName($servizio->getPraticaFlowServiceName());

        $pratica = new $praticaClassName();
        if (!$pratica instanceof Pratica) {
            throw new \RuntimeException("Wrong Pratica FCQN for servizio {$servizio->getName()}");
        }
        $pratica
            ->setServizio($servizio)
            //->setType($servizio->getSlug())
            ->setUser($user)
            ->setStatus(Pratica::STATUS_DRAFT);

        $repo = $this->getDoctrine()->getRepository('App:Pratica');
        $lastPraticaList = $repo->findBy(
            array(
                'user' => $user,
                'servizio' => $servizio,
                'status' => [
                    Pratica::STATUS_COMPLETE,
                    Pratica::STATUS_SUBMITTED,
                    Pratica::STATUS_PENDING,
                    Pratica::STATUS_REGISTERED
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

        if ($user instanceof CPSUser) {
            $praticaFlowService->populatePraticaFieldsWithUserValues($user, $pratica);
        }

        $em = $this->getDoctrine()->getManager();
        $em->persist($pratica);
        $em->flush();

        $logger->info(
            LogConstants::PRATICA_CREATED,
            ['type' => $pratica->getType(), 'pratica' => $pratica]
        );

        return $pratica;
    }

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

    /**
     * @Route("/{servizio}/draft", name="pratiche_list_draft")
     * @ParamConverter("servizio", class="App:Servizio", options={"mapping": {"servizio": "slug"}})
     * @param Servizio $servizio
     * @return Response
     */
    public function listDraftByService(Servizio $servizio)
    {
        $user = $this->getUser();
        $repo = $this->getDoctrine()->getRepository('App:Pratica');
        $pratiche = $repo->findBy(
            array(
                'user' => $user,
                'servizio' => $servizio,
                'status' => [
                    Pratica::STATUS_DRAFT,
                    Pratica::STATUS_DRAFT_FOR_INTEGRATION,
                ],
            ),
            array('creationTime' => 'ASC')
        );

        return $this->render('Pratiche/listDraftByService.html.twig', [
            'user' => $user,
            'pratiche' => $pratiche,
            'title' => 'bozze_servizio',
            'msg' => array(
                'type' => 'warning',
                'text' => 'msg_bozze_servizio',
            ),
        ]);
    }

    /**
     * @Route("/compila/{pratica}", name="pratiche_compila")
     * @ParamConverter("pratica", class="App:Pratica")
     * @param Pratica $pratica
     * @param PraticaFlowRegistry $praticaFlowRegistry
     * @param LoggerInterface $logger
     * @param TranslatorInterface $translator
     * @return Response
     */
    public function compila(Pratica $pratica, PraticaFlowRegistry $praticaFlowRegistry, LoggerInterface $logger, TranslatorInterface $translator)
    {
        $em = $this->getDoctrine()->getManager();

        if ($pratica->getStatus() !== Pratica::STATUS_DRAFT_FOR_INTEGRATION
            && $pratica->getStatus() !== Pratica::STATUS_DRAFT
            && $pratica->getStatus() !== Pratica::STATUS_PAYMENT_PENDING) {
            return $this->redirectToRoute(
                'pratiche_show',
                ['pratica' => $pratica->getId()]
            );
        }

        $user = $this->getUser();
        $this->checkUserCanAccessPratica($pratica, $user);

        /** @var PraticaFlow $praticaFlowService */
        $praticaFlowService = $praticaFlowRegistry->getByName($pratica->getServizio()->getPraticaFlowServiceName());

        if ($pratica->getServizio()->isPaymentRequired()) {
            $praticaFlowService->setPaymentRequired(true);
        }

        $praticaFlowService->setInstanceKey($user->getId());

        $praticaFlowService->bind($pratica);

        if ($pratica->getInstanceId() == null) {
            $pratica->setInstanceId($praticaFlowService->getInstanceId());
        }

        $form = $praticaFlowService->createForm();
        if ($praticaFlowService->isValid($form)) {
            $currentStep = $praticaFlowService->getCurrentStepNumber();

            //Erogatore
            //FIXME: find a way to generalize the ente selection step
            if ($currentStep == 1) {
                $this->infereErogatoreFromEnteAndServizio($pratica);
            }

            $praticaFlowService->saveCurrentStepData($form);
            $pratica->setLastCompiledStep($currentStep);

            if ($praticaFlowService->nextStep()) {
                $em->flush();
                $form = $praticaFlowService->createForm();
            } else {
                $praticaFlowService->onFlowCompleted($pratica);

                $logger->info(
                    LogConstants::PRATICA_UPDATED,
                    ['id' => $pratica->getId(), 'pratica' => $pratica]
                );

                $this->addFlash('feedback', $translator->trans('pratica_ricevuta'));

                $praticaFlowService->getDataManager()->drop($praticaFlowService);
                $praticaFlowService->reset();

                return $this->redirectToRoute(
                    'pratiche_show',
                    ['pratica' => $pratica->getId()]
                );
            }
        }

        return $this->render('Pratiche/compila.html.twig', [
            'form' => $form->createView(),
            'pratica' => $praticaFlowService->getFormData(),
            'flow' => $praticaFlowService,
            'formserver_url' => $this->getParameter('formserver_public_url'),
            'user' => $user,
        ]);
    }

    private function checkUserCanAccessPratica(Pratica $pratica, UserInterface $user)
    {
        $isTheOwner = false;
        $isRelated = false;

        if ($user instanceof CPSUser) {
            $praticaUser = $pratica->getUser();
            $isTheOwner = $praticaUser->getId() === $user->getId();
            $cfs = $pratica->getRelatedCFs();
            if (!is_array($cfs)) {
                $cfs = [$cfs];
            }
            $isRelated = in_array($user->getCodiceFiscale(), $cfs);
        }

        if (!$isTheOwner && !$isRelated) {
            throw new UnauthorizedHttpException("User can not read pratica {$pratica->getId()}");
        }
    }

    /**
     * @Route("/{pratica}", name="pratiche_show")
     * @ParamConverter("pratica", class="App:Pratica")
     * @param Pratica $pratica
     * @return Response
     */
    public function show(Pratica $pratica)
    {
        $user = $this->getUser();
        $this->checkUserCanAccessPratica($pratica, $user);
        $result = [
            'pratica' => $pratica,
            'user' => $user,
            'formserver_url' => $this->getParameter('formserver_public_url'),
        ];

        if ($pratica instanceof GiscomPratica) {
            $allegati = [];
            $attachments = $pratica->getAllegati();
            if (count($attachments) > 0) {

                /** @var Allegato $a */
                foreach ($attachments as $a) {
                    $allegati[$a->getId()] = $a->getNumeroProtocollo();
                }
            }
            $result['allegati'] = $allegati;
        }

        return $this->render('Pratiche/show.html.twig', $result);
    }

    /**
     * @Route("/{pratica}/payment-callback", name="pratiche_payment_callback")
     * @ParamConverter("pratica", class="App:Pratica")
     * @param Request $request
     * @param Pratica $pratica
     * @param PraticaStatusService $praticaStatusService
     * @return RedirectResponse
     * @throws \Exception
     */
    public function paymentCallback(Request $request, Pratica $pratica, PraticaStatusService $praticaStatusService)
    {
        $user = $this->getUser();
        $this->checkUserCanAccessPratica($pratica, $user);
        $outcome = $request->get('esito');

        if ($outcome == 'OK') {
            $praticaStatusService->setNewStatus($pratica, Pratica::STATUS_PAYMENT_OUTCOME_PENDING);
        }

        return $this->redirectToRoute('pratiche_show', [
            'pratica' => $pratica
        ]);
    }

    /**
     * @Route("/{pratica}/pdf", name="pratiche_show_pdf")
     * @ParamConverter("pratica", class="App:Pratica")
     * @param Pratica $pratica
     * @param ModuloPdfBuilderService $pdfBuilderService
     * @return BinaryFileResponse
     * @throws \Exception
     */
    public function showPdf(Pratica $pratica, ModuloPdfBuilderService $pdfBuilderService)
    {
        $user = $this->getUser();
        $this->checkUserCanAccessPratica($pratica, $user);
        $allegato = $pdfBuilderService->showForPratica($pratica);


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
     * @Route("/{pratica}/delete", name="pratiche_delete")
     * @ParamConverter("pratica", class="App:Pratica")
     * @param Pratica $pratica
     * @return RedirectResponse
     */
    public function delete(Pratica $pratica)
    {
        $user = $this->getUser();
        $this->checkUserCanAccessPratica($pratica, $user);
        if ($pratica->getStatus() != Pratica::STATUS_DRAFT) {
            throw new UnauthorizedHttpException("Pratica can't be deleted, not in draft status");
        }

        $em = $this->getDoctrine()->getManager();
        $em->remove($pratica);
        $em->flush();


        return $this->redirectToRoute('pratiche');
    }

    /**
     * @Route("/{pratica}/protocollo", name="pratiche_show_protocolli")
     * @ParamConverter("pratica", class="App:Pratica")
     * @param Pratica $pratica
     * @return Response
     * @throws \ReflectionException
     */
    public function showProtocolli(Pratica $pratica)
    {
        $user = $this->getUser();
        $this->checkUserCanAccessPratica($pratica, $user);

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

        return $this->render('Pratiche/show.html.twig', [
            'pratica' => $pratica,
            'allegati' => $allegati,
            'user' => $user,
        ]);
    }

    /**
     * @Route("/formio/validate", name="formio_validate")
     * @return JsonResponse
     */
    public function formioValidate()
    {
        // @todo: validazione base del form
        //$user = $this->getUser();
        $response = array('status' => 'OK');

        return JsonResponse::create($response, Response::HTTP_OK);
    }
}
