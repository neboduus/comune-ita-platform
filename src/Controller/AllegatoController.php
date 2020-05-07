<?php

namespace App\Controller;

use App\Entity\Allegato;
use App\Entity\AllegatoScia;
use App\Entity\CPSUser;
use App\Entity\Integrazione;
use App\Entity\Pratica;
use App\Entity\User;
use App\Form\Base\AllegatoType;
use App\Form\Extension\TestiAccompagnatoriProcedura;
use App\Logging\LogConstants;
use App\Multitenancy\Annotations\MustHaveTenant;
use App\Multitenancy\TenantAwareController;
use App\Services\ModuloPdfBuilderService;
use App\Services\P7MThumbnailerService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Vich\UploaderBundle\Mapping\PropertyMapping;
use Vich\UploaderBundle\Mapping\PropertyMappingFactory;
use Vich\UploaderBundle\Naming\DirectoryNamerInterface;

/**
 * Class AllegatoController
 * Dobbiamo fornire gli allegati sia agli operatori che agli utenti, quindi non montiamo la rotta sotto una url generica
 * Lasciamo il compito a ogni singola action
 * @Route("")
 * @MustHaveTenant()
 */
class AllegatoController extends TenantAwareController
{
    private $pdfBuilderService;

    private $directoryNamer;

    private $logger;

    private $translator;

    private $propertyMappingFactory;

    public function __construct(
        ModuloPdfBuilderService $pdfBuilderService,
        DirectoryNamerInterface $directoryNamer,
        LoggerInterface $logger,
        TranslatorInterface $translator,
        PropertyMappingFactory $propertyMappingFactory
    )
    {
        $this->pdfBuilderService = $pdfBuilderService;
        $this->directoryNamer = $directoryNamer;
        $this->logger = $logger;
        $this->translator = $translator;
        $this->propertyMappingFactory = $propertyMappingFactory;
    }

    /**
     * @Route("/pratiche/allegati/new",name="allegati_create_cpsuser")
     * @param Request $request
     * @param TestiAccompagnatoriProcedura $testiAccompagnatoriProcedura
     * @return Response|RedirectResponse
     * @throws \Exception
     */
    public function cpsUserCreateAllegato(Request $request, TestiAccompagnatoriProcedura $testiAccompagnatoriProcedura)
    {
        $allegato = new Allegato();
        /** @var CPSUser $user */
        $user = $this->getUser();

        $form = $this->createForm(AllegatoType::class, $allegato, ['helper' => $testiAccompagnatoriProcedura]);
        $form->add($this->translator->trans('salva'), SubmitType::class);

        $form->handleRequest($request);
        if ($form->isValid()) {
            $allegato->setOwner($user);
            $em = $this->getDoctrine()->getManager();
            $em->persist($allegato);
            $em->flush();

            return new RedirectResponse($this->generateUrl('allegati_list_cpsuser'));
        }

        return $this->render('Allegato/cpsUserCreateAllegato.html.twig', [
            'form' => $form->createView(),
            'user' => $this->getUser(),
        ]);
    }

    /**
     * @Route("/allegati",name="allegati_upload")
     * @param Request $request
     * @return JsonResponse|Response
     * @throws \Exception
     */
    public function uploadAllegato(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        switch ($request->getMethod()) {

            case 'GET':
                $fileName = str_replace('/', '', $request->get('form'));
                $file = $em->getRepository('App:Allegato')->findOneBy(['originalFilename' => $fileName]);
                if ($file instanceof Allegato) {
                    return $this->redirectToRoute('allegati_download_cpsuser', ['allegato' => $file]);
                } else {
                    return new Response('', Response::HTTP_NOT_FOUND);
                }
                break;

            case 'POST':
                $uploadedFile = $request->files->get('file');
                $allegato = new Allegato();
                $allegato->setFile($uploadedFile);
                $description = $request->get('description') ?? 'Allegato senza descrizione';
                $allegato->setDescription($description);
                $allegato->setOriginalFilename($request->get('name'));
                /** @var CPSUser $user */
                $user = $this->getUser();
                if ($user instanceof User) {
                    $allegato->setOwner($user);
                }
                $em->persist($allegato);
                $em->flush();

                $data = [
                    'id' => $allegato->getId()
                ];
                return new JsonResponse($data);
                break;

            case 'DELETE':
                $fileName = str_replace('/', '', $request->get('form'));
                $file = $em->getRepository('App:Allegato')->findOneBy(['originalFilename' => $fileName]);
                if ($file instanceof Allegato) {
                    if ($file->getOwner() != $this->getUser() && $file->getOwner() != null) {
                        return new Response('', Response::HTTP_FORBIDDEN);
                    }

                    $applications = $file->getPratiche();
                    /** @var Pratica $item */
                    foreach ($applications as $item) {
                        $item->removeAllegato($file);
                        $em->persist($item);
                    }
                    $em->remove($file);
                    $em->flush();

                    return new Response('', Response::HTTP_OK);
                } else {
                    return new Response('', Response::HTTP_NOT_FOUND);
                }
                break;

            default:
                return new Response('', Response::HTTP_OK);
                break;
        }
    }

