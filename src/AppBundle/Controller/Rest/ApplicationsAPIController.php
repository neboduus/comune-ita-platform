<?php

namespace AppBundle\Controller\Rest;


use AppBundle\Entity\Pratica;
use AppBundle\Model\PaymentOutcome;

use AppBundle\Services\InstanceService;
use AppBundle\Services\ModuloPdfBuilderService;
use AppBundle\Services\PraticaStatusService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManager;
use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Form\FormInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use JMS\Serializer\SerializerBuilder;

/**
 * Class ServicesAPIController
 * @property EntityManager em
 * @property InstanceService is
 * @package AppBundle\Controller
 * @Route("/applications")
 */
class ApplicationsAPIController extends AbstractFOSRestController
{
  const CURRENT_API_VERSION = '1.0';

  /**
   * @var
   */
  private $statusService;

  /**
   * @var ModuloPdfBuilderService
   */
  protected $pdfBuilder;

  public function __construct(EntityManager $em, InstanceService $is, PraticaStatusService $statusService, ModuloPdfBuilderService $pdfBuilder)
  {
    $this->em = $em;
    $this->is = $is;
    $this->statusService = $statusService;
    $this->pdfBuilder = $pdfBuilder;
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
      return $this->view("Object not found", Response::HTTP_NOT_FOUND);
    }

    if ($application->getStatus() != Pratica::STATUS_PAYMENT_OUTCOME_PENDING) {
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
        $moduloCompilato = $this->pdfBuilder->createForPratica($application);
        $application->addModuloCompilato($moduloCompilato);
        $this->statusService->setNewStatus($application, Pratica::STATUS_SUBMITTED);

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

    return $this->view("Object Modified Successfully", Response::HTTP_OK);
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
