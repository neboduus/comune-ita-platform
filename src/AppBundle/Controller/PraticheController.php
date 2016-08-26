<?php

namespace AppBundle\Controller;

use AppBundle\Entity\ComponenteNucleoFamiliare;
use AppBundle\Entity\CPSUser;
use AppBundle\Entity\IscrizioneAsiloNido;
use AppBundle\Entity\Pratica;
use AppBundle\Entity\Servizio;
use AppBundle\Entity\User;
use AppBundle\Form\IscrizioneAsiloNido\IscrizioneAsiloNidoFlow;
use AppBundle\Logging\LogConstants;
use Craue\FormFlowBundle\Form\FormFlowInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class PraticheController
 *
 * @package AppBundle\Controller
 * @Route("/pratiche")
 */
class PraticheController extends Controller
{
    /**
     * @Route("/", name="pratiche")
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        $user = $this->getUser();
        $repo = $this->getDoctrine()->getRepository('AppBundle:Pratica');
        $pratiche = $repo->findBy(
            array('user' => $user),
            array('status' => 'DESC')
        );

        return $this->render('AppBundle:Default:pratiche.html.twig', array('user' => $user, 'pratiche' => $pratiche, 'title' => 'lista_pratiche'));
    }

    /**
     * @Route("/{servizio}/new", name="pratiche_new")
     * @ParamConverter("servizio", class="AppBundle:Servizio", options={"mapping": {"servizio": "slug"}})
     *
     * @param Servizio $servizio
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function newAction(Servizio $servizio)
    {
        $user = $this->getUser();
        $repo = $this->getDoctrine()->getRepository('AppBundle:Pratica');
        $pratiche = $repo->findBy(
            array(
                'user' => $user,
                'servizio' => $servizio,
                'status' => Pratica::STATUS_DRAFT),
            array('creationTime' => 'ASC')
        );

        if (!empty($pratiche))
        {
            return $this->redirectToRoute(
                'pratiche_list_draft',
                ['servizio'=>$servizio->getSlug()]
            );
        }

        $pratica = $this->createNewPratica($servizio, $user);
        return $this->redirectToRoute(
            'pratiche_compila',
            ['pratica' => $pratica->getId()]
        );

    }

    /**
     * @Route("/{servizio}/draft", name="pratiche_list_draft")
     * @ParamConverter("servizio", class="AppBundle:Servizio", options={"mapping": {"servizio": "slug"}})
     *
     * @param Servizio $servizio
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function listDraftByServiceAction(Servizio $servizio)
    {
        $user = $this->getUser();
        $repo = $this->getDoctrine()->getRepository('AppBundle:Pratica');
        $pratiche = $repo->findBy(
            array(
                'user' => $user,
                'servizio' => $servizio,
                'status' => Pratica::STATUS_DRAFT),
            array('creationTime' => 'ASC')
        );

        return $this->render(
            'AppBundle:Default:pratiche.html.twig',
            array(
                'user' => $user,
                'pratiche' => $pratiche,
                'title' => 'bozze_servizio',
                'msg' => array(
                    'type' => 'warning',
                    'text' => 'msg_bozze_servizio'
                )
            )
        );
    }

    /**
     * @Route("/compila/{pratica}", name="pratiche_compila")
     * @ParamConverter("pratica", class="AppBundle:Pratica")
     *
     * @param IscrizioneAsiloNido $pratica
     *
     * @return \Symfony\Component\HttpFoundation\Response
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

        //@todo
        /** @var FormFlowInterface $flow */
        $flow = $this->get('ocsdc.form.flow.asilonido');

        $flow->bind($pratica);
        $form = $flow->createForm();


        if ($flow->isValid($form)) {
            $flow->saveCurrentStepData($form);
            if ($flow->getCurrentStepNumber() == IscrizioneAsiloNidoFlow::STEP_ALLEGATI) {
                $errors = $this->get('validator')->validate($pratica);
                if ($errors->count() > 0) {
                    foreach ($errors as $error) {
                        $formattedErrorMessage = sprintf(
                            $this->get('translator')->trans('errori.allegato.tipo_non_valido'),
                            $error->getInvalidValue()->getOriginalFilename()
                        );
                        $form->addError(new FormError($formattedErrorMessage));
                    }
                } else {
                    $flow->nextStep();
                    $this->getDoctrine()->getManager()->flush();
                    $form = $flow->createForm();
                }
            } elseif ($flow->nextStep()) {
                $this->getDoctrine()->getManager()->flush();
                $form = $flow->createForm();
            } else {
                $pratica->setStatus(Pratica::STATUS_SUBMITTED);

                $this->getDoctrine()->getManager()->flush();

                $this->get('logger')->info(
                    LogConstants::PRATICA_UPDATED, ['id' => $pratica->getId(), 'pratica' => $pratica]
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

        return $this->render('@App/Default/pratiche/compila_iscrizione_asilo_nido.html.twig', array(
            'form' => $form->createView(),
            'flow' => $flow,
        ));
    }

    /**
     * @Route("/{pratica}", name="pratiche_show")
     * @ParamConverter("pratica", class="AppBundle:Pratica")
     *
     * @param Pratica $pratica
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showAction(Pratica $pratica)
    {
        return $this->render('@App/Default/pratica.html.twig', ['pratica' => $pratica]);
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
                'status' => [Pratica::STATUS_COMPLETE,Pratica::STATUS_SUBMITTED,Pratica::STATUS_PENDING,Pratica::STATUS_REGISTERED]
            ),
            array('creationTime' => 'DESC'),
            1
        );
        $lastPratica = null;
        if ($lastPraticaList){
            $lastPratica = $lastPraticaList[0];
        }
        if ($lastPratica instanceof IscrizioneAsiloNido) {
            foreach($lastPratica->getNucleoFamiliare() as $compontente){
                $cloneCompontente = new ComponenteNucleoFamiliare();
                $cloneCompontente->setNome($compontente->getNome());
                $cloneCompontente->setCognome($compontente->getCognome());
                $cloneCompontente->setCodiceFiscale($compontente->getCodiceFiscale());
                $cloneCompontente->setRapportoParentela($compontente->getRapportoParentela());
                $pratica->addNucleoFamiliare($cloneCompontente);
            }
        }

        $user = $this->getUser();
        $pratica->setRichiedenteNome($user->getNome());
        $pratica->setRichiedenteCognome($user->getCognome());
        $pratica->setRichiedenteLuogoNascita($user->getLuogoNascita());
        $pratica->setRichiedenteDataNascita($user->getDataNascita());
        $pratica->setRichiedenteIndirizzoResidenza($user->getIndirizzoResidenza());
        $pratica->setRichiedenteCapResidenza($user->getCapResidenza());
        $pratica->setRichiedenteCittaResidenza($user->getCittaResidenza());
        $pratica->setRichiedenteTelefono($user->getTelefono());
        $pratica->setRichiedenteEmail($user->getEmailCanonical());

        $em = $this->getDoctrine()->getManager();
        $em->persist($pratica);
        $em->flush();

        $this->get('logger')->info(
            LogConstants::PRATICA_CREATED, ['type' => $pratica->getType(), 'pratica' => $pratica]
        );

        return $pratica;
    }
}
