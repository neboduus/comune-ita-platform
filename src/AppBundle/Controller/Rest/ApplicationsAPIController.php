<?php

namespace AppBundle\Controller\Rest;


use AppBundle\Dto\Application;
use AppBundle\Dto\Service;
use AppBundle\Entity\Allegato;
use AppBundle\Entity\Pratica;
use AppBundle\Entity\Servizio;
use AppBundle\Model\PaymentOutcome;
use AppBundle\Model\MetaPagedList;
use AppBundle\Model\LinksPagedList;
use AppBundle\Services\InstanceService;
use AppBundle\Services\ModuloPdfBuilderService;
use AppBundle\Services\PraticaStatusService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManager;
use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Form\FormInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use JMS\Serializer\SerializerBuilder;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class ServicesAPIController
 * @property EntityManager em
 * @property InstanceService is
 * @package AppBundle\Controller
 * @Route("/applications")
 */
class ApplicationsAPIController extends AbstractFOSRestController
{

  /**
   * @var
   */
  private $statusService;

  /**
   * @var ModuloPdfBuilderService
   */
  protected $pdfBuilder;

  protected $router;

  protected $baseUrl = '';

  public function __construct(EntityManager $em, InstanceService $is, PraticaStatusService $statusService, ModuloPdfBuilderService $pdfBuilder, UrlGeneratorInterface $router)
  {
    $this->em = $em;
    $this->is = $is;
    $this->statusService = $statusService;
    $this->pdfBuilder = $pdfBuilder;
    $this->router = $router;
    $this->baseUrl = $this->router->generate('applications_api_list', [], UrlGeneratorInterface::ABSOLUTE_URL);
  }

  /**
   * List all Applications
   *  @Rest\Get("", name="applications_api_list")
   *  @SWG\Parameter(
   *      name="Authorization",
   *      in="header",
   *      description="The authentication Bearer",
   *      required=false,
   *      type="string"
   *  )
   *
   *  @SWG\Parameter(
   *      name="service",
   *      in="query",
   *      type="string",
   *      required=false,
   *      description="Slug of the service"
   *  )
   *  @SWG\Parameter(
   *      name="offset",
   *      in="query",
   *      type="integer",
   *      required=false,
   *      description="Offset of the query"
   *  )
   *  @SWG\Parameter(
   *      name="limit",
   *      in="query",
   *      type="integer",
   *      required=false,
   *      description="Limit of the query",
   *      maximum="100"
   *  )
   *
   * @SWG\Response(
   *     response=200,
   *     description="Retrieve list of applications",
   *     @SWG\Schema(
   *         type="object",
   *         @SWG\Property(property="meta", type="object", ref=@Model(type=MetaPagedList::class)),
   *         @SWG\Property(property="links", type="object", ref=@Model(type=LinksPagedList::class)),
   *         @SWG\Property(property="data", type="array", @SWG\Items(ref=@Model(type=Application::class)))
   *     )
   * )
   * @SWG\Tag(name="applications")
   */

