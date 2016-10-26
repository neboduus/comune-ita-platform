<?php

namespace AppBundle\Controller;

use AppBundle\Entity\ComponenteNucleoFamiliare;
use AppBundle\Entity\CPSUser;
use AppBundle\Entity\ModuloCompilato;
use AppBundle\Entity\Pratica;
use AppBundle\Entity\Servizio;
use AppBundle\Form\Base\PraticaFlow;
use AppBundle\Logging\LogConstants;
use Craue\FormFlowBundle\Form\FormFlowInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Vich\UploaderBundle\Mapping\PropertyMapping;

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

        $praticheDraft = $repo->findBy(
            [
                'user' => $user,
                'status' => Pratica::STATUS_DRAFT,
            ],
            [
                'creationTime' => 'ASC',
            ]
        );

        $pratichePending = $repo->findBy(
            [
                'user' => $user,
                'status' => [
                    Pratica::STATUS_PENDING,
                    Pratica::STATUS_SUBMITTED,
                    Pratica::STATUS_REGISTERED,
                ],
            ],
            [
                'creationTime' => 'ASC',
            ]
        );

        $praticheCompleted = $repo->findBy(
            [
                'user' => $user,
                'status' => Pratica::STATUS_COMPLETE,
            ],
            [
                'creationTime' => 'ASC',
            ]
        );

        $praticheCancelled = $repo->findBy(
            [
                'user' => $user,
                'status' => Pratica::STATUS_CANCELLED,
            ],
            [
                'creationTime' => 'ASC',
            ]
        );


        return [
            'user' => $user,
            'pratiche' => $pratiche,
            'title' => 'lista_pratiche',
            'tab_pratiche' => array(
                'draft'      => $praticheDraft,
                'pending'    => $pratichePending,
                'completed'  => $praticheCompleted,
                'cancelled'  => $praticheCancelled,
            ),
        ];
    }

    /**
     * @Route("/{servizio}/new", name="pratiche_new")
     * @ParamConverter("servizio", class="AppBundle:Servizio", options={"mapping": {"servizio": "slug"}})
     *
     * @param Request  $request
     * @param Servizio $servizio
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function newAction(Request $request, Servizio $servizio)
    {
        $user = $this->getUser();
        $repo = $this->getDoctrine()->getRepository('AppBundle:Pratica');
        $pratiche = $repo->findBy(
            array(
                'user' => $user,
                'servizio' => $servizio,
                'status' => Pratica::STATUS_DRAFT,
            ),
            array('creationTime' => 'ASC')
        );

        if (!empty($pratiche)) {
            return $this->redirectToRoute(
                'pratiche_list_draft',
                [ 'servizio' => $servizio->getSlug() ]
            );
        }

        $pratica = $this->createNewPratica($servizio, $user);

        $enteSlug = $request->query->get(self::ENTE_SLUG_QUERY_PARAMETER, null);
        if ($enteSlug != null) {
            $ente = $this->getDoctrine()
                ->getRepository('AppBundle:Ente')
                ->findOneBySlug($enteSlug);
            if ($ente != null) {
                $pratica->setEnte($ente);
            } else {
                $this->get('logger')->info(
                    LogConstants::PRATICA_WRONG_ENTE_REQUESTED,
                    [
                        'pratica' => $pratica,
                        'headers' => $request->headers,
                    ]
                );
            }
        }

        return $this->redirectToRoute(
            'pratiche_compila',
            ['pratica' => $pratica->getId()]
        );
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
                'status' => Pratica::STATUS_DRAFT,
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
    public function compilaAction(Pratica $pratica)
    {
        //@todo da testare
        //@todo scrivere la storia
        if ($pratica->getStatus() !== Pratica::STATUS_DRAFT) {
            return $this->redirectToRoute(
                'pratiche_show',
                ['pratica' => $pratica->getId()]
            );
        }

        $this->checkUserCanAccessPratica($pratica);

        $user = $this->getUser();

        /** @var PraticaFlow $praticaFlowService */
        $praticaFlowService = $this->get($pratica->getServizio()->getPraticaFlowServiceName());

        $praticaFlowService->bind($pratica);
        $form = $praticaFlowService->createForm();


        if ($praticaFlowService->isValid($form)) {
            $praticaFlowService->saveCurrentStepData($form);
            if ($praticaFlowService->nextStep()) {
                $this->getDoctrine()->getManager()->flush();
                $form = $praticaFlowService->createForm();
            } else {
                $pratica->setStatus(Pratica::STATUS_SUBMITTED);
                $pratica->setSubmissionTime(time());
                $this->createModuloForPratica($pratica, $user);
                $this->get('ocsdc.mailer')->dispatchMailForPratica($pratica, $this->getParameter('default_from_email_address'));

                $this->getDoctrine()->getManager()->flush();

                $this->get('logger')->info(
                    LogConstants::PRATICA_UPDATED,
                    ['id' => $pratica->getId(), 'pratica' => $pratica]
                );

                $this->addFlash(
                    'feedback',
                    $this->get('translator')->trans('pratica_ricevuta')
                );

                $praticaFlowService->reset();

                return $this->redirectToRoute(
                    'pratiche_show',
                    ['pratica' => $pratica->getId()]
                );
            }
        }

        return [
            'form' => $form->createView(),
            'pratica' => $pratica,
            'flow' => $praticaFlowService,
            'user' => $user,
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
    public function showAction(Pratica $pratica)
    {
        $this->checkUserCanAccessPratica($pratica);

        $user = $this->getUser();

        return [
            'pratica' => $pratica,
            'user' => $user,
        ];
    }

    private function createNewPratica(Servizio $servizio, CPSUser $user)
    {
        $praticaClassName = $servizio->getPraticaFCQN();
        /** @var PraticaFlow $praticaFlowService */
        $praticaFlowService = $this->get($servizio->getPraticaFlowServiceName());

        $pratica = new $praticaClassName();
        if (!$pratica instanceof Pratica){
            throw new \RuntimeException("Wrong Pratica FCQN for servizio {$servizio->getName()}");
        }
        $pratica
            ->setServizio($servizio)
            ->setType($servizio->getSlug())
            ->setUser($user)
            ->setStatus(Pratica::STATUS_DRAFT);

        $repo = $this->getDoctrine()->getRepository('AppBundle:Pratica');
        $lastPraticaList = $repo->findBy(
            array(
                'user' => $user,
                'servizio' => $servizio,
                'status' => [Pratica::STATUS_COMPLETE, Pratica::STATUS_SUBMITTED, Pratica::STATUS_PENDING, Pratica::STATUS_REGISTERED],
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

    /**
     * @param Pratica $pratica
     * @param $user
     */
    private function createModuloForPratica(Pratica $pratica, $user)
    {
        /**
         * crea il modulo esportato e allegalo alla pratica
         */
        $content = $this->renderModuloAsPdf($pratica, $user);
        $moduloCompilato = new ModuloCompilato();
        $moduloCompilato->setOwner($user);
        $destDir = $this->getDestDirFromModuloContext($moduloCompilato);
        $fileName = uniqid().'.pdf';
        $filePath = $destDir.DIRECTORY_SEPARATOR.$fileName;

        $fs = $this->get('filesystem');
        $fs->dumpFile($filePath, $content);
        $moduloCompilato->setFile(new File($filePath));

        $now = new \DateTime();
        $now->setTimestamp($pratica->getSubmissionTime());

        $moduloCompilato->setFilename($fileName);
        $servizioName = $pratica->getServizio()->getName();
        $moduloCompilato->setOriginalFilename("Modulo {$servizioName} ".$now->format('Ymdhi'));
        $moduloCompilato->setDescription(
            $this->get('translator')->trans(
                'pratica.modulo.descrizione',
                [ 'nomeservizio' => $pratica->getServizio()->getName(), 'datacompilazione' => $now->format('d/m/Y h:i') ]
            )
        );
        $this->getDoctrine()->getManager()->persist($moduloCompilato);

        $pratica->addModuloCompilato($moduloCompilato);
    }

    /**
     * @param Pratica $pratica
     * @param $user
     * @return string
     */
    private function renderModuloAsPdf(Pratica $pratica, $user):string
    {
        $className = (new \ReflectionClass($pratica))->getShortName();;
        $html = $this->renderView('AppBundle:Pratiche:pdf/'.$className.'.html.twig', array(
            'pratica' => $pratica,
            'user' => $user,
        ));
        $content = $this->get('knp_snappy.pdf')->getOutputFromHtml($html);

        return $content;
    }

    /**
     * @param $moduloCompilato
     *
     * @return string
     */
    private function getDestDirFromModuloContext($moduloCompilato):string
    {
        /** @var PropertyMapping $mapping */
        $mapping = $this->get('vich_uploader.property_mapping_factory')->fromObject($moduloCompilato)[0];
        $path = $this->get('ocsdc.allegati.directory_namer')->directoryName($moduloCompilato, $mapping);
        $destDir = $mapping->getUploadDestination().'/'.$path;

        return $destDir;
    }

    private function checkUserCanAccessPratica(Pratica $pratica)
    {
        $praticaUser = $pratica->getUser();
        if ( $praticaUser->getId() !== $this->getUser()->getId()) {
            throw new UnauthorizedHttpException("User can not read pratica {$pratica->getId()}");
        }
    }

}
