<?php

namespace App\Controller\Rest;

use App\Dto\Application;
use App\Entity\Pratica;
use App\Entity\Servizio;
use App\Model\LinksPagedList;
use App\Model\MetaPagedList;
use App\Model\PaymentOutcome;
use App\Multitenancy\Annotations\MustHaveTenant;
use App\Multitenancy\TenantAwareFOSRestController;
use App\Repository\PraticaRepository;
use App\Services\InstanceService;
use App\Services\ModuloPdfBuilderService;
use App\Services\PraticaStatusService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\Annotations as Rest;
use JMS\Serializer\Serializer;
use JMS\Serializer\SerializerBuilder;
use Nelmio\ApiDocBundle\Annotation\Model;
use Psr\Log\LoggerInterface;
use Swagger\Annotations as SWG;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class ServicesAPIController
 * @package App\Controller
 * @Route("/applications")
 * @MustHaveTenant()
 */
class ApplicationsAPIController extends TenantAwareFOSRestController
{
    private $em;

    private $is;

    /**
     * @var KernelInterface
     */
    protected $kernel;
    /**
     * @var ModuloPdfBuilderService
     */
    protected $pdfBuilder;
    /**
     * @var UrlGeneratorInterface
     */
    protected $router;
    /**
     * @var string
     */
    protected $baseUrl = '';
    /**
     * @var PraticaStatusService
     */
    private $statusService;

    public function __construct(
        EntityManagerInterface $em,
        InstanceService $is,
        PraticaStatusService $statusService,
        ModuloPdfBuilderService $pdfBuilder,
        UrlGeneratorInterface $router,
        KernelInterface $kernel
    )
    {
        $this->em = $em;
        $this->is = $is;
        $this->statusService = $statusService;
        $this->pdfBuilder = $pdfBuilder;
        $this->router = $router;
        $this->baseUrl = $this->router->generate('applications_api_list', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $this->kernel = $kernel;
    }

    /**
     * List all Applications
     * @Rest\Get("", name="applications_api_list")
     * @SWG\Parameter(
     *      name="Authorization",
     *      in="header",
     *      description="The authentication Bearer",
     *      required=false,
     *      type="string"
     *  )
     *
     * @SWG\Parameter(
     *      name="service",
     *      in="query",
     *      type="string",
     *      required=false,
     *      description="Slug of the service"
     *  )
     * @SWG\Parameter(
     *      name="offset",
     *      in="query",
     *      type="integer",
     *      required=false,
     *      description="Offset of the query"
     *  )
     * @SWG\Parameter(
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
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getApplications(Request $request)
    {
        $offset = intval($request->get('offset', 0));
        $limit = intval($request->get('limit', 10));

        $serviceParameter = $request->get('service', false);

        if ($limit > 100) {
            return $this->view(["Limit parameter is too high"], Response::HTTP_BAD_REQUEST);
        }

        $repositoryService = $this->getDoctrine()->getRepository('App:Servizio');
        $service = $repositoryService->findOneBy(['slug' => $serviceParameter]);

        $em = $this->getDoctrine()->getManager();
        /** @var PraticaRepository $repoApplications */
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

        $result = [];
        $result['meta']['count'] = $count;
        $result['meta']['parameter']['offset'] = $offset;
        $result['meta']['parameter']['limit'] = $limit;

        $result['links']['self'] = $this->generateUrl('applications_api_list', ['offset' => $offset, 'limit' => $limit], UrlGeneratorInterface::ABSOLUTE_URL);
        $result['links']['prev'] = null;
        $result['links']['next'] = null;
        $result ['data'] = [];

        if ($offset != 0) {
            $result['links']['prev'] = $this->generateUrl('applications_api_list', ['offset' => $offset - $limit, 'limit' => $limit], UrlGeneratorInterface::ABSOLUTE_URL);;
        }

        if ($offset + $limit < $count) {
            $result['links']['next'] = $this->generateUrl('applications_api_list', ['offset' => $offset + $limit, 'limit' => $limit], UrlGeneratorInterface::ABSOLUTE_URL);
        }

        /** @var Pratica[] $applications */
        $applications = $repoApplications->findBy($criteria, ['creationTime' => 'ASC'], $limit, $offset);
        foreach ($applications as $s) {
            $result ['data'][] = Application::fromEntity($s, $this->baseUrl . '/' . $s->getId());
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
    public function getApplication($id)
    {
        try {
            $repository = $this->getDoctrine()->getRepository('App:Pratica');
            /** @var Pratica $result */
            $result = $repository->find($id);
            if ($result === null) {
                return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
            }

            return $this->view(Application::fromEntity($result, $this->baseUrl . '/' . $result->getId()), Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->view(["Object conversion error"], Response::HTTP_NOT_FOUND);
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
     *     description="Object not found"
     * )
     * @SWG\Tag(name="applications")
     *
     * @param $id
     * @param $attachmentId
     * @return \FOS\RestBundle\View\View|Response
     */
    public function attachment($id, $attachmentId)
    {
        $repository = $this->getDoctrine()->getRepository('App:Pratica');
        /** @var Pratica $result */
        $result = $repository->find($id);
        if ($result === null) {
            return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
        }

        $repository = $this->getDoctrine()->getRepository('App:Allegato');
        $result = $repository->find($attachmentId);
        if ($result === null) {
            return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
        }

        /** @var File $file */
        $file = $result->getFile();
        if ($result->getType() == 'modulo_compilato') {
            $fileContent = file_get_contents($file->getPathname());
        } else {
            $path = $result->getCreatedAt()->format('Y/m-d/Hi');
            $fileContent = file_get_contents($this->kernel->getProjectDir() . '/var/uploads/pratiche/allegati/' . $path . DIRECTORY_SEPARATOR . $file->getFilename());
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
     * @param $id
     * @param Request $request
     * @param LoggerInterface $logger
     * @return \FOS\RestBundle\View\View
     */
    public function postApplicationPayment($id, Request $request, LoggerInterface $logger)
    {
        $repository = $this->getDoctrine()->getRepository('App:Pratica');
        /** @var Pratica $application */
        $application = $repository->find($id);

        if (!$application) {
            return $this->view("Object not found", Response::HTTP_NOT_FOUND);
        }

        if ($application->getStatus() != Pratica::STATUS_PAYMENT_OUTCOME_PENDING) {
            return $this->view("Application isn't in correct state", Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $paymentOutcome = new paymentOutcome();
        $form = $this->createForm('App\Form\PaymentOutcomeType', $paymentOutcome);
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
        /** @var Serializer $serializer */
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
            $logger->error(
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
