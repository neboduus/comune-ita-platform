<?php

namespace App\Controller;

use App\Entity\Ente;
use App\Entity\Pratica;
use App\Entity\Servizio;
use App\FormIO\ExpressionValidator;
use App\Handlers\Servizio\ForbiddenAccessException;
use App\Handlers\Servizio\ServizioHandlerRegistry;
use App\Logging\LogConstants;
use App\Services\DematerializedFormAllegatiAttacherService;
use App\Services\InstanceService;
use App\Services\ModuloPdfBuilderService;
use App\Services\PraticaStatusService;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class PraticheAnonimeController
 *
 * @package App\Controller
 * @Route("/pratiche-anonime")
 */
class PraticheAnonimeController extends Controller
{
  /**
   * @var LoggerInterface
   */
  protected $logger;

  /**
   * @var TranslatorInterface
   */
  protected $translator;

  /**
   * @var PraticaStatusService
   */
  protected $statusService;

  /**
   * @var ModuloPdfBuilderService
   */
  protected $pdfBuilder;

  /**
   * @var DematerializedFormAllegatiAttacherService
   */
  protected $dematerializer;

  /** @var InstanceService */
  protected $instanceService;

  /**
   * @var bool
   */
  protected $hashValidity;

  /** @var ExpressionValidator */
  protected $expressionValidator;

  /** @var ServizioHandlerRegistry */
  protected $servizioHandlerRegistry;

  /** @var SessionInterface */
  protected $session;

  /**
   * PraticaFlow constructor.
   *
   * @param LoggerInterface $logger
   * @param TranslatorInterface $translator
   * @param PraticaStatusService $statusService
   * @param ModuloPdfBuilderService $pdfBuilder
   * @param DematerializedFormAllegatiAttacherService $dematerializer
   * @param InstanceService $instanceService
   * @param $hashValidity
   * @param ExpressionValidator $expressionValidator
   * @param ServizioHandlerRegistry $servizioHandlerRegistry
   * @param SessionInterface $session
   */
  public function __construct(
    LoggerInterface $logger,
    TranslatorInterface $translator,
    PraticaStatusService $statusService,
    ModuloPdfBuilderService $pdfBuilder,
    DematerializedFormAllegatiAttacherService $dematerializer,
    InstanceService $instanceService,
    $hashValidity,
    ExpressionValidator $expressionValidator,
    ServizioHandlerRegistry $servizioHandlerRegistry,
    SessionInterface $session
  ) {
    $this->logger = $logger;
    $this->translator = $translator;
    $this->statusService = $statusService;
    $this->pdfBuilder = $pdfBuilder;
    $this->dematerializer = $dematerializer;
    $this->instanceService = $instanceService;
    $this->hashValidity = $hashValidity;
    $this->expressionValidator = $expressionValidator;
    $this->servizioHandlerRegistry = $servizioHandlerRegistry;
    $this->session = $session;
  }

  /**
   * @Route("/{servizio}/new", name="pratiche_anonime_new")
   * @ParamConverter("servizio", class="App:Servizio", options={"mapping": {"servizio": "slug"}})
   * @param Request $request
   * @param Servizio $servizio
   *
   * @return Response
   */
  public function newAction(Request $request, Servizio $servizio)
  {
    $handler = $this->servizioHandlerRegistry->getByName($servizio->getHandler());

    $ente = $this->instanceService->getCurrentInstance();

    if (!$ente instanceof Ente) {
      $this->logger->info(LogConstants::PRATICA_WRONG_ENTE_REQUESTED, ['headers' => $request->headers]);
      throw new \InvalidArgumentException(LogConstants::PRATICA_WRONG_ENTE_REQUESTED);
    }

    try {
      $handler->canAccess($servizio, $ente);
    } catch (ForbiddenAccessException $e) {
      $this->addFlash('warning', $this->translator->trans($e->getMessage(), $e->getParameters()));

      return $this->redirectToRoute('servizi_list');
    }

    try {

      return $handler->execute($servizio, $ente);
    } catch (\Exception $e) {
      $this->logger->error($e->getMessage(), ['servizio' => $servizio->getSlug()]);
      return $this->render(
        'Servizi/serviziFeedback.html.twig',
        array(
          'servizio' => $servizio,
          'status' => 'danger',
          'message' => $handler->getErrorMessage(),
          'message_detail' => $e->getMessage(),
        )
      );
    }
  }

