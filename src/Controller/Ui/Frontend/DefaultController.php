<?php

namespace App\Controller\Ui\Frontend;

use App\Controller\Ui\Frontend\ServiziController;
use App\InstancesProvider;
use App\Services\InstanceService;
use App\Entity\CPSUser;
use App\Entity\Ente;
use App\Entity\Pratica;
use App\Entity\PraticaRepository;
use App\Entity\TerminiUtilizzo;
use App\Logging\LogConstants;
use App\Security\AbstractAuthenticator;
use App\Security\LogoutSuccessHandler;
use Artprima\PrometheusMetricsBundle\Metrics\Renderer;
use Exception;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class DefaultController
 *
 * @package App\Controller
 */
class DefaultController extends AbstractController
{

  /** @var LoggerInterface */
  private $logger;

  /** @var TranslatorInterface */
  private $translator;

  /** @var InstanceService */
  private $instanceService;

  /**
   * DefaultController constructor.
   * @param TranslatorInterface $translator
   * @param LoggerInterface $logger
   * @param InstanceService $instanceService
   * @param Renderer $metricsRenderer
   */
  public function __construct(TranslatorInterface $translator, LoggerInterface $logger, InstanceService $instanceService, Renderer $metricsRenderer)
  {
    $this->logger = $logger;
    $this->translator = $translator;
    $this->instanceService = $instanceService;
  }


  /**
   * @return Response|null
   */
  public function commonAction()
  {
    if ($this->instanceService->hasInstance()) {
      return $this->forward(ServiziController::class . '::serviziAction');
    } else {
      $enti = [];
      foreach (InstancesProvider::factory()->getInstances() as $identifier => $instance) {
        $indentifierParts = explode('/', $identifier);
        $enti[] = [
          'name' => $instance['name'] ?? ucwords(str_replace('-', ' ', $instance['identifier'])),
          'slug' => $indentifierParts[1]
        ];
      }

      return $this->render(
        'Default/common.html.twig',
        ['enti' => $enti]
      );
    }
  }

  /**
   * @return Response
   */
  public function statusAction()
  {
    return new JsonResponse(json_encode(['status' => 'ok']), Response::HTTP_OK, [], true);
  }

  /**
   * @Route("/", name="instance_home")
   * @return Response
   */
  public function indexAction(Request $request)
  {
    $ente = $this->instanceService->getCurrentInstance();
    if ($ente->isSearchAndCatalogueEnabled()) {
      return $this->forward(ServiziController::class . '::serviziAction');
    }
    return $this->redirect($ente->getServicesUrl());
  }

  /**
   * @Route("/privacy", name="privacy")
   */
  public function privacyAction()
  {
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

    $repo = $this->getDoctrine()->getRepository('App\Entity\TerminiUtilizzo');
    $terms = $repo->findAll();
    $form = $this->setupTermsAcceptanceForm($terms)->handleRequest($request);
    $user = $this->getUser();

    if ($form->isSubmitted()) {
      $redirectRoute = $request->query->has('r') ? $request->query->get('r') : 'home';
      $redirectRouteParams = $request->query->has('p') ? unserialize($request->query->get('p')) : array();
      $redirectRouteQuery = $request->query->has('q') ? unserialize($request->query->get('q')) : array();

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

    return $this->render( 'Default/termsAccept.html.twig', [
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

    $params = array_merge($redirectRouteParams, $redirectRouteQuery) ?? [];
    try {
      $redirectUrl = $this->generateUrl($redirectRoute, $params);
      return $this->redirect($redirectUrl);
    } catch (\Exception $e) {
      $this->logger->error('Error generating redirect url after terms accept: ', ['redirect_route' => $redirectRoute, 'params' => $params]);
    }
    return $this->redirectToRoute('terms_accept');
  }
}
