<?php

namespace AppBundle\Controller;

use AppBundle\Entity\TerminiUtilizzo;
use AppBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\VarDumper\VarDumper;

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
     * @Route("/terms_accept", name="terms_accept")
     */
    public function termsAcceptAction(Request $request)
    {
        $logger = $this->get('logger');

        $repo = $this->getDoctrine()->getRepository('AppBundle:TerminiUtilizzo');
        $terms = $repo->findAll();

        $data = array();
        $formBuilder = $this->createFormBuilder($data);
        foreach($terms as $term){
            $formBuilder->add((string)$term->getId(), CheckboxType::class, array(
                'label'    => 'Dò il consenso', //@todo usare translator
                'required' => true
            ));
        }
        $formBuilder->add('save', SubmitType::class, array('label' => 'Salva'));
        $form = $formBuilder->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $user = $this->getUser();
            if ($user instanceof User){
                $manager = $this->getDoctrine()->getManager();
                $user->setTermsAccepted(true);
                //@todo testare i logger
                $logger->info("User {$user->getId()} has accepted the terms of service");
                $manager->persist($user);
                try{
                    $manager->flush();
                }catch(\Exception $e){
                    $logger->error($e->getMessage());
                }

            }
            return $this->redirectToRoute('app_default_index');
        }

        return $this->render('AppBundle:Default:terms_accept.html.twig', array(
            'form' => $form->createView(),
            'terms' => $terms
        ));
    }

    /**
     * @Route("/pratiche", name="pratiche")
     */
    public function praticheAction(Request $request)
    {
        return new Response('Todo'); //@todo implementare controller
    }

}
