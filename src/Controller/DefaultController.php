<?php

namespace App\Controller;

use App\Entity\CPSUser;
use App\Entity\Ente;
use App\Entity\Pratica;
use App\Entity\PraticaRepository;
use App\Entity\TerminiUtilizzo;
use App\InstanceKernel;
use App\Logging\LogConstants;
use App\Security\AbstractAuthenticator;
use App\Security\LogoutSuccessHandler;
use App\Services\InstanceService;
use Exception;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class DefaultController
 *
 * @package App\Controller
 */
class DefaultController extends Controller
{

  /** @var LoggerInterface */
  private $logger;

  /** @var TranslatorInterface */
  private $translator;

  /**
   * DefaultController constructor.
   */
  public function __construct(TranslatorInterface $translator, LoggerInterface $logger)
  {
    $this->logger = $logger;
    $this->translator = $translator;
  }

  /**
   * @Route("/", name="home")
   *
   * @return Response
   */
  public function indexAction()
  {
    if ($this->container->get('kernel') instanceof InstanceKernel) {
      return $this->forward(ServiziController::class . '::serviziAction');
    } else {
      return $this->render(
        'Default/common.html.twig',
        ['enti' => $this->getDoctrine()->getRepository('App:Ente')->findAll()]
      );
    }
  }

  /**
   * @Route("/privacy", name="privacy")
   */
  public function privacyAction()
  {
  }

  /**
   * @Route("/login", name="login")
   */
  public function loginAction()
  {
    // Redirect in base a configurazione di istanza
    return $this->redirectToRoute($this->getAuthRedirect());
  }

  /**
   * @Route("/auth/login-pat", name="login_pat")
   */
  public function loginPatAction()
  {
    throw new UnauthorizedHttpException("Something went wrong in authenticator");
  }

  /**
   * @Route("/auth/login-open", name="login_open")
   * @param Request $request
   */
  public function loginOpenAction(Request $request)
  {
    if ($request->query->has('_abort')){
      return $this->redirectToRoute('home');
    }
    throw new UnauthorizedHttpException("Something went wrong in authenticator");
  }

  /**
   * @Route("/logout", name="user_logout")
   * @throws Exception
   * @see LogoutSuccessHandler
   */
  public function logout()
  {
    throw new Exception('This method can be blank - it will be intercepted by the logout key on your firewall');
  }

  /**
   * @Route("/terms_accept/", name="terms_accept")
   * @param Request $request
   *
   * @return Response
   */
  public function termsAcceptAction(Request $request)
  {
    $logger = $this->logger;

    $repo = $this->getDoctrine()->getRepository('App:TerminiUtilizzo');

    /**
     * FIXME: gestire termini multipli
     * Il sistema è pronto per iniziare a gestire una accettazione di termini condizionale
     * con alcuni obbligatori e altri opzionali, tutti versionati. Al momento marchiamo tutti come accettati
     */

    $terms = $repo->findAll();

    $form = $this->setupTermsAcceptanceForm($terms)->handleRequest($request);

    $user = $this->getUser();

    if ($form->isSubmitted()) {
      $redirectRoute = $request->query->has('r') ? $request->query->get('r') : 'home';
      $redirectRouteParams = $request->query->has('p') ? unserialize($request->query->get('p')) : array();
      $redirectRouteQuery = $request->query->has('p') ? unserialize($request->query->get('q')) : array();

      return $this->markTermsAcceptedForUser(
        $user,
        $logger,
        $redirectRoute,
        $redirectRouteParams,
        $redirectRouteQuery,
        $terms
      );
    } else {
      $logger->info(LogConstants::USER_HAS_TO_ACCEPT_TERMS, ['userid' => $user->getId()]);
    }

    return $this->render('Default/termsAccept.html.twig', [
      'form' => $form->createView(),
      'terms' => $terms,
      'user' => $user,
    ]);
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
      $formBuilder->add(
        (string)$term->getId(),
        CheckboxType::class,
        array(
          'label' => $this->translator->trans('terms_do_il_consenso'),
          'required' => true,
        )
      );
    }
    $formBuilder->add('save', SubmitType::class, array('label' => $this->translator->trans('salva')));
    $form = $formBuilder->getForm();

    return $form;
  }

  /**
   * @param CPSUser $user
   * @param LoggerInterface $logger
   * @param string $redirectRoute
   * @param array $redirectRouteParams
   * @param array $terms
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  private function markTermsAcceptedForUser(
    $user,
    $logger,
    $redirectRoute = null,
    $redirectRouteParams = array(),
    $redirectRouteQuery = array(),
    $terms
  ): RedirectResponse {
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
   * @Route("/metrics", name="sdc_metrics")
   *
   * @return Response
   */
  public function metricsAction(Request $request)
  {
    /** @var PraticaRepository $praticaRepository */
    $praticaRepository = $this->getDoctrine()->getRepository(Pratica::class);
    $metrics = $praticaRepository->getMetrics();

    $request->setRequestFormat('text');
    $response = new Response();

    return $this->render(
      'Default/metrics.html.twig',
      [
        'metrics' => $metrics,
      ]
    );
  }

  /**
   * @Route("/prometheus.json", name="prometheus")
   */
  public function prometheusAction(Request $request)
  {
    $result = [];
    $hostname = $request->getHost();
    $env = null;

    $scheme = $request->isSecure() ? 'https' : 'http';

    /** @var Ente[] $enti */
    $enti = $this->getDoctrine()->getRepository('App:Ente')->findAll();
    foreach ($enti as $ente) {
      $result[] = [
        "targets" => [$hostname],
        "labels" => [
          "job" => $hostname,
          "env" => $env,
          "__scheme__" => $scheme,
          "__metrics_path__" => "/".$ente->getSlug()."/metrics",
        ],
      ];
    }
    $request->setRequestFormat('json');

    return new JsonResponse(json_encode($result), 200, [], true);
  }

  private function getAuthRedirect()
  {
    if ($this->getParameter('login_route') == AbstractAuthenticator::LOGIN_TYPE_NONE) {
      return 'home';
    }

    return $this->getParameter('login_route');
  }
}
