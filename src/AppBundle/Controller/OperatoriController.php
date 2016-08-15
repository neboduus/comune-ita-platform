<?php
namespace AppBundle\Controller;

use AppBundle\Entity\Pratica;
use AppBundle\Form\AzioniOperatore\NumeroFascicoloPraticaType;
use AppBundle\Form\AzioniOperatore\NumeroProtocolloPraticaType;
use AppBundle\Logging\LogConstants;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class OperatoriController
 * @Route("/operatori")
 */
class OperatoriController extends Controller
{
    /**
     * @Route("/",name="operatori_index")
     * @Template()
     * @return array
     */
    public function indexAction()
    {
        $praticheRepo = $this->getDoctrine()->getRepository('AppBundle:Pratica');
        $praticheMie = $praticheRepo->findBy(
            [
                'operatore' => $this->getUser(),
                'ente' => $this->getUser()->getEnte(),
                'status' => [
                    Pratica::STATUS_PENDING,
                    Pratica::STATUS_SUBMITTED,
                    Pratica::STATUS_REGISTERED,
                ],
            ]
        );

        $praticheLibere = $praticheRepo->findBy(
            [
                'operatore' => null,
                'ente' => $this->getUser()->getEnte(),
                'status' => [
                    Pratica::STATUS_PENDING,
                    Pratica::STATUS_SUBMITTED,
                    Pratica::STATUS_REGISTERED,
                ],
            ]
        );

        return array(
            'pratiche_mie'  => $praticheMie,
            'pratiche_libere'  => $praticheLibere,
        );
    }

    /**
     * @Route("/{pratica}/numero_di_fascicolo",name="operatori_set_numero_fascicolo_a_pratica")
     * @Template()
     * @return array
     */
    public function addNumeroDiFascicoloToPraticaAction(Request $request, Pratica $pratica)
    {
        $form = $this->createForm(NumeroFascicoloPraticaType::class, $pratica);
        $form->add('submit', SubmitType::class, array(
            'label' => $this->get('translator')->trans('salva'),
        ));
        $form->handleRequest($request);

        if ($form->isValid()) {
            $this->get('logger')->info(sprintf(LogConstants::PRATICA_FASCICOLO_ASSEGNATO, $pratica->getId(), $pratica->getNumeroFascicolo()));

            $this->getDoctrine()->getManager()->flush();
        }

        return array('form' => $form->createView());
    }

    /**
     * @Route("/{pratica}/numero_di_protocollo",name="operatori_set_numero_protocollo_a_pratica")
     * @Template()
     * @return array
     */
    public function addNumeroDiProtocolloToPraticaAction(Request $request, Pratica $pratica)
    {
        $form = $this->createForm(NumeroProtocolloPraticaType::class, $pratica);
        $form->add('submit', SubmitType::class, array(
            'label' => $this->get('translator')->trans('salva'),
        ));
        $form->handleRequest($request);

        if ($form->isValid()) {
            $this->get('logger')->info(sprintf(LogConstants::PRATICA_PROTOCOLLO_ASSEGNATO, $pratica->getId(), $pratica->getNumeroProtocollo()));

            $this->getDoctrine()->getManager()->flush();
        }

        return array('form' => $form->createView());
    }
}
