<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Pratica;
use AppBundle\Entity\Servizio;
use AppBundle\Entity\User;
use Craue\FormFlowBundle\Form\FormFlowInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use AppBundle\Logging\LogConstants;

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

        return $this->render('AppBundle:Default:pratiche.html.twig', array('user' => $user, 'pratiche' => $pratiche));
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
        $pratica = $this->createNewPratica($servizio, $user);

        return $this->redirectToRoute(
            'pratiche_compila',
            ['pratica' => $pratica->getId()]
        );
    }

    /**
     * @Route("/compila/{pratica}", name="pratiche_compila")
     * @ParamConverter("pratica", class="AppBundle:Pratica")
     *
     * @param Pratica $pratica
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function compilaAction(Pratica $pratica)
    {
        if ($pratica->getStatus() !== Pratica::STATUS_DRAFT) {
            return $this->redirectToRoute(
                'pratiche_show',
                ['pratica' => $pratica->getId()]
            );
        }

        /** @var FormFlowInterface $flow */
        $flow = $this->get('ocsdc.form.flow.asilonido');
        $flow->bind($pratica);

        $form = $flow->createForm();
        if ($flow->isValid($form)) {
            $flow->saveCurrentStepData($form);
            if ($flow->nextStep()) {
                $form = $flow->createForm();
            } else {
                $flow->reset();
                $pratica->setStatus(Pratica::STATUS_SUBMITTED);
                $this->getDoctrine()->getManager()->flush();

                $this->get('logger')->info(
                    LogConstants::PRATICA_UPDATED, ['id' => $pratica->getId(), 'pratica' => $pratica]
                );

                $this->addFlash(
                    'feedback',
                    $this->get('translator')->trans('pratica_ricevuta')
                );
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

    private function createNewPratica(Servizio $servizio, User $user)
    {
        $pratica = new Pratica();
        $pratica
            ->setServizio($servizio)
            ->setType($servizio->getSlug())
            ->setUser($user)
            ->setStatus(Pratica::STATUS_DRAFT);

        $em = $this->getDoctrine()->getManager();
        $em->persist($pratica);
        $em->flush();

        $this->get('logger')->info(
            LogConstants::PRATICA_CREATED, ['type' => $pratica->getType(), 'pratica' => $pratica]
        );

        return $pratica;
    }
}