  public function getApplicationsAction(Request $request)
  {
    $offset = intval($request->get('offset', 0));
    $limit = intval($request->get('limit', 10));

    $serviceParameter = $request->get('service', false);

    if ( $limit  > 100 ) {
      return $this->view(["Limit parameter is too high"], Response::HTTP_BAD_REQUEST);
    }

    $queryParameters = ['offset' => $offset, 'limit' => $limit];
    if ($serviceParameter)
      $queryParameters['service'] = $serviceParameter;

    $repositoryService = $this->getDoctrine()->getRepository('AppBundle:Servizio');
    $service = $repositoryService->findOneBy(['slug' => $serviceParameter]);

    if ($serviceParameter && !$service) {
      return $this->view(["Service not found"], Response::HTTP_NOT_FOUND);
    }

    $em = $this->getDoctrine()->getManager();
    $repoApplications = $em->getRepository(Pratica::class);
    $query = $repoApplications->createQueryBuilder('a')
      ->select('count(a.id)');

    $criteria = [];
    if ($service instanceof Servizio) {
      $query
        ->where('a.servizio = :serviceId')
        ->setParameter('serviceId', $service->getId());

      $criteria = ['servizio' => $service->getId()];
    }

    $count = $query
      ->getQuery()
      ->getSingleScalarResult();


    $result=[];
    $result['meta']['count'] = $count;
    $result['meta']['parameter']['offset'] = $offset;
    $result['meta']['parameter']['limit'] = $limit;

    $result['links']['self'] = $this->generateUrl('applications_api_list', $queryParameters, UrlGeneratorInterface::ABSOLUTE_URL);
    $result['links']['prev'] = null;
    $result['links']['next'] = null;
    $result ['data'] = [];

    if ($offset != 0) {
      $queryParameters['offset'] = $offset - $limit;
      $result['links']['prev'] = $this->generateUrl('applications_api_list', $queryParameters, UrlGeneratorInterface::ABSOLUTE_URL);
    }

    if ($offset + $limit < $count) {
      $queryParameters['offset'] = $offset + $limit;
      $result['links']['next'] = $this->generateUrl('applications_api_list', $queryParameters, UrlGeneratorInterface::ABSOLUTE_URL);
    }


    $applications = $repoApplications->findBy($criteria, ['creationTime' => 'ASC'], $limit, $offset );
    foreach ($applications as $s) {
      $result ['data'][]= Application::fromEntity($s, $this->baseUrl . '/' . $s->getId());
    }
    return $this->view($result, Response::HTTP_OK);
  }


  /**
   * Retreive an Applications
   * @Rest\Get("/{id}", name="application_api_get")
   *
   * @SWG\Response(
   *     response=200,
   *     description="Retreive an Application",
   *     @Model(type=Application::class)
   * )
   *
   * @SWG\Response(
   *     response=404,
   *     description="Application not found"
   * )
   * @SWG\Tag(name="applications")
   *
   * @param $id
   * @return \FOS\RestBundle\View\View
   */
  public function getApplicationAction($id)
  {
    try {
      $repository = $this->getDoctrine()->getRepository('AppBundle:Pratica');
      $result = $repository->find($id);
      if ($result === null) {
        return $this->view(["Application not found"], Response::HTTP_NOT_FOUND);
      }

      return $this->view(Application::fromEntity($result, $this->baseUrl . '/' . $result->getId()), Response::HTTP_OK);
    } catch (\Exception $e) {
      return $this->view(["Identifier conversion error"], Response::HTTP_BAD_REQUEST);
    }
  }

  /**
   * Retreive an Applications attachment
   * @Rest\Get("/{id}/attachments/{attachmentId}", name="application_api_attachment_get")
   *
   * @SWG\Response(
   *     response=200,
   *     description="Retreive attachment file",
   * )
   *
   * @SWG\Response(
   *     response=404,
   *     description="Attachment not found"
   * )
   * @SWG\Tag(name="applications")
   *
   * @param $id
   * @return BinaryFileResponse|\FOS\RestBundle\View\View
   */
  public function attachmentAction($id,  $attachmentId)
  {

    $repository = $this->getDoctrine()->getRepository('AppBundle:Allegato');
    $result = $repository->find($attachmentId);
    if ($result === null) {
      return $this->view(["Attachment not found"], Response::HTTP_NOT_FOUND);
    }
    /** @var File $file */
    $file = $result->getFile();
    if ($result->getType() == 'modulo_compilato') {
      $fileContent = file_get_contents($file->getPathname());
    } else {
      $path = $result->getCreatedAt()->format('Y/m-d/Hi');
      $fileContent = file_get_contents($this->container->getParameter('kernel.project_dir'). '/var/uploads/pratiche/allegati/' . $path . DIRECTORY_SEPARATOR . $file->getFilename());
    }
    $filename = $result->getFilename();
    $response = new Response($fileContent);
    $disposition = $response->headers->makeDisposition(
      ResponseHeaderBag::DISPOSITION_ATTACHMENT,
      $filename
    );
    $response->headers->set('Content-Disposition', $disposition);

    return $response;
  }


