<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Allegato;
use AppBundle\Entity\CPSUser;
use AppBundle\Entity\DematerializedFormAllegatiContainer;
use AppBundle\Entity\DematerializedFormPratica;
use AppBundle\Entity\Pratica;
use AppBundle\Entity\Servizio;
use AppBundle\Entity\User;
use AppBundle\Form\Base\MessageType;
use AppBundle\Form\Base\PraticaFlow;
use AppBundle\Logging\LogConstants;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * Class PraticheController
 *
 * @package AppBundle\Controller
 * @Route("/pratiche")
 */
class PraticheController extends Controller
{

    const ENTE_SLUG_QUERY_PARAMETER = 'ente';

    /**
     * @Route("/", name="pratiche")
     * @Template()
     *
     * @return array
     */
    public function indexAction()
    {
        $user = $this->getUser();
        $repo = $this->getDoctrine()->getRepository('AppBundle:Pratica');
        $pratiche = $repo->findBy(
            array('user' => $user),
            array('status' => 'DESC')
        );

        $praticheDraft = $repo->findDraftPraticaForUser($user);
        $pratichePending = $repo->findPendingPraticaForUser($user);
        $praticheProcessing = $repo->findProcessingPraticaForUser($user);
        $praticheCompleted = $repo->findCompletePraticaForUser($user);
        $praticheCancelled = $repo->findCancelledPraticaForUser($user);
        $praticheDraftForIntegration = $repo->findDraftForIntegrationPraticaForUser($user);
        $praticheRelated = $repo->findRelatedPraticaForUser($user);


        return [
            'user' => $user,
            'pratiche' => $pratiche,
            'title' => 'lista_pratiche',
            'tab_pratiche' => array(
                'draft' => $praticheDraft,
                'pending' => $pratichePending,
                'processing' => $praticheProcessing,
                'completed' => $praticheCompleted,
                'cancelled' => $praticheCancelled,
                'integration' => $praticheDraftForIntegration,
                'related' => $praticheRelated,
            ),
        ];
    }

    /**
     * @Route("/{servizio}/new", name="pratiche_new")
     * @ParamConverter("servizio", class="AppBundle:Servizio", options={"mapping": {"servizio": "slug"}})
     *
     * @param Request $request
     * @param Servizio $servizio
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function newAction(Request $request, Servizio $servizio)
    {
        $user = $this->getUser();
        if ($servizio->getHandler() == null || empty($servizio->getHandler()) || $servizio->getHandler() == 'default') {

            $praticaFQCN = $servizio->getPraticaFCQN();
            $praticaInstance = new  $praticaFQCN();
            $isServizioScia = $praticaInstance instanceof DematerializedFormPratica;

            $repo = $this->getDoctrine()->getRepository('AppBundle:Pratica');
            $pratiche = $repo->findBy(
                array(
                    'user' => $user,
                    'servizio' => $servizio,
                    'status' => Pratica::STATUS_DRAFT,
                ),
                array('creationTime' => 'ASC')
            );

            if (!$isServizioScia && !empty( $pratiche )) {
                return $this->redirectToRoute(
                    'pratiche_list_draft',
                    ['servizio' => $servizio->getSlug()]
                );
            }

            $pratica = $this->createNewPratica($servizio, $user);

            $enteSlug = $ente = null;
            if ($this->getParameter('prefix') != null)
            {
                $enteSlug = $this->getParameter('prefix');
            }
            else {
                $enteSlug = $request->query->get(self::ENTE_SLUG_QUERY_PARAMETER, null);
            }

            if ($enteSlug != null)
            {
                $ente = $this->getDoctrine()
                    ->getRepository('AppBundle:Ente')
                    ->findOneBySlug($enteSlug);
            }

            if ($ente != null) {
                $pratica->setEnte($ente);
                $this->infereErogatoreFromEnteAndServizio($pratica);
                $this->getDoctrine()->getManager()->flush();
            } else {
                $this->get('logger')->info(
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
            /** @var ServizioHandlerInterface $handler */
            $handler = $this->get($servizio->getHandler());

