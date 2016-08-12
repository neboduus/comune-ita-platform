<?php
namespace AppBundle\Controller;

use AppBundle\Entity\Pratica;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
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
}