  /**
   * Update payment data of an application
   * @Route("/{id}/payment", name="applications_payment_api_post")
   * @Rest\Post("/{id}/payment", name="applications_payment_api_post")
   *
   * @SWG\Parameter(
   *     name="Authorization",
   *     in="header",
   *     description="The authentication Bearer",
   *     required=true,
   *     type="string"
   * )
   *
   * @SWG\Parameter(
   *     name="Payment data",
   *     in="body",
   *     type="json",
   *     description="Update payment data of an application",
   *     required=true,
   *     @SWG\Schema(
   *         type="object",
   *         ref=@Model(type=PaymentOutcome::class)
   *     )
   * )
   *
   * @SWG\Response(
   *     response=200,
   *     description="Updated"
   * )
   *
   * @SWG\Response(
   *     response=400,
   *     description="Bad request"
   * )
   *
   * @SWG\Response(
   *     response=404,
   *     description="Not found"
   * )
   *
   * @SWG\Response(
   *     response=422,
   *     description="Unprocessable Entity"
   * )
   *
   * @SWG\Tag(name="applications")
   *
   * @param Request $request
   * @return \FOS\RestBundle\View\View
   */
  public function postApplicationPaymentAction($id, Request $request)
  {
    $repository = $this->getDoctrine()->getRepository('AppBundle:Pratica');
    $application = $repository->find($id);

    if (!$application) {
      return $this->view("Application not found", Response::HTTP_NOT_FOUND);
    }

    if (!in_array($application->getStatus(), [Pratica::STATUS_PAYMENT_OUTCOME_PENDING, Pratica::STATUS_PAYMENT_PENDING])) {
      return $this->view("Application isn't in correct state", Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    $paymentOutcome = new paymentOutcome();
    $form = $this->createForm('AppBundle\Form\PaymentOutcomeType', $paymentOutcome);
    $this->processForm($request, $form);

    if (!$form->isValid()) {
      $errors = $this->getErrorsFromForm($form);
      $data = [
        'type' => 'validation_error',
        'title' => 'There was a validation error',
        'errors' => $errors
      ];
      return $this->view($data, Response::HTTP_BAD_REQUEST);
    }

    $paymentData = $application->getPaymentData();
    $serializer = SerializerBuilder::create()->build();
    $paymentData['outcome'] = $serializer->toArray($paymentOutcome);
    $application->setPaymentData($paymentData);

    try {
      $this->em->persist($application);
      $this->em->flush();

      if ($paymentOutcome->getStatus() == 'OK') {
        $this->statusService->setNewStatus($application, Pratica::STATUS_PAYMENT_SUCCESS);

        // Invio la pratica
        $application->setSubmissionTime(time());
        $this->statusService->setNewStatus($application, Pratica::STATUS_PRE_SUBMIT);

      } else {
        $this->statusService->setNewStatus($application, Pratica::STATUS_PAYMENT_ERROR);
      }

    } catch (\Exception $e) {

      $data = [
        'type' => 'error',
        'title' => 'There was an error during save process',
        'description' => $e->getMessage()
      ];
      $this->get('logger')->error(
        $e->getMessage(),
        ['request' => $request]
      );
      return $this->view($data, Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    return $this->view("Application Payment Modified Successfully", Response::HTTP_OK);
  }

  /**
   * @param Request $request
   * @param FormInterface $form
   */
  private function processForm(Request $request, FormInterface $form)
  {
    $data = json_decode($request->getContent(), true);

    // Todo: find better way
    if (isset($data['data']) && count($data['data']) > 0) {
      $data['data'] = \json_encode($data['data']);
    } else {
      $data['data'] = \json_encode([]);
    }

    $clearMissing = $request->getMethod() != 'PATCH';
    $form->submit($data, $clearMissing);
  }

  /**
   * @param FormInterface $form
   * @return array
   */
  private function getErrorsFromForm(FormInterface $form)
  {
    $errors = array();
    foreach ($form->getErrors() as $error) {
      $errors[] = $error->getMessage();
    }
    foreach ($form->all() as $childForm) {
      if ($childForm instanceof FormInterface) {
        if ($childErrors = $this->getErrorsFromForm($childForm)) {
          $errors[] = $childErrors;
        }
      }
    }
    return $errors;
  }
}
