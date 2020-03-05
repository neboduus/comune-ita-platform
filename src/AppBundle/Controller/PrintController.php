<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Pratica;
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
   * @Route("/{pratica}", name="print_pratiche")
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

    return [
      'formserver_url' => $this->getParameter('formserver_public_url'),
      'form' => $form->createView(),
      'pratica' => $pratica,
      'user' => $user
    ];

  }


  /**
   * @Route("/{pratica}/test", name="print_pratiche_test")
   * @ParamConverter("pratica", class="AppBundle:Pratica")
   * @param Pratica $pratica
   *
   * @return array
   */
  public function printPraticaTestAction(Request $request, Pratica $pratica)
  {

    $pdfBuilfer = $this->container->get('ocsdc.modulo_pdf_builder');
    $pdfBuilfer->createForPratica($pratica);
    echo 'Generated';
    exit;

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
   * @Route("/{pratica}/pdf", name="pratiche_anonime_show_pdf")
   * @ParamConverter("pratica", class="AppBundle:Pratica")
   * @param Pratica $pratica
   *
   * @return BinaryFileResponse
   */
  public function showPdfAction(Request $request, Pratica $pratica)
  {
    $user = $pratica->getUser();
    $allegato = $this->container->get('ocsdc.modulo_pdf_builder')->showForPratica($pratica);


    return new BinaryFileResponse(
      $allegato->getFile()->getPath() . '/' . $allegato->getFile()->getFilename(),
      200,
      [
        'Content-type' => 'application/octet-stream',
        'Content-Disposition' => sprintf('attachment; filename="%s"', $allegato->getOriginalFilename() . '.' . $allegato->getFile()->getExtension()),
      ]
    );
  }
}
