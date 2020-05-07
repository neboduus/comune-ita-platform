<?php

namespace App\Controller;

use App\Entity\Servizio;
use App\Multitenancy\TenantAwareController;
use App\Services\ServizioHandlerRegistry;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use App\Multitenancy\Annotations\MustHaveTenant;

/**
 * Class ServiziController
 * @package App\Controller
 * @Route("/servizi")
 * @MustHaveTenant()
 */
class ServiziController extends TenantAwareController
{
    /**
     * @Route("/", name="servizi_list")
     * @return Response
     */
    public function servizi()
    {
        $serviziRepository = $this->getDoctrine()->getRepository('App:Servizio');
        $stickyServices = $serviziRepository->findBy(
            ['sticky' => true],
            ['name' => 'ASC',]
        );
        $servizi = $serviziRepository->findBy(
            ['status' => [1, 2]],
            ['name' => 'ASC',]
        );

        return $this->render('Servizi/servizi.html.twig', [
            'sticky_services' => $stickyServices,
            'servizi' => $servizi,
            'user' => $this->getUser(),
        ]);
    }

    /**
     * @Route("/{slug}", name="servizi_show")
     * @param string $slug
     * @param ServizioHandlerRegistry $servizioHandlerRegistry
     * @return Response
     */
    public function serviziDetail($slug, ServizioHandlerRegistry $servizioHandlerRegistry)
    {
        $serviziRepository = $this->getDoctrine()->getRepository('App:Servizio');
        /** @var Servizio $servizio */
        $servizio = $serviziRepository->findOneBy(['slug' => $slug]);
        if (!$servizio instanceof Servizio) {
            throw new NotFoundHttpException("Servizio $slug not found");
        }

        $serviziArea = $serviziRepository->findBy(['topics' => $servizio->getTopics()]);
        $handler = $servizioHandlerRegistry->getByName($servizio->getHandler());

        return $this->render('Servizi/serviziDetail.html.twig', [
            'servizio' => $servizio,
            'servizi_area' => $serviziArea,
            'user' => $this->getUser(),
            'handler' => $handler
        ]);
    }
}
