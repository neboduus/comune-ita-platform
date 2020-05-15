<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Allegato;
use AppBundle\Entity\Pratica;
use AppBundle\Entity\Servizio;
use AppBundle\Logging\LogConstants;
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
   * @Route("/pratica/{pratica}", name="print_pratiche")
   * @ParamConverter("pratica", class="AppBundle:Pratica")
   * @Template()
   * @param Pratica $pratica
   *
   * @return array
   */
  public function printPraticaAction(Request $request, Pratica $pratica)
  {
    $user = $pratica->getUser();
    $form = $this->createForm('AppBundle\Form\FormIO\FormIORenderType', $pratica);

    $attachments = $pratica->getAllegati();
    $preparedAttachments = [];
    if ( $attachments->count() > 0) {
      /** @var Allegato $a */
      foreach ($attachments as $a) {
        $temp = [];
        $temp['id'] = $a->getId();
        $temp['local_name'] = $a->getFilename();

        $originalFilenameParts = explode('-',  $a->getOriginalFilename());
        $userFilename = implode('-', array_slice($originalFilenameParts, 0, -5)) . '.' . $a->getFile()->getExtension();
        $temp['original_filename'] = $userFilename;
        $temp['hash'] = hash_file('md5', $a->getFile()->getPathname());

        $preparedAttachments[]=$temp;

      }

    }

    return [
      'formserver_url' => $this->getParameter('formserver_public_url'),
      'form' => $form->createView(),
      'pratica' => $pratica,
      'user' => $user,
      'attachments' => $preparedAttachments
    ];

  }

  /**
   * @Route("/{pratica}/show", name="print_pratiche_show")
   * @ParamConverter("pratica", class="AppBundle:Pratica")
   * @param Pratica $pratica
   *
   */
  public function printPraticaShowAction(Request $request, Pratica $pratica)
  {

    $fileContent = $this->container->get('ocsdc.modulo_pdf_builder')->generatePdfUsingGotemberg($pratica);

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
   * @Template()
   * @param Servizio $service
   *
   * @return array
   */
  public function printServiceAction(Request $request, Servizio $service)
  {

    $pratica = $this->createApplication($service);

    $form = $this->createForm('AppBundle\Form\FormIO\FormIORenderType', $pratica);

    return [
      'formserver_url' => $this->getParameter('formserver_public_url'),
      'form' => $form->createView(),
      'pratica' => $pratica
    ];
  }

  /**
   * @Route("/service/{service}/pdf", name="print_service_pdf")
   * @ParamConverter("service", class="AppBundle:Servizio")
   * @param Servizio $service
   *
   * @return Response
   */
  public function printServicePdfAction(Request $request, Servizio $service)
  {

    $fileContent = $this->container->get('ocsdc.modulo_pdf_builder')->generateServicePdfUsingGotemberg($service);

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
   * @Template()
   * @param Servizio $service
   *
   * @return array
   */
  public function previewServiceAction(Request $request, Servizio $service)
  {

    $pratica = $this->createApplication($service);

    $form = $this->createForm('AppBundle\Form\FormIO\FormIORenderType', $pratica);

    return [
      'formserver_url' => $this->getParameter('formserver_public_url'),
      'form' => $form->createView(),
      'pratica' => $pratica
    ];
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
