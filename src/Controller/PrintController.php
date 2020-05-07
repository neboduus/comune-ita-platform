<?php

namespace App\Controller;

use App\Entity\Pratica;
use App\Entity\Servizio;
use App\Multitenancy\TenantAwareController;
use App\Services\ModuloPdfBuilderService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use App\Multitenancy\Annotations\MustHaveTenant;

/**
 * Class PraticheAnonimeController
 *
 * @package App\Controller
 * @Route("/print")
 * @MustHaveTenant()
 */
class PrintController extends TenantAwareController
{
    private $pdfBuilderService;

    public function __construct(ModuloPdfBuilderService $pdfBuilderService)
    {
        $this->pdfBuilderService = $pdfBuilderService;
    }

    /**
     * @Route("/pratica/{pratica}", name="print_pratiche")
     * @ParamConverter("pratica", class="App:Pratica")
     * @param Pratica $pratica
     * @return Response
     */
    public function printPratica(Pratica $pratica)
    {
        $user = $pratica->getUser();
        $form = $this->createForm('App\Form\FormIO\FormIORenderType', $pratica);

        return $this->render('Print/printPratica.html.twig', [
            'formserver_url' => $this->getParameter('formserver_public_url'),
            'form' => $form->createView(),
            'pratica' => $pratica,
            'user' => $user
        ]);
    }

    /**
     * @Route("/{pratica}/show", name="print_pratiche_show")
     * @ParamConverter("pratica", class="App:Pratica")
     * @param Pratica $pratica
     * @return Response
     */
    public function printPraticaShow(Pratica $pratica)
    {
        $fileContent = $this->pdfBuilderService->generatePdfUsingGotemberg($pratica);

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
     * @ParamConverter("service", class="App:Servizio")
     * @param Servizio $service
     * @return Response
     */
    public function printService(Servizio $service)
    {
        $pratica = $this->createApplication($service);
        $form = $this->createForm('App\Form\FormIO\FormIORenderType', $pratica);

        return $this->render('Print/printService.html.twig', [
            'formserver_url' => $this->getParameter('formserver_public_url'),
            'form' => $form->createView(),
            'pratica' => $pratica
        ]);
    }

    /**
     * @Route("/service/{service}/pdf", name="print_service_pdf")
     * @ParamConverter("service", class="App:Servizio")
     * @param Servizio $service
     * @return Response
     */
    public function printServicePdf(Servizio $service)
    {
        $fileContent = $this->pdfBuilderService->generateServicePdfUsingGotemberg($service);

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
     * @ParamConverter("service", class="App:Servizio")
     * @param Servizio $service
     * @return Response
     */
    public function previewService(Servizio $service)
    {
        $pratica = $this->createApplication($service);
        $form = $this->createForm('App\Form\FormIO\FormIORenderType', $pratica);

        return $this->render('Print/previewService.html.twig', [
            'formserver_url' => $this->getParameter('formserver_public_url'),
            'form' => $form->createView(),
            'pratica' => $pratica
        ]);
    }

    /**
     * @param Servizio $service
     * @return Pratica
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
