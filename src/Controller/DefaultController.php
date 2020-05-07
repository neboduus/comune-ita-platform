<?php

namespace App\Controller;

use App\Entity\CPSUser;
use App\Entity\TerminiUtilizzo;
use App\Logging\LogConstants;
use App\Multitenancy\Entity\Main\Tenant;
use App\Multitenancy\TenantAwareController;
use Symfony\Component\Security\Core\User\UserInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class DefaultController
 *
 * @package App\Controller
 */
class DefaultController extends TenantAwareController
{
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @Route("/", name="home")
     *
     * @return Response
     */
    public function index()
    {
        if ($this->hasTenant()) {
            return $this->forward(ServiziController::class . '::servizi');
        } else {
            $tenants = $this->getDoctrine()->getRepository(Tenant::class)->findAll();

            return $this->render('Main/index.html.twig', [
                'tenants' => $tenants,
            ]);
        }
    }

    /**
     * @Route("/privacy", name="privacy")
     */
    public function privacy()
    {
        return $this->render('Default/privacy.html.twig');
    }

    /**
     * @Route("/terms_accept/", name="terms_accept")
     * @param Request $request
     * @param LoggerInterface $logger
     * @return Response
     */
    public function termsAccept(Request $request, LoggerInterface $logger)
    {
        $repo = $this->getDoctrine()->getRepository('App:TerminiUtilizzo');

        /**
         * FIXME: gestire termini multipli
         * Il sistema è pronto per iniziare a gestire una accettazione di termini condizionale
         * con alcuni obbligatori e altri opzionali, tutti versionati. Al momento marchiamo tutti come accettati
         */

        /** @var TerminiUtilizzo[] $terms */
        $terms = $repo->findAll();

        $form = $this->setupTermsAcceptanceForm($terms)->handleRequest($request);

        $user = $this->getUser();

        if ($form->isSubmitted()) {
            $redirectRoute = $request->query->has('r') ? $request->query->get('r') : 'home';
            $redirectRouteParams = $request->query->has('p') ? unserialize($request->query->get('p')) : array();
            $redirectRouteQuery = $request->query->has('p') ? unserialize($request->query->get('q')) : array();

            return $this->markTermsAcceptedForUser($user, $terms, $logger, $redirectRoute, $redirectRouteParams, $redirectRouteQuery);
        } else {
            $logger->info(LogConstants::USER_HAS_TO_ACCEPT_TERMS, ['userid' => $user->getId()]);
        }

        return $this->render('Default/termsAccept.html.twig', [
            'form' => $form->createView(),
            'terms' => $terms,
            'user' => $user
        ]);
    }

    /**
     * @param UserInterface|CPSUser $user
     * @param LoggerInterface $logger
     * @param string $redirectRoute
     * @param array $redirectRouteParams
     * @param array $terms
     * @return RedirectResponse
     */
    private function markTermsAcceptedForUser($user, $terms, $logger, $redirectRoute = null, $redirectRouteParams = array(), $redirectRouteQuery = array()): RedirectResponse
    {
        $manager = $this->getDoctrine()->getManager();
        foreach ($terms as $term) {
            $user->addTermsAcceptance($term);
        }
        $logger->info(LogConstants::USER_HAS_ACCEPTED_TERMS, ['userid' => $user->getId()]);
        $manager->persist($user);
        try {
            $manager->flush();
        } catch (\Exception $e) {
            $logger->error($e->getMessage());
        }

        return $this->redirectToRoute($redirectRoute, array_merge($redirectRouteParams, $redirectRouteQuery));
    }

    /**
     * @param TerminiUtilizzo[] $terms
     * @return FormInterface
     */
    private function setupTermsAcceptanceForm($terms): FormInterface
    {
        $data = array();
        $formBuilder = $this->createFormBuilder($data);
        foreach ($terms as $term) {
            $formBuilder->add((string)$term->getId(), CheckboxType::class, array(
                'label' => $this->translator->trans('terms_do_il_consenso'),
                'required' => true,
            ));
        }
        $formBuilder->add('save', SubmitType::class, array('label' => $this->translator->trans('salva')));
        $form = $formBuilder->getForm();

        return $form;
    }
}
