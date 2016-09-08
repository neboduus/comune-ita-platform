<?php


namespace AppBundle\Controller;


use AppBundle\Entity\Allegato;
use AppBundle\Form\Base\AllegatoType;
use AppBundle\Form\Extension\TestiAccompagnatoriProcedura;
use AppBundle\Logging\LogConstants;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
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
        if ($allegato->getOwner() === $user) {
            $logger->info(
                LogConstants::ALLEGATO_DOWNLOAD_PERMESSO_CPSUSER,
                [
                    'user' => $user->getId().' ('.$user->getNome().' '.$user->getCognome().')',
                    'originalFileName' => $allegato->getOriginalFilename(),
                    'allegato' => $allegato->getId(),
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
        $isOperatoreAmongstTheAllowedOnes = false;
        $becauseOfPratiche = [];

        foreach($allegato->getPratiche() as $pratica){
            if($pratica->getOperatore() === $user){
                $becauseOfPratiche[] = $pratica->getId();
                $isOperatoreAmongstTheAllowedOnes = true;
            }
        }

        if ($isOperatoreAmongstTheAllowedOnes) {
            $logger->info(
                LogConstants::ALLEGATO_DOWNLOAD_PERMESSO_OPERATORE,
                [
                    'user' => $user->getId().' ('.$user->getNome().' '.$user->getCognome().')',
                    'originalFileName' => $allegato->getOriginalFilename(),
                    'allegato' => $allegato->getId(),
                    'pratiche' => $becauseOfPratiche
                ]
            );

            return $this->createBinaryResponseForAllegato($allegato);
        }
        $this->logUnauthorizedAccessAttempt($allegato, $logger);
        throw new NotFoundHttpException(); //security by obscurity
    }

    /**
     * @param Request $request
     * @Route("/pratiche/allegati",name="allegati_create_cpsuser")
     * @Template()
     */
    public function cpsUserCreateAllegatoAction(Request $request)
    {
        $allegato = new Allegato();

        $form = $this->createForm(AllegatoType::class,$allegato,[ 'helper' => new TestiAccompagnatoriProcedura($this->get('translator'))]);
        $form->add($this->get('translator')->trans('salva'),SubmitType::class);

        $form->handleRequest($request);
        if($form->isValid()){
            $result = $this->get('validator')->validate($allegato);
            $allegato->setOwner($this->getUser());
            $em = $this->getDoctrine()->getManager();
            $em->persist($allegato);
            $em->flush();
        }

        return [ 'form' => $form->createView() ];
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
                'allegato' => $allegato->getId(),
            ]
        );
    }
}
