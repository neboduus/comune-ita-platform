<?php


namespace AppBundle\Controller;


use AppBundle\Entity\Allegato;
use AppBundle\Entity\CPSUser;
use AppBundle\Form\Base\AllegatoType;
use AppBundle\Form\Extension\TestiAccompagnatoriProcedura;
use AppBundle\Logging\LogConstants;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
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
     * @param Request $request
     * @Route("/pratiche/allegati/new",name="allegati_create_cpsuser")
     * @Template()
     * @return mixed
     */
    public function cpsUserCreateAllegatoAction(Request $request)
    {
        $allegato = new Allegato();

        $form = $this->createForm(AllegatoType::class, $allegato, ['helper' => new TestiAccompagnatoriProcedura($this->get('translator'))]);
        $form->add($this->get('translator')->trans('salva'), SubmitType::class);

        $form->handleRequest($request);
        if ($form->isValid()) {
            $allegato->setOwner($this->getUser());
            $em = $this->getDoctrine()->getManager();
            $em->persist($allegato);
            $em->flush();

            return new RedirectResponse($this->get('router')->generate('allegati_list_cpsuser'));
        }

        return ['form' => $form->createView()];
    }

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

        foreach ($allegato->getPratiche() as $pratica) {
            if ($pratica->getOperatore() === $user) {
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
                    'pratiche' => $becauseOfPratiche,
                ]
            );

            return $this->createBinaryResponseForAllegato($allegato);
        }
        $this->logUnauthorizedAccessAttempt($allegato, $logger);
        throw new NotFoundHttpException(); //security by obscurity
    }


    /**
     * @Route("/pratiche/allegati/", name="allegati_list_cpsuser")
     * @Template()
     */
    public function cpsUserListAllegatiAction()
    {
        $user = $this->getUser();
        $allegati = [];
        if ($user instanceof CPSUser) {
            $query = $this->getDoctrine()
                ->getManager()
                ->createQuery("SELECT allegato 
                FROM AppBundle\Entity\Allegato allegato 
                WHERE allegato INSTANCE OF AppBundle\Entity\Allegato 
                AND allegato.owner = :user")
                ->setParameter('user', $this->getUser());

            $retrievedAllegati = $query->getResult();
            foreach ($retrievedAllegati as $allegato) {
                $allegati[] = [
                    'allegato' => $allegato,
                    'deleteform' => $this->createDeleteFormForAllegato($allegato)->createView(),
                ];
            }
        }

        return [
            'allegati' => $allegati,
        ];
    }


    /**
     * @param Request  $request
     * @param Allegato $allegato
     * @Route("/pratiche/allegati/{allegato}/delete",name="allegati_delete_cpsuser")
     * @Method("DELETE")
     * @return RedirectResponse
     */
    public function cpsUserDeleteAllegatoAction(Request $request, Allegato $allegato)
    {
        $deleteForm = $this->createDeleteFormForAllegato($allegato);

        $deleteForm->handleRequest($request);

        if ($allegato->getOwner() === $this->getUser() && $deleteForm->isValid()) {
            if ($allegato->getPratiche()->count() > 0) {
                $this->get('session')->getFlashBag()
                    ->add('error', $this->get('translator')->trans('allegato.non_cancellabile'));

                $this->get('logger')->info(LogConstants::ALLEGATO_CANCELLAZIONE_NEGATA, [
                    'allegato' => $allegato,
                    'user' => $this->getUser(),
                ]);

                return new RedirectResponse($this->get('router')->generate('allegati_list_cpsuser'));
            }

            $em = $this->getDoctrine()->getManager();
            $em->remove($allegato);
            $em->flush();
            $this->get('session')->getFlashBag()
                ->add('info', $this->get('translator')->trans('allegato.cancellato'));
            $this->get('logger')->info(LogConstants::ALLEGATO_CANCELLAZIONE_PERMESSA, [
                'allegato' => $allegato,
                'user' => $this->getUser(),
            ]);
        }

        return new RedirectResponse($this->get('router')->generate('allegati_list_cpsuser'));
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

    /**
     * @param $allegato
     * @return \Symfony\Component\Form\Form
     */
    private function createDeleteFormForAllegato($allegato):Form
    {
        return $this->createFormBuilder(array('id' => $allegato->getId()))
            ->add('id', HiddenType::class)
            ->add('elimina', SubmitType::class)
            ->setAction($this->get('router')->generate('allegati_delete_cpsuser', ['allegato' => $allegato->getId()]))
            ->setMethod('DELETE')
            ->getForm();
    }
}
