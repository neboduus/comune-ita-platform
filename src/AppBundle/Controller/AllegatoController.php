<?php


namespace AppBundle\Controller;


use AppBundle\Entity\Allegato;
use AppBundle\Logging\LogConstants;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class AllegatoController
 * Dobbiamo fornire gli allegati sia agli operatori che agli utenti, quindi non montiamo la rotta sotto una url generica
 * Lasciamo il compito a ogni singola action
 * @Route("")
 */
class AllegatoController extends Controller
{
    /**
     * @param Request  $request
     * @param Allegato $allegato
     * @Route("/pratiche/allegati/{allegato}", name="allegati_download_cpsuser")
     * @return BinaryFileResponse
     * @throws NotFoundHttpException
     */
    public function cpsUserAllegatoDownloadAction(Request $request, Allegato $allegato)
    {
        $logger = $this->get('logger');
        $user = $this->getUser();
        if ($allegato->getPratica()->getUser() === $user) {
            $logger->info(
                LogConstants::ALLEGATO_DOWNLOAD_PERMESSO_CPSUSER,
                [
                    'user' => $user->getId().' ('.$user->getNome().' '.$user->getCognome().')',
                    'originalFileName' => $allegato->getOriginalFilename(),
                    'pratica' => $allegato->getPratica()->getId(),
                ]
            );

            return $this->createBinaryResponseForAllegato($allegato);
        }
        $this->logUnauthorizedAccessAttempt($allegato, $logger);

        throw new NotFoundHttpException(); //security by obscurity
    }

    /**
     * @param Request  $request
     * @param Allegato $allegato
     * @Route("/operatori/allegati/{allegato}", name="allegati_download_operatore")
     * @return BinaryFileResponse
     * @throws NotFoundHttpException
     */
    public function operatoreAllegatoDownloadAction(Request $request, Allegato $allegato)
    {
        $logger = $this->get('logger');
        $user = $this->getUser();
        if ($allegato->getPratica()->getOperatore() === $user) {
            $logger->info(
                LogConstants::ALLEGATO_DOWNLOAD_PERMESSO_OPERATORE,
                [
                    'user' => $user->getId().' ('.$user->getNome().' '.$user->getCognome().')',
                    'originalFileName' => $allegato->getOriginalFilename(),
                    'pratica' => $allegato->getPratica()->getId(),
                ]
            );

            return $this->createBinaryResponseForAllegato($allegato);
        }
        $this->logUnauthorizedAccessAttempt($allegato, $logger);
        throw new NotFoundHttpException(); //security by obscurity
    }

    /**
     * @param Allegato $allegato
     * @return BinaryFileResponse
     */
    private function createBinaryResponseForAllegato(Allegato $allegato)
    {
        $filename = $allegato->getFilename();
        $directoryNamer = $this->get('ocsdc.allegati.directory_namer');
        $mapping = $this->get('vich_uploader.property_mapping_factory')->fromObject($allegato)[0];
        $destDir = $mapping->getUploadDestination().'/'.$directoryNamer->directoryName($allegato, $mapping);
        $filePath = $destDir.DIRECTORY_SEPARATOR.$filename;

        return new BinaryFileResponse(
            $filePath,
            200,
            [
                'Content-type' => 'application/octet-stream',
                'Content-Disposition' => sprintf('attachment; filename="%s"', $allegato->getOriginalFilename()),
            ]
        );
    }

    /**
     * @param Allegato $allegato
     * @param $logger
     */
    private function logUnauthorizedAccessAttempt(Allegato $allegato, $logger)
    {
        $logger->info(
            LogConstants::ALLEGATO_DOWNLOAD_NEGATO,
            [
                'originalFileName' => $allegato->getOriginalFilename(),
                'pratica' => $allegato->getPratica()->getId(),
            ]
        );
    }
}
