<?php

namespace App\Controller;

use App\Entity\Allegato;
use App\Entity\CPSUser;
use App\Entity\DematerializedFormPratica;
use App\Entity\Pratica;
use App\Entity\Servizio;
use App\Logging\LogConstants;
use App\Multitenancy\Annotations\MustHaveTenant;
use App\Multitenancy\TenantAwareController;
use App\Services\DematerializedFormAllegatiAttacherService;
use App\Services\InstanceService;
use App\Services\ModuloPdfBuilderService;
use App\Services\PraticaFlowRegistry;
use App\Services\PraticaStatusService;
use DateTime;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class PraticheAnonimeController
 *
 * @package App\Controller
 * @Route("/pratiche-anonime")
 * @MustHaveTenant
 */
class PraticheAnonimeController extends TenantAwareController
{
    const ENTE_SLUG_QUERY_PARAMETER = 'ente';
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
    protected $hashValidity;

    public function __construct(
        LoggerInterface $logger,
        TranslatorInterface $translator,
        PraticaStatusService $statusService,
        ModuloPdfBuilderService $pdfBuilder,
        DematerializedFormAllegatiAttacherService $dematerializer,
        $hashValidity
    )
    {
        $this->logger = $logger;
        $this->translator = $translator;
        $this->statusService = $statusService;
        $this->pdfBuilder = $pdfBuilder;
        $this->dematerializer = $dematerializer;
        $this->hashValidity = $hashValidity;
    }

