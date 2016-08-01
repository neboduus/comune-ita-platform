<?php

namespace AppBundle\Controller;

use AppBundle\Entity\TerminiUtilizzo;
use AppBundle\Logging\Constants;
use AppBundle\Logging\LogConstants;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class DefaultController
 *
 * @package AppBundle\Controller
 */
class DefaultController extends Controller
{
    /**
     * @Route("/")
     */
    public function indexAction(Request $request)
    {
        $user = $this->getUser();
        return $this->render('AppBundle:Default:index.html.twig', array('user' => $user));
    }

    /**
     * @Route("/terms_accept/", name="terms_accept")
     */
    public function termsAcceptAction(Request $request)
    {
        $logger = $this->get('logger');


        $repo = $this->getDoctrine()->getRepository('AppBundle:TerminiUtilizzo');
        $terms = $repo->findAll();

        $form = $this->setupTermsAcceptanceForm($terms);

        $form->handleRequest($request);

        $user = $this->getUser();

        if ($form->isSubmitted()) {
            return $this->markTermsAcceptedForUser($user, $logger);
        } else {
            $logger->info(LogConstants::USER_HAS_TO_ACCEPT_TERMS, ['userid' => $user->getId()]);
        }

        return $this->render('AppBundle:Default:terms_accept.html.twig', array(
            'form' => $form->createView(),
            'terms' => $terms
        ));
    }

    /**
     * @Route("/pratiche/", name="pratiche")
     */
    public function praticheAction(Request $request)
    {
        return new Response('Todo'); //@todo implementare controller
    }

    /**
     * @param $user
     * @param $logger
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    private function markTermsAcceptedForUser($user, $logger)
    {
        $manager = $this->getDoctrine()->getManager();
        $user->setTermsAccepted(true);
        $logger->info(LogConstants::USER_HAS_ACCEPTED_TERMS, ['userid' => $user->getId()]);
        $manager->persist($user);
        try {
            $manager->flush();
        } catch (\Exception $e) {
            $logger->error($e->getMessage());
        }

        return $this->redirectToRoute('app_default_index');
    }

    /**
     * @param TerminiUtilizzo[] $terms
     * @return \Symfony\Component\Form\Form|\Symfony\Component\Form\FormInterface
     */
    private function setupTermsAcceptanceForm($terms):FormInterface
    {
        $translator = $this->get('translator');
        $data = array();
        $formBuilder = $this->createFormBuilder($data);
        foreach ($terms as $term) {
            $formBuilder->add((string) $term->getId(), CheckboxType::class, array(
                'label' => $translator->trans('terms_do_il_consenso'),
                'required' => true,
            ));
        }
        $formBuilder->add('save', SubmitType::class, array('label' => $translator->trans('salva')));
        $form = $formBuilder->getForm();

        return $form;
    }

}