    /**
     * TODO: TestMe
     * @Route("/pratiche/allegati/upload/scia/{id}",name="allegati_scia_create_cpsuser", methods={"POST"})
     * @param Request $request
     * @param Pratica $pratica
     * @param P7MThumbnailerService $p7MThumbnailerService
     * @return JsonResponse
     * @throws \Exception
     */
    public function cpsUserUploadAllegatoScia(Request $request, Pratica $pratica, P7MThumbnailerService $p7MThumbnailerService)
    {
        $uploadedFile = $request->files->get('file');

        $pathParts = pathinfo($uploadedFile->getRealPath());
        $dirname = $pathParts['dirname'];

        $targetFile = $dirname . '/' . $pratica->getId() . time();
        exec("openssl smime -verify -inform DER -in {$uploadedFile->getRealPath()} -noverify > {$targetFile} 2>&1");

        if (!in_array(mime_content_type($targetFile), array('application/pdf', 'application/octet-stream'))) {
            unlink($targetFile);
            return new JsonResponse('Sono permessi solo file pdf firmati', Response::HTTP_BAD_REQUEST);
        }

        if ($pratica->getStatus() == Pratica::STATUS_DRAFT_FOR_INTEGRATION) {
            $allegato = new Integrazione();
        } else {
            $allegato = new AllegatoScia();
        }

        $description = $request->get('description') ?? 'Allegato senza descrizione';
        /** @var CPSUser $user */
        $user = $this->getUser();

        $allegato->setOriginalFilename($uploadedFile->getClientOriginalName())
            ->setFile($uploadedFile)
            ->setDescription($description)
            ->setOwner($user);

        $em = $this->getDoctrine()->getManager();
        $em->persist($allegato);
        $em->flush();

        $data = [
            'name' => $allegato->getOriginalFilename(),
            'url' => '#',
            'id' => $allegato->getId(),
            'index' => $request->get('index') ?? null,
            'type' => $request->get('type') ?? null,
        ];

        if ($this->getParameter('generate_p7m_thumbnail') == true) {
            if ($p7MThumbnailerService->createThumbnailForAllegato($allegato)) {
                $data['url'] = $this->generateUrl(
                    'allegati_download_thumbnail_cpsuser',
                    ['allegato' => $allegato->getId()]
                );
            }
        }

        @unlink($targetFile);

        return new JsonResponse($data);
    }

    /**
     * @Route("/pratiche/allegati/{allegato}", name="allegati_download_cpsuser")
     * @param Allegato $allegato
     * @return BinaryFileResponse
     */
    public function cpsUserAllegatoDownload(Allegato $allegato)
    {
        $user = $this->getUser();
        if ($allegato->getOwner() === $user) {
            $this->logger->info(
                LogConstants::ALLEGATO_DOWNLOAD_PERMESSO_CPSUSER,
                [
                    'user' => $user->getId() . ' (' . $user->getNome() . ' ' . $user->getCognome() . ')',
                    'originalFileName' => $allegato->getOriginalFilename(),
                    'allegato' => $allegato->getId(),
                ]
            );

            return $this->createBinaryResponseForAllegato($allegato);
        }
        $this->logUnauthorizedAccessAttempt($allegato);
        throw new NotFoundHttpException(); //security by obscurity
    }

