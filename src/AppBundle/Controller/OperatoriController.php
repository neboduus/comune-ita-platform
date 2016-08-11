<?php
namespace AppBundle\Controller;

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
     */
    public function indexAction()
    {

    }
}
