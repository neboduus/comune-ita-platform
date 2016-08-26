<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Pratica;
use AppBundle\Entity\Servizio;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * Class UserController
 * @package AppBundle\Controller
 * @Route("/user")
 */
class UserController extends Controller
{
    /**
     * @Route("/", name="user_dashboard")
     * @Template()
     * @param Request $request
     * @return array
     */
    public function indexAction(Request $request)
    {
        $user = $this->getUser();
        $praticheRepo = $this->getDoctrine()->getRepository('AppBundle:Pratica');

        $praticheDraft = $praticheRepo->findBy(
            [
                'user' => $user,
                'status' => Pratica::STATUS_DRAFT
            ],
            [
                'creationTime' => 'ASC'
            ]
        );

        $pratichePending = $praticheRepo->findBy(
            [
                'user' => $user,
                'status' => [
                    Pratica::STATUS_PENDING,
                    Pratica::STATUS_SUBMITTED,
                    Pratica::STATUS_REGISTERED
                ]
            ],
            [
                'creationTime' => 'ASC'
            ]
        );

        $praticheCompleted = $praticheRepo->findBy(
            [
                'user' => $user,
                'status' => Pratica::STATUS_COMPLETE
            ],
            [
                'creationTime' => 'ASC'
            ]
        );

        $praticheCancelled = $praticheRepo->findBy(
            [
                'user' => $user,
                'status' => Pratica::STATUS_CANCELLED
            ],
            [
                'creationTime' => 'ASC'
            ]
        );
        return array(
            'pratiche' => array(
                'draft'      => $praticheDraft,
                'pending'    => $pratichePending,
                'completed'  => $praticheCompleted,
                'cancelled'  => $praticheCancelled
            )
        );
    }

    /**
     * @Route("/profile", name="user_profile")
     * @Template()
     * @param Request $request
     *
     * @return array
     */
    public function profileAction(Request $request)
    {
        $user = $this->getUser();

        return [
            'user' => $user
        ];
    }

}
