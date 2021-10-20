<?php

namespace AppBundle\Controller\Ui\Frontend;

use AppBundle\Entity\Allegato;
use AppBundle\Entity\Pratica;
use AppBundle\Entity\Servizio;
use AppBundle\Logging\LogConstants;
use AppBundle\Services\FileService;
use AppBundle\Services\ModuloPdfBuilderService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use TheCodingMachine\Gotenberg\Client;
use TheCodingMachine\Gotenberg\URLRequest;
use TheCodingMachine\Gotenberg\Request as GotembergRequest;


/**
 * Class PraticheAnonimeController
 *
 * @package AppBundle\Controller
 * @Route("/print")
 */
class PrintController extends Controller
{

  /**
   * @var ModuloPdfBuilderService
   */
  private $moduloPdfBuilderService;

  /**
   * @var FileService
   */
  private $fileService;

  /**
   * PrintController constructor.
   * @param ModuloPdfBuilderService $moduloPdfBuilderService
   * @param FileService $fileService
   */
  public function __construct(ModuloPdfBuilderService $moduloPdfBuilderService, FileService $fileService)
  {
    $this->moduloPdfBuilderService = $moduloPdfBuilderService;
    $this->fileService = $fileService;
  }


  /**
   * @Route("/pratica/{pratica}", name="print_pratiche")
   * @ParamConverter("pratica", class="AppBundle:Pratica")
   * @param Pratica $pratica
   *
   * @return Response
   */
  public function printPraticaAction(Request $request, Pratica $pratica)
  {
    $user = $pratica->getUser();
    $form = $this->createForm('AppBundle\Form\FormIO\FormIORenderType', $pratica);

    $attachments = $pratica->getAllegati();
    $preparedAttachments = [];
    $preparedNoProtocolAttachments = [];
    if ( $attachments->count() > 0) {
      /** @var Allegato $a */
      foreach ($attachments as $a) {
        $protocolRequired = $a->isProtocolRequired() ?? true;
        $temp = [];
        $temp['id'] = $a->getId();
        $temp['local_name'] = $a->getFilename();

        $originalFilenameParts = explode('-',  $a->getOriginalFilename());
        $userFilename = implode('-', array_slice($originalFilenameParts, 0, -5)) . '.' . $a->getFile()->getExtension();
        $temp['original_filename'] = $userFilename;
        $temp['hash'] = $a->getHash() ?? $this->fileService->getHash($a);
        if ($protocolRequired) {
          $preparedAttachments[]=$temp;
        } else {
          $preparedNoProtocolAttachments[]=$temp;
        }
      }
    }

    return $this->render( '@App/Print/printPratica.html.twig', [
      'formserver_url' => $this->getParameter('formserver_public_url'),
      'form' => $form->createView(),
      'pratica' => $pratica,
      'user' => $user,
      'attachments' => $preparedAttachments,
      'attachmentsNoProtocol' => $preparedNoProtocolAttachments,
    ]);

  }

  /**
   * @Route("/pratica/{pratica}/show", name="print_pratiche_show")
   * @ParamConverter("pratica", class="AppBundle:Pratica")
   * @param Request $request
   * @param Pratica $pratica
   * @return Response
   * @throws \TheCodingMachine\Gotenberg\ClientException
   * @throws \TheCodingMachine\Gotenberg\RequestException
   */
  public function printPraticaShowAction(Request $request, Pratica $pratica)
  {

    $fileContent = $this->moduloPdfBuilderService->generatePdfUsingGotemberg($pratica);

    // Provide a name for your file with extension
    $filename = time() . '.pdf';

    // Return a response with a specific content
    $response = new Response($fileContent);

    // Create the disposition of the file
    $disposition = $response->headers->makeDisposition(
      ResponseHeaderBag::DISPOSITION_ATTACHMENT,
      $filename
    );

    // Set the content disposition
    $response->headers->set('Content-Disposition', $disposition);

    // Dispatch request
    return $response;

  }

  /**
   * @Route("/service/{service}", name="print_service")
   * @ParamConverter("service", class="AppBundle:Servizio")
   * @param Servizio $service
   *
   * @return array
   */
  public function printServiceAction(Request $request, Servizio $service)
  {

    $pratica = $this->createApplication($service);

    $form = $this->createForm('AppBundle\Form\FormIO\FormIORenderType', $pratica);

    return $this->render( '@App/Print/printService.html.twig', [
      'formserver_url' => $this->getParameter('formserver_admin_url'),
      'form' => $form->createView(),
      'pratica' => $pratica
    ]);
  }

  /**
   * @Route("/service/{service}/pdf", name="print_service_pdf")
   * @ParamConverter("service", class="AppBundle:Servizio")
   * @param Request $request
   * @param Servizio $service
   *
   * @param ModuloPdfBuilderService $pdfBuilderService
   * @return Response
   * @throws \TheCodingMachine\Gotenberg\ClientException
   * @throws \TheCodingMachine\Gotenberg\RequestException
   */
  public function printServicePdfAction(Request $request, Servizio $service)
  {

    $fileContent = $this->moduloPdfBuilderService->generateServicePdfUsingGotemberg($service);

    // Provide a name for your file with extension
    $filename = time() . '.pdf';

    // Return a response with a specific content
    $response = new Response($fileContent);

    // Create the disposition of the file
    $disposition = $response->headers->makeDisposition(
      ResponseHeaderBag::DISPOSITION_ATTACHMENT,
      $filename
    );

    // Set the content disposition
    $response->headers->set('Content-Disposition', $disposition);

    // Dispatch request
    return $response;

  }

  /**
   * @Route("/service/{service}/preview", name="preview_service")
   * @ParamConverter("service", class="AppBundle:Servizio")
   * @param Servizio $service
   *
   * @return Response
   */
  public function previewServiceAction(Request $request, Servizio $service)
  {

    $pratica = $this->createApplication($service);

    $form = $this->createForm('AppBundle\Form\FormIO\FormIORenderType', $pratica);

    return $this->render( '@App/Print/previewService.html.twig', [
      'formserver_url' => $this->getParameter('formserver_admin_url'),
      'form' => $form->createView(),
      'pratica' => $pratica
    ]);
  }

  /**
   * @param Servizio $service
   * @return mixed
   */
  private function createApplication(Servizio $service)
  {
    $praticaClassName = $service->getPraticaFCQN();
    $pratica = new $praticaClassName();
    if (!$pratica instanceof Pratica) {
      throw new \RuntimeException("Wrong Pratica FCQN for servizio {$service->getName()}");
    }
    $pratica
      ->setServizio($service)
      ->setStatus(Pratica::STATUS_DRAFT);

    return $pratica;
  }


}