    /**
     * @Route("/{servizio}/new", name="pratiche_anonime_new")
     * @ParamConverter("servizio", class="App:Servizio", options={"mapping": {"servizio": "slug"}})
     * @param Servizio $servizio
     * @param InstanceService $instanceService
     * @param PraticaFlowRegistry $praticaFlowRegistry
     * @return array|RedirectResponse
     * @throws \Exception
     */
    public function new(Servizio $servizio, InstanceService $instanceService, PraticaFlowRegistry $praticaFlowRegistry)
    {
        if ($servizio->getStatus() != Servizio::STATUS_AVAILABLE) {
            $this->addFlash('warning', 'Il servizio ' . $servizio->getName() . ' non è disponibile.');
            return $this->redirectToRoute('servizi_list');
        }

        if ($servizio->getAccessLevel() > 0 || $servizio->getAccessLevel() === null) {
            $this->addFlash('warning', 'Il servizio ' . $servizio->getName() . ' è disponibile solo per gli utenti loggati.');
            return $this->redirectToRoute('servizi_list');
        }

        /** @var DematerializedFormPratica|Pratica $pratica */
        $pratica = $this->createNewPratica($servizio, $instanceService);

        // La sessione deve essere creata prima del flow, altrimenti lo crea con id vuoto
        if (!$this->get('session')->isStarted()) {
            $this->get('session')->start();
        }

        $flow = $praticaFlowRegistry->getByName('ocsdc.form.flow.formioanonymous');
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

                $attachments = $pratica->getAllegati();
                if (!empty($attachments)) {
                    /** @var Allegato $a */
                    foreach ($attachments as $a) {
                        $a->setOwner($user);
                        $em->persist($pratica);
                    }
                }
                $em->flush();
                $flow->onFlowCompleted($pratica);

                $this->logger->info(
                    LogConstants::PRATICA_UPDATED,
                    ['id' => $pratica->getId(), 'pratica' => $pratica]
                );

                $this->addFlash('feedback', $this->translator->trans('pratica_ricevuta'));
                $flow->getDataManager()->drop($flow);
                $flow->reset();

                return $this->redirectToRoute(
                    'pratiche_anonime_show',
                    [
                        'pratica' => $pratica->getId(),
                        'hash' => $pratica->getHash()
                    ]
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
     * @param Servizio $servizio
     * @param InstanceService $instanceService
     * @return Pratica
     * @throws \Exception
     */
    private function createNewPratica(Servizio $servizio, InstanceService $instanceService)
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
            ->setStatus(Pratica::STATUS_DRAFT)
            ->setHash(hash('sha256', $pratica->getId()) . '-' . (new DateTime())->getTimestamp());

        $pratica->setEnte($instanceService->getCurrentInstance());
        $this->infereErogatoreFromEnteAndServizio($pratica);

        $this->logger->info(
            LogConstants::PRATICA_CREATED,
            ['type' => $pratica->getType(), 'pratica' => $pratica]
        );

        return $pratica;
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

    /**
     * @param array $data
     * @return CPSUser
     * @throws \Exception
     */
    private function checkUser(array $data): CPSUser
    {
        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();
        $cf = isset($data['flattened']['applicant.data.fiscal_code.data.fiscal_code']) ? $data['flattened']['applicant.data.fiscal_code.data.fiscal_code'] : false;

        // Check md5 sessione
        $result = $em->createQueryBuilder()
            ->select('user.id')
            ->from('App:User', 'user')
            ->where('upper(user.username) = upper(:username)')
            ->setParameter('username', md5($this->get('session')->getId()))
            ->getQuery()->getResult();

        if (!empty($result)) {
            $repository = $this->getDoctrine()->getRepository('App:CPSUser');
            /** @var CPSUser $user */
            $user = $repository->find($result[0]['id']);

            return $user;
        }

        $user = new CPSUser();
        $user
            ->setDataNascita(isset($data['flattened']['applicant.data.Born.data.natoAIl']) && !empty($data['flattened']['applicant.data.Born.data.natoAIl']) ? new \DateTime($data['flattened']['applicant.data.Born.data.natoAIl']) : null)
            ->setLuogoNascita(isset($data['flattened']['applicant.data.Born.data.place_of_birth']) && !empty($data['flattened']['applicant.data.Born.data.place_of_birth']) ? $data['flattened']['applicant.data.Born.data.place_of_birth'] : '')
            ->setSdcIndirizzoResidenza(isset($data['flattened']['applicant.data.address.data.address']) && !empty($data['flattened']['applicant.data.address.data.address']) ? $data['flattened']['applicant.data.address.data.address'] : '')
            ->setSdcCittaResidenza(isset($data['flattened']['applicant.data.address.data.municipality']) && !empty($data['flattened']['applicant.data.address.data.municipality']) ? $data['flattened']['applicant.data.address.data.municipality'] : '')
            ->setSdcCapResidenza(isset($data['flattened']['applicant.data.address.data.postal_code']) && !empty($data['flattened']['applicant.data.address.data.postal_code']) ? $data['flattened']['applicant.data.address.data.postal_code'] : '')
            ->setUsername(md5($this->get('session')->getId()))
            ->setCodiceFiscale($cf . '-' . md5($this->get('session')->getId()) . '-' . time())
            ->setEmail(isset($data['flattened']['applicant.data.email_address']) ? $data['flattened']['applicant.data.email_address'] : $user->getId() . '@' . CPSUser::FAKE_EMAIL_DOMAIN)
            ->setEmailContatto(isset($data['flattened']['applicant.data.email_address']) ? $data['flattened']['applicant.data.email_address'] : $user->getId() . '@' . CPSUser::FAKE_EMAIL_DOMAIN)
            ->setNome(isset($data['flattened']['applicant.data.completename.data.name']) ? $data['flattened']['applicant.data.completename.data.name'] : '')
            ->setCognome(isset($data['flattened']['applicant.data.completename.data.surname']) ? $data['flattened']['applicant.data.completename.data.surname'] : '');

        $user->addRole('ROLE_USER')
            ->addRole('ROLE_CPS_USER')
            ->setEnabled(true)
            ->setPassword('');

        $em->persist($user);
        return $user;
    }

    /**
     * @Route("/{pratica}", name="pratiche_anonime_show")
     * @ParamConverter("pratica", class="App:Pratica")
     * @param Request $request
     * @param Pratica $pratica
     * @return Response
     * @throws \Exception
     */
    public function show(Request $request, Pratica $pratica)
    {
        $hash = $request->query->get('hash');

        if ($hash && $hash == $pratica->getHash()) {
            $timestamp = explode('-', $hash);
            $timestamp = end($timestamp);
            $maxVisibilityDate = (new DateTime())->setTimestamp($timestamp)->modify('+ ' . $this->hashValidity . ' days');

            if ($maxVisibilityDate >= new DateTime('now')) {
                return $this->render('PraticheAnonime/show.html.twig', [
                    'pratica' => $pratica
                ]);
            }
        }

        return new Response(null, Response::HTTP_FORBIDDEN);
    }

    /**
     * @Route("/{pratica}/pdf", name="pratiche_anonime_show_pdf")
     * @ParamConverter("pratica", class="App:Pratica")
     * @param Request $request
     * @param Pratica $pratica
     * @return BinaryFileResponse|Response
     * @throws \Exception
     */
    public function showPdf(Request $request, Pratica $pratica)
    {
        $hash = $request->query->get('hash');

        if ($hash && $hash == $pratica->getHash()) {
            $timestamp = explode('-', $hash);
            $timestamp = end($timestamp);
            $maxVisibilityDate = (new DateTime())->setTimestamp($timestamp)->modify('+ ' . $this->hashValidity . ' days');

            if ($maxVisibilityDate >= new DateTime('now')) {
                $allegato = $this->pdfBuilder->showForPratica($pratica);

                return new BinaryFileResponse(
                    $allegato->getFile()->getPath() . '/' . $allegato->getFile()->getFilename(),
                    200,
                    [
                        'Content-type' => 'application/octet-stream',
                        'Content-Disposition' => sprintf('attachment; filename="%s"', $allegato->getOriginalFilename() . '.' . $allegato->getFile()->getExtension()),
                    ]
                );
            }
        }

        return new Response(null, Response::HTTP_FORBIDDEN);
    }

    /**
     * @Route("/formio/validate", name="formio_validate")
     */
    public function formioValidate()
    {
        // Todo: validazione base del form
        //$user = $this->getUser();
        $response = array('status' => 'OK');
        return JsonResponse::create($response, Response::HTTP_OK);
    }
}
