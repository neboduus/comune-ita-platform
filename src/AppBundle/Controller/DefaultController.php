<?php

namespace AppBundle\Controller;

use AppBundle\Entity\CPSUser;
use AppBundle\Entity\TerminiUtilizzo;
use AppBundle\Logging\LogConstants;
use AppBundle\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
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
     *
     * @return Response
     */
    public function indexAction()
    {
        $user = $this->getUser();
        return $this->render('AppBundle:Default:index.html.twig', array('user' => $user));
    }

    /**
     * @Route("/terms_accept/", name="terms_accept")
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function termsAcceptAction(Request $request)
    {
        $logger = $this->get('logger');

        $repo = $this->getDoctrine()->getRepository('AppBundle:TerminiUtilizzo');
        $terms = $repo->findAll();

        $form = $this->setupTermsAcceptanceForm($terms)->handleRequest($request);

        $user = $this->getUser();

        if ($form->isSubmitted()) {
            $redirectRoute = $request->query->has('r') ? $request->query->get('r') : 'app_default_index';
            $redirectRouteParams = $request->query->has('p') ? unserialize($request->query->get('p')) : array();
            return $this->markTermsAcceptedForUser($user, $logger, $redirectRoute, $redirectRouteParams);
        }else{
            $logger->info(LogConstants::USER_HAS_TO_ACCEPT_TERMS, ['userid' => $user->getId()]);
        }

        return $this->render('AppBundle:Default:terms_accept.html.twig', array(
            'form' => $form->createView(),
            'terms' => $terms
        ));
    }

    /**
     * @param CPSUser $user
     * @param LoggerInterface $logger
     * @param string $redirectRoute
     * @param array $redirectRouteParams
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    private function markTermsAcceptedForUser($user, $logger, $redirectRoute = null, $redirectRouteParams = array()):RedirectResponse
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
        return $this->redirectToRoute($redirectRoute, $redirectRouteParams);
    }

    /**
     * @param TerminiUtilizzo[] $terms
     * @return FormInterface
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