    /**
     * @param Allegato $allegato
     * @return BinaryFileResponse
     */
    private function createBinaryResponseForAllegato(Allegato $allegato)
    {
        $filename = $allegato->getFilename();
        /** @var PropertyMapping $mapping */
        $mapping = $this->propertyMappingFactory->fromObject($allegato)[0];
        $destDir = $mapping->getUploadDestination() . '/' . $this->directoryNamer->directoryName($allegato, $mapping);
        $filePath = $destDir . DIRECTORY_SEPARATOR . $filename;

        return new BinaryFileResponse(
            $filePath,
            200,
            [
                'Content-type' => 'application/octet-stream',
                'Content-Disposition' => sprintf('attachment; filename="%s"', $allegato->getOriginalFilename() . '.' . $allegato->getFile()->getExtension()),
            ]
        );
    }

    /**
     * @param Allegato $allegato
     */
    private function logUnauthorizedAccessAttempt(Allegato $allegato)
    {
        $this->logger->info(
            LogConstants::ALLEGATO_DOWNLOAD_NEGATO,
            [
                'originalFileName' => $allegato->getOriginalFilename(),
                'allegato' => $allegato->getId(),
            ]
        );
    }

    /**
     * @Route("/pratiche/allegati/thumbnail/{allegato}", name="allegati_download_thumbnail_cpsuser")
     * @param Allegato $allegato
     * @return BinaryFileResponse
     */
    public function cpsUserAllegatoThumbnail(Allegato $allegato)
    {
        $user = $this->getUser();
        if ($allegato->getOwner() === $user) {
            $this->logger->info(
                LogConstants::ALLEGATO_DOWNLOAD_PERMESSO_CPSUSER,
                [
                    'user' => $user->getId() . ' (' . $user->getNome() . ' ' . $user->getCognome() . ')',
                    'originalFileName' => $allegato->getOriginalFilename(),
                    'allegato' => $allegato->getId(),
                ]
            );

            $filename = $allegato->getFilename();
            /** @var PropertyMapping $mapping */
            $mapping = $this->propertyMappingFactory->fromObject($allegato)[0];
            $destDir = $mapping->getUploadDestination() . '/' . $this->directoryNamer->directoryName($allegato, $mapping) . '/thumbnails';
            $filePath = $destDir . DIRECTORY_SEPARATOR . $filename . '.png';

            return new BinaryFileResponse($filePath);
        }

        $this->logUnauthorizedAccessAttempt($allegato);
        throw new NotFoundHttpException(); //security by obscurity
    }

    /**
     * @Route("/operatori/allegati/{allegato}", name="allegati_download_operatore")
     * @param Allegato $allegato
     * @return BinaryFileResponse
     * @throws NotFoundHttpException
     */
    public function operatoreAllegatoDownload(Allegato $allegato)
    {
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
            $this->logger->info(
                LogConstants::ALLEGATO_DOWNLOAD_PERMESSO_OPERATORE,
                [
                    'user' => $user->getId() . ' (' . $user->getNome() . ' ' . $user->getCognome() . ')',
                    'originalFileName' => $allegato->getOriginalFilename(),
                    'allegato' => $allegato->getId(),
                    'pratiche' => $becauseOfPratiche,
                ]
            );

            return $this->createBinaryResponseForAllegato($allegato);
        }