  /**
   * @Route("/{pratica}", name="pratiche_anonime_show")
   * @ParamConverter("pratica", class="App:Pratica")
   * @param Request $request
   * @param Pratica $pratica
   *
   * @return Pratica[]|Response
   * @throws \Exception
   */
  public function showAction(Request $request, Pratica $pratica)
  {
    if ($pratica->isValidHash($this->getHash($request), $this->hashValidity)) {

      return $this->render(
        'PraticheAnonime/show.html.twig',
        [
          'pratica' => $pratica,
          'formserver_url' => $this->getParameter('formserver_public_url'),
        ]
      );
    }

    return new Response(null, Response::HTTP_FORBIDDEN);
  }

  private function getHash(Request $request)
  {
    if (!$this->session->isStarted()) {
      $this->session->start();
    }
    $hash = $request->query->get('hash');
    if ($hash) {
      $this->session->set(Pratica::HASH_SESSION_KEY, $hash);
    }

    return $hash;
  }

  /**
   * @Route("/{pratica}/payment-callback/{hash}", name="pratiche_anonime_payment_callback")
   * @ParamConverter("pratica", class="App:Pratica")
   * @param Request $request
   * @param Pratica $pratica
   * @param $hash
   * @return Response
   */
  public function paymentCallbackAction(Request $request, Pratica $pratica, $hash)
  {
    if ($pratica->isValidHash($hash, $this->hashValidity)) {
      $outcome = $request->get('esito');

      if ($outcome == 'OK') {
        $this->statusService->setNewStatus(
          $pratica,
          Pratica::STATUS_PAYMENT_OUTCOME_PENDING
        );
      }

      return $this->redirectToRoute(
        'pratiche_anonime_show',
        [
          'pratica' => $pratica,
          'hash' => $pratica->getHash(),
        ]
      );
    }

    return new Response(null, Response::HTTP_FORBIDDEN);
  }

  /**
   * @Route("/{pratica}/pdf", name="pratiche_anonime_show_pdf")
   * @ParamConverter("pratica", class="App:Pratica")
   * @param Request $request
   * @param Pratica $pratica
   * @return Response
   */
  public function showPdfAction(Request $request, Pratica $pratica)
  {
    if ($pratica->isValidHash($this->getHash($request), $this->hashValidity)) {
      $compiledModules = $pratica->getModuliCompilati();
      if (empty($compiledModules)) {
        return new Response('', Response::HTTP_NOT_FOUND);
      }
      $attachment = $compiledModules[0];
      $fileContent = file_get_contents($attachment->getFile()->getPathname());
      $filename = $pratica->getId().'.pdf';
      $response = new Response($fileContent);
      $disposition = $response->headers->makeDisposition(
        ResponseHeaderBag::DISPOSITION_ATTACHMENT,
        $filename
      );
      $response->headers->set('Content-Disposition', $disposition);
      $response->headers->set('Content-Type', 'application/pdf');

      return $response;
    }

    return new Response(null, Response::HTTP_FORBIDDEN);
  }

  /**
   * @Route("/formio/validate/{servizio}", name="anonymous_formio_validate")
   * @ParamConverter("servizio", class="App:Servizio", options={"mapping": {"servizio": "slug"}})
   *
   * @param Request $request
   * @param Servizio $servizio
   *
   * @return JsonResponse
   */
  public function formioValidateAction(Request $request, Servizio $servizio)
  {
    $validator = $this->expressionValidator;

    $errors = $validator->validateData(
      $servizio,
      $request->getContent()
    );

    $response = ['status' => 'OK', 'errors' => null];
    if (!empty($errors)) {
      $response = ['status' => 'KO', 'errors' => $errors];
    }

    return JsonResponse::create($response, Response::HTTP_OK);
  }
}
