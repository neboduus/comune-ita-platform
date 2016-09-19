<?php

namespace AppBundle\Controller;

use AppBundle\Entity\ComponenteNucleoFamiliare;
use AppBundle\Entity\CPSUser;
use AppBundle\Entity\IscrizioneAsiloNido;
use AppBundle\Entity\ModuloCompilato;
use AppBundle\Entity\Pratica;
use AppBundle\Entity\Servizio;
use AppBundle\Logging\LogConstants;
use Craue\FormFlowBundle\Form\FormFlowInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;

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
     * @param IscrizioneAsiloNido $pratica
     *
     * @return array
     */
    public function compilaAction(IscrizioneAsiloNido $pratica)
    {
        //@todo da testare
        //@todo scrivere la storia
        if ($pratica->getStatus() !== Pratica::STATUS_DRAFT) {
            return $this->redirectToRoute(
                'pratiche_show',
                ['pratica' => $pratica->getId()]
            );
        }

        $user = $this->getUser();
        /** @var FormFlowInterface $flow */
        $flow = $this->get('ocsdc.form.flow.asilonido');

        $flow->bind($pratica);
        $form = $flow->createForm();


        if ($flow->isValid($form)) {
            $flow->saveCurrentStepData($form);
            if ($flow->nextStep()) {
                $this->getDoctrine()->getManager()->flush();
                $form = $flow->createForm();
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

                $flow->reset();

                return $this->redirectToRoute(
                    'pratiche_show',
                    ['pratica' => $pratica->getId()]
                );
            }
        }

        return [
            'form' => $form->createView(),
            'flow' => $flow,
            'user' => $user,
        ];
    }

    /**
     * @Route("/{pratica}", name="pratiche_show")
     * @ParamConverter("pratica", class="AppBundle:Pratica")
     * @Template()
     * @param IscrizioneAsiloNido $pratica
     *
     * @return array
     */
    public function showAction(IscrizioneAsiloNido $pratica)
    {
        $user = $this->getUser();

        return [
            'pratica' => $pratica,
            'user' => $user,
        ];
    }

    private function createNewPratica(Servizio $servizio, CPSUser $user)
    {
        $pratica = new IscrizioneAsiloNido();
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
        if ($lastPratica instanceof IscrizioneAsiloNido) {
            foreach ($lastPratica->getNucleoFamiliare() as $oldCompontente) {
                $this->addNewComponenteToPraticaFromOldComponente($oldCompontente, $pratica);
            }
        }

        $user = $this->getUser();
        $this->populatePraticaFieldsWithUserValues($user, $pratica);

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
     * @param IscrizioneAsiloNido $pratica
     * @param $user
     */
    private function createModuloForPratica(IscrizioneAsiloNido $pratica, $user)
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
        $moduloCompilato->setOriginalFilename('Modulo Iscrizione Nido '.$now->format('Ymdhi'));
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
     * @param IscrizioneAsiloNido $pratica
     * @param $user
     * @return string
     */
    private function renderModuloAsPdf(IscrizioneAsiloNido $pratica, $user):string
    {
        $html = $this->renderView('AppBundle:Pratiche:iscrizioneNidoExport.html.twig', array(
            'pratica' => $pratica,
            'user' => $user,
        ));
        $content = $this->get('knp_snappy.pdf')->getOutputFromHtml($html);

        return $content;
    }

    /**
     * @param $moduloCompilato
     * @return string
     */
    private function getDestDirFromModuloContext($moduloCompilato):string
    {
        $mapping = $this->get('vich_uploader.property_mapping_factory')->fromObject($moduloCompilato)[0];
        $path = $this->get('ocsdc.allegati.directory_namer')->directoryName($moduloCompilato, $mapping);
        $destDir = $mapping->getUploadDestination().'/'.$path;

        return $destDir;
    }

    /**
     * @param CPSUser $user
     * @param $pratica
     */
    private function populatePraticaFieldsWithUserValues(CPSUser $user, IscrizioneAsiloNido $pratica)
    {
        $pratica->setRichiedenteNome($user->getNome());
        $pratica->setRichiedenteCognome($user->getCognome());
        $pratica->setRichiedenteLuogoNascita($user->getLuogoNascita());
        $pratica->setRichiedenteDataNascita($user->getDataNascita());
        $pratica->setRichiedenteIndirizzoResidenza($user->getIndirizzoResidenza());
        $pratica->setRichiedenteCapResidenza($user->getCapResidenza());
        $pratica->setRichiedenteCittaResidenza($user->getCittaResidenza());
        $pratica->setRichiedenteTelefono($user->getTelefono());
        $pratica->setRichiedenteEmail($user->getEmailCanonical());
    }

    /**
     * @param $compontente
     * @param $pratica
     */
    private function addNewComponenteToPraticaFromOldComponente(ComponenteNucleoFamiliare $compontente, Pratica $pratica)
    {
        $cloneCompontente = new ComponenteNucleoFamiliare();
        $cloneCompontente->setNome($compontente->getNome());
        $cloneCompontente->setCognome($compontente->getCognome());
        $cloneCompontente->setCodiceFiscale($compontente->getCodiceFiscale());
        $cloneCompontente->setRapportoParentela($compontente->getRapportoParentela());
        $pratica->addNucleoFamiliare($cloneCompontente);
    }
}