        $this->logUnauthorizedAccessAttempt($allegato);
        throw new NotFoundHttpException(); //security by obscurity
    }

    /**
     * @Route("/operatori/risposta/{allegato}", name="risposta_download_operatore")
     * @param Allegato $allegato
     * @return BinaryFileResponse
     * @throws NotFoundHttpException
     */
    public function operatoreRispostaDownload(Allegato $allegato)
    {
        $user = $this->getUser();
        $isOperatoreAmongstTheAllowedOnes = false;
        $becauseOfPratiche = [];

        $repo = $this->getDoctrine()->getRepository('App:Pratica');
        /** @var Pratica[] $pratiche */
        $pratiche = $repo->findBy(
            array('rispostaOperatore' => $allegato)
        );

        foreach ($pratiche as $pratica) {
            if ($pratica->getOperatore() === $user) {
                $becauseOfPratiche[] = $pratica->getId();
                $isOperatoreAmongstTheAllowedOnes = true;
            }
        }

        if ($isOperatoreAmongstTheAllowedOnes) {
            $this->logger->info(
                LogConstants::ALLEGATO_DOWNLOAD_PERMESSO_OPERATORE,
                [
                    'user' => $user->getId() . ' (' . $user->getNome() . ' ' . $user->getCognome() . ')',
                    'originalFileName' => $allegato->getOriginalFilename(),
                    'allegato' => $allegato->getId(),
                    'pratiche' => $becauseOfPratiche,
                ]
            );

            return $this->createBinaryResponseForAllegato($allegato);
        }

        $this->logUnauthorizedAccessAttempt($allegato);
        throw new NotFoundHttpException(); //security by obscurity
    }

    /**
     * @Route("/operatori/{pratica}/risposta_non_firmata",name="allegati_download_risposta_non_firmata")
     * @param Pratica $pratica
     * @return BinaryFileResponse
     */
    public function scaricaRispostaNonFirmata(Pratica $pratica)
    {
        if ($pratica->getOperatore() !== $this->getUser()) {
            throw new AccessDeniedHttpException();
        }

        if ($pratica->getEsito() === null) {
            throw new NotFoundHttpException();
        }

        $unsignedResponse = $this->pdfBuilderService->createUnsignedResponseForPratica($pratica);
        return $this->createBinaryResponseForAllegato($unsignedResponse);
    }

    /**
     * @Route("/pratiche/allegati/", name="allegati_list_cpsuser")
     * @return Response
     */
    public function cpsUserListAllegati()
    {
        $user = $this->getUser();
        $allegati = [];
        if ($user instanceof CPSUser) {
            /** @var EntityManager $manager */
            $manager = $this->getDoctrine()->getManager();

            /** @var Query $query */
            $query = $manager->createQuery("SELECT allegato
                FROM App\Entity\Allegato allegato
                WHERE (allegato INSTANCE OF App\Entity\Allegato OR allegato INSTANCE OF App\Entity\AllegatoScia)
                AND (allegato NOT INSTANCE OF App\Entity\ModuloCompilato )
                AND allegato.owner = :user
                ORDER BY allegato.filename ASC")
                ->setParameter('user', $this->getUser());

            $retrievedAllegati = $query->getResult();
            foreach ($retrievedAllegati as $allegato) {
                $deleteForm = $this->createDeleteFormForAllegato($allegato);
                $allegati[] = [
                    'allegato' => $allegato,
                    'deleteform' => $deleteForm ? $deleteForm->createView() : null,
                ];
            }
        }

        return $this->render('Allegato/cpsUserListAllegati.html.twig', [
            'allegati' => $allegati,
            'user' => $this->getUser(),
        ]);
    }

    /**
     * @param Allegato $allegato
     * @return \Symfony\Component\Form\FormInterface|null
     */
    private function createDeleteFormForAllegato($allegato)
    {
        return $this->canDeleteAllegato($allegato) ?
            $this->createFormBuilder(array('id' => $allegato->getId()))
                ->add('id', HiddenType::class)
                ->add('elimina', SubmitType::class, ['attr' => ['class' => 'btn btn-xs btn-danger']])
                ->setAction($this->generateUrl('allegati_delete_cpsuser', ['allegato' => $allegato->getId()]))
                ->setMethod('DELETE')
                ->getForm()
            : null;
    }

    private function canDeleteAllegato(Allegato $allegato)
    {
        return $allegato->getOwner() === $this->getUser()
            && $allegato->getPratiche()->count() == 0
            && !is_subclass_of($allegato, Allegato::class);
    }

    /**
     * @param Request $request
     * @param Allegato $allegato
     * @Route("/pratiche/allegati/{allegato}/delete",name="allegati_delete_cpsuser", methods={"DELETE"})
     * @return RedirectResponse
     */
    public function cpsUserDeleteAllegato(Request $request, Allegato $allegato)
    {
        $deleteForm = $this->createDeleteFormForAllegato($allegato);
        if ($deleteForm instanceof Form) {
            $deleteForm->handleRequest($request);

            if ($this->canDeleteAllegato($allegato) && $deleteForm->isValid()) {
                $em = $this->getDoctrine()->getManager();
                $em->remove($allegato);
                $em->flush();
                $this->addFlash('info', $this->translator->trans('allegato.cancellato'));
                $this->logger->info(LogConstants::ALLEGATO_CANCELLAZIONE_PERMESSA, [
                    'allegato' => $allegato,
                    'user' => $this->getUser(),
                ]);
            }
        }

        return new RedirectResponse($this->generateUrl('allegati_list_cpsuser'));
    }
}