            if ($result = $handler->execute()) {
                return $handler->execute();
            }
            /*throw new \Exception("Si è verificato un problema durante l'esecuzione del servizio {$servizio->getName()}");*/
            //todo: recuperare mes da handler, per adesso faccio fix specifico per imis
            return $this->render('@App/Servizi/serviziFeedback.html.twig', array(
                'servizio' => $servizio,
                'status'   => 'danger',
                'msg'      => "Non é possibile effettuare il download del file. Non risultano immobili a suo nome all'interno del comune."
            ));
        }
    }

    /**
     * @Route("/{servizio}/draft", name="pratiche_list_draft")
     * @ParamConverter("servizio", class="AppBundle:Servizio", options={"mapping": {"servizio": "slug"}})
     * @Template()
     * @param Servizio $servizio
     *
     * @return array
     */
    public function listDraftByServiceAction(Servizio $servizio)
    {
        $user = $this->getUser();
        $repo = $this->getDoctrine()->getRepository('AppBundle:Pratica');
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

        return [
            'user' => $user,
            'pratiche' => $pratiche,
            'title' => 'bozze_servizio',
            'msg' => array(
                'type' => 'warning',
                'text' => 'msg_bozze_servizio',
            ),
        ];
    }

    /**
     * @Route("/compila/{pratica}", name="pratiche_compila")
     * @ParamConverter("pratica", class="AppBundle:Pratica")
     * @Template()
     * @param Pratica $pratica
     *
     * @return array
     */
    public function compilaAction(Request $request, Pratica $pratica)
    {
        echo ' ';
        $em = $this->getDoctrine()->getManager();

        if ($pratica->getStatus() !== Pratica::STATUS_DRAFT_FOR_INTEGRATION && $pratica->getStatus() !== Pratica::STATUS_DRAFT) {
            return $this->redirectToRoute(
                'pratiche_show',
                ['pratica' => $pratica->getId()]
            );
        }

        $user = $this->getUser();
        $this->checkUserCanAccessPratica($pratica, $user);

        /** @var PraticaFlow $praticaFlowService */
        $praticaFlowService = $this->get($pratica->getServizio()->getPraticaFlowServiceName());

        if ($pratica->getServizio()->isPaymentRequired())
        {
            $praticaFlowService->setPaymentRequired(true);
        }

        $praticaFlowService->setInstanceKey($user->getId());

        $praticaFlowService->bind($pratica);

        if ($pratica->getInstanceId() == null) {
            $pratica->setInstanceId($praticaFlowService->getInstanceId());
        }
        $resumeURI = $praticaFlowService->getResumeUrl($request);
        $thread = $this->createThreadElementsForUserAndPratica($pratica, $user, $resumeURI);

        $form = $praticaFlowService->createForm();
        if ($praticaFlowService->isValid($form)) {

            $currentStep = $praticaFlowService->getCurrentStepNumber();
            //Erogatore
            //FIXME: find a way to generalize the ente selection step
            if($currentStep == 1 ) {
                $this->infereErogatoreFromEnteAndServizio($pratica);
            }

            $praticaFlowService->saveCurrentStepData($form);
            $pratica->setLastCompiledStep($currentStep);

            if ($praticaFlowService->nextStep()) {

                $em->flush();
                $form = $praticaFlowService->createForm();

                $resumeURI = $praticaFlowService->getResumeUrl($request);
                $thread = $this->createThreadElementsForUserAndPratica($pratica, $user, $resumeURI);

            } else {

                $praticaFlowService->onFlowCompleted($pratica);

                $this->get('logger')->info(
                    LogConstants::PRATICA_UPDATED,
                    ['id' => $pratica->getId(), 'pratica' => $pratica]
                );

                $this->addFlash('feedback', $this->get('translator')->trans('pratica_ricevuta'));

                $praticaFlowService->getDataManager()->drop($praticaFlowService);
                $praticaFlowService->reset();

                return $this->redirectToRoute(
                    'pratiche_show',
                    ['pratica' => $pratica->getId()]
                );
            }
        }

        return [
            'form' => $form->createView(),
            'pratica' => $praticaFlowService->getFormData(),
            'flow' => $praticaFlowService,
            'user' => $user,
            'threads' => $thread,
        ];
    }

    /**
     * @Route("/{pratica}", name="pratiche_show")
     * @ParamConverter("pratica", class="AppBundle:Pratica")
     * @Template()
     * @param Pratica $pratica
     *
     * @return array
     */
    public function showAction(Request $request, Pratica $pratica)
    {
        $user = $this->getUser();
        $this->checkUserCanAccessPratica($pratica, $user);
        $resumeURI = $request->getUri();
        $thread = $this->createThreadElementsForUserAndPratica($pratica, $user, $resumeURI);

        return [
            'pratica' => $pratica,
            'user' => $user,
            'threads' => $thread,
        ];
    }

    /**
     * @Route("/{pratica}/protocollo", name="pratiche_show_protocolli")
     * @ParamConverter("pratica", class="AppBundle:Pratica")
     * @Template()
     * @param Pratica $pratica
     *
     * @return array
     */
    public function showProtocolliAction(Request $request, Pratica $pratica)
    {
        $user = $this->getUser();
        $this->checkUserCanAccessPratica($pratica, $user);
        $resumeURI = $request->getUri();
        $thread = $this->createThreadElementsForUserAndPratica($pratica, $user, $resumeURI);

        $allegati = [];
        foreach($pratica->getNumeriProtocollo() as $protocollo){
            $allegato = $this->getDoctrine()->getRepository('AppBundle:Allegato')->find($protocollo->id);
            if ($allegato instanceof Allegato){
                $allegati[] = [
                    'allegato' => $allegato,
                    'tipo' => (new \ReflectionClass(get_class($allegato)))->getShortName(),
                    'protocollo' => $protocollo->protocollo
                ];
            }
        }

        return [
            'pratica' => $pratica,
            'allegati' => $allegati,
            'user' => $user,
            'threads' => $thread,
        ];
    }
    
    /**
     * @param Servizio $servizio
     * @param CPSUser $user
     *
     * @return Pratica
     */
    private function createNewPratica(Servizio $servizio, CPSUser $user)
    {
        $praticaClassName = $servizio->getPraticaFCQN();
        /** @var PraticaFlow $praticaFlowService */
        $praticaFlowService = $this->get($servizio->getPraticaFlowServiceName());

        $pratica = new $praticaClassName();
        if (!$pratica instanceof Pratica) {
            throw new \RuntimeException("Wrong Pratica FCQN for servizio {$servizio->getName()}");
        }
        $pratica
            ->setServizio($servizio)
            //->setType($servizio->getSlug())
            ->setUser($user)
            ->setStatus(Pratica::STATUS_DRAFT);

        $repo = $this->getDoctrine()->getRepository('AppBundle:Pratica');
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

        $user = $this->getUser();
        $praticaFlowService->populatePraticaFieldsWithUserValues($user, $pratica);

        $em = $this->getDoctrine()->getManager();
        $em->persist($pratica);
        $em->flush();

        $this->get('logger')->info(
            LogConstants::PRATICA_CREATED,
            ['type' => $pratica->getType(), 'pratica' => $pratica]
        );

        return $pratica;
    }

    private function checkUserCanAccessPratica(Pratica $pratica, CPSUser $user)
    {
        $praticaUser = $pratica->getUser();
        $isTheOwner = $praticaUser->getId() === $user->getId();
        $cfs = $pratica->getRelatedCFs();
        if (!is_array($cfs))
        {
            $cfs = [$cfs];
        }
        $isRelated = in_array($user->getCodiceFiscale(), $cfs);


        if ( !$isTheOwner && !$isRelated ) {
            throw new UnauthorizedHttpException("User can not read pratica {$pratica->getId()}");
        }
    }

    /**
     * @param Pratica $pratica
     * @param $user
     * @return array
     */
    private function createThreadElementsForUserAndPratica(Pratica $pratica, User $user, $returnURL)
    {

        if ($pratica->getEnte()) {
            $messagesAdapterService = $this->get('ocsdc.messages_adapter');
            //FIXME: this should be the Capofila Ente (the first in the array of the Erogatore's ones)
            $ente = $pratica->getEnte();
            $servizio = $pratica->getServizio();
            $userThread = $messagesAdapterService->getThreadsForUserEnteAndService($user, $ente, $servizio);
            if (!$userThread) {
                return null;
            }

            $threadId = $userThread[0]->threadId;
            $threadForm = $this->createForm(
                MessageType::class,
                [
                    'thread_id' => $threadId,
                    'sender_id' => $user->getId(),
                    'return_url' => $returnURL,
                ],
                [
                    'action' => $this->get('router')->generate('messages_controller_enqueue_for_user', ['threadId' => $threadId]),
                    'method' => 'PUT',
                ]
            );

            $thread = [
                'threadId' => $threadId,
                'title'    => $userThread[0]->title,
                'messages' => $messagesAdapterService->getDecoratedMessagesForThread($threadId, $user),
                'form'     => $threadForm->createView(),
            ];

            return [$thread];
        }

        return null;
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
}
