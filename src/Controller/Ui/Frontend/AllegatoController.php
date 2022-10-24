<?php


namespace App\Controller\Ui\Frontend;


use App\Entity\Allegato;
use App\Entity\AllegatoMessaggio;
use App\Entity\AllegatoOperatore;
use App\Entity\AllegatoScia;
use App\Entity\CPSUser;
use App\Entity\Integrazione;
use App\Entity\OperatoreUser;
use App\Entity\Pratica;
use App\Entity\RichiestaIntegrazione;
use App\Entity\User;
use App\Form\Base\AllegatoType;
use App\Form\Extension\TestiAccompagnatoriProcedura;
use App\Logging\LogConstants;
use App\Security\Voters\AttachmentVoter;
use App\Services\FileService\AbstractFileService;
use App\Services\FileService\AllegatoFileService;
use App\Validator\Constraints\ValidMimeType;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use League\Flysystem\FileNotFoundException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class AllegatoController
 * Dobbiamo fornire gli allegati sia agli operatori che agli utenti, quindi non montiamo la rotta sotto una url generica
 * Lasciamo il compito a ogni singola action
 * @Route("")
 */
class AllegatoController extends AbstractController
{

  /** @var TranslatorInterface $translator */
  private $translator;

  /** @var RouterInterface  */
  private $router;

  /** @var LoggerInterface */
  private $logger;

  /** @var AllegatoFileService */
  private $fileService;

  /** @var EntityManagerInterface */
  private $entityManager;

  private $allowedExtensions;
  /**
   * @var SessionInterface
   */
  private $session;


  /**
   * AllegatoController constructor.
   * @param TranslatorInterface $translator
   * @param RouterInterface $router
   * @param LoggerInterface $logger
   * @param AllegatoFileService $fileService
   * @param EntityManagerInterface $entityManager
   * @param SessionInterface $session
   * @param $allowedExtensions
   */
  public function __construct(
    TranslatorInterface $translator,
    RouterInterface $router,
    LoggerInterface $logger,
    AllegatoFileService $fileService,
    EntityManagerInterface $entityManager,
    SessionInterface $session,
    $allowedExtensions
  )
  {
    $this->translator = $translator;
    $this->router = $router;
    $this->logger = $logger;
    $this->fileService = $fileService;
    $this->entityManager = $entityManager;
    $this->session = $session;
    $this->allowedExtensions = array_merge(...$allowedExtensions);
  }

  /**
   * @param Request $request
   * @Route("/pratiche/allegati/new",name="allegati_create_cpsuser")
   * @return mixed
   */
  public function cpsUserCreateAllegatoAction(Request $request)
  {
    $allegato = new Allegato();

    $form = $this->createForm(AllegatoType::class, $allegato, ['helper' => new TestiAccompagnatoriProcedura($this->translator)]);
    $form->add($this->translator->trans('salva'), SubmitType::class);

    $form->handleRequest($request);
    if ($form->isSubmitted() && $form->isValid()) {
      $allegato->setOwner($this->getUser());
      $this->entityManager->persist($allegato);
      $this->entityManager->flush();

      return new RedirectResponse($this->router->generate('allegati_list_cpsuser'));
    }

    return $this->render( 'Allegato/cpsUserCreateAllegato.html.twig', [
      'form' => $form->createView(),
      'user' => $this->getUser(),
    ]);
  }

  /**
   * @param Request $request
   * @return JsonResponse
   * @Route("/upload", name="attachment_upload", methods={"POST"})
   */
  public function uploadAttachmentAction(Request $request)
  {
    try {
      $session = $this->session;
      if (!$session->isStarted()){
        $session->start();
      }

      $fileName = $request->request->get('original_filename');
      $description = $request->request->get('description') ?? Allegato::DEFAULT_DESCRIPTION;
      $mimeType = $request->request->get('mime_type') ?? Allegato::DEFAULT_MIME_TYPE;
      $protocolRequired = $request->request->get('protocol_required') ?? true;

      $allegato = new Allegato();
      $user  = $this->getUser();
      // Gli allegati sono di proprietà dei soli utenti
      if ($user instanceof CPSUser) {
        $allegato->setOwner($user);
      }

      $allegato->setOriginalFilename($fileName);
      $allegato->setDescription($description);
      $allegato->setMimeType($mimeType);
      $expireDate = new DateTime(AbstractFileService::PRESIGNED_PUT_EXPIRE_STRING);
      $allegato->setExpireDate($expireDate);
      $allegato->setProtocolRequired($protocolRequired);

      $allegato->setFilename($this->fileService->getName($allegato));

      $path = $this->fileService->getPath($allegato);
      $filePath = $path . DIRECTORY_SEPARATOR . $fileName;
      $allegato->setFile(new File($filePath, false));
      $allegato->setHash(hash('sha256', $session->getId()));

      $this->entityManager->persist($allegato);
      $this->entityManager->flush();

      $uri = $this->fileService->getPresignedPostRequestUri($allegato);
      $data = [
        'id' => $allegato->getId(),
        'uri' => $uri,
      ];
      return new JsonResponse($data, Response::HTTP_CREATED);
    } catch (\Exception $e) {
      $this->logger->error($e->getMessage());
      return new JsonResponse(['status' => 'error', 'message' => 'Whoops, looks like something went wrong'], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  /**
   * @param Request $request
   * @param Allegato $allegato
   * @return JsonResponse
   * @Route("/upload/{allegato}",name="attachment_upload_finalize", methods={"PUT"})
   */
  public function uploadAttachmentFinalizeAction(Request $request, Allegato $allegato)
  {
    // Todo: Come garantisco in sicurezza per gli anonimi?
    //$this->denyAccessUnlessGranted(AttachmentVoter::EDIT, $allegato);

    try {

      $session = $this->session;
      if (!$session->isStarted()){
        $session->start();
      }

      $fileHash = $request->request->get('file_hash');
      $allegato->setFileHash($fileHash);
      $allegato->setExpireDate(null);
      $this->entityManager->persist($allegato);
      $this->entityManager->flush();

      return new JsonResponse([], Response::HTTP_NO_CONTENT);
    } catch (\Exception $e) {
      $this->logger->error($e->getMessage());
      return new JsonResponse(['status' => 'error', 'message' => 'Whoops, looks like something went wrong'], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
  }



  /**
   * @param Request $request
   * @Route("/allegati",name="allegati_upload")
   * @return mixed
   */
  public function uploadAllegatoAction(Request $request)
  {
    $session = $this->session;
    if (!$session->isStarted()){
      $session->start();
    }

    switch ($request->getMethod()) {

      case 'GET':
        $fileName = str_replace('/', '', $request->get('form'));
        $allegato = $this->entityManager->getRepository('App\Entity\Allegato')->findOneBy(['originalFilename' => $fileName]);
        if ($allegato instanceof Allegato) {
          if ($allegato->getHash() == hash('sha256', $session->getId())){
            try {
              return $this->fileService->download($allegato);
            } catch (FileNotFoundException $e) {
              return new Response(["Attachment not found"], Response::HTTP_NOT_FOUND);
            }
          }else{
            return $this->redirectToRoute('allegati_download_cpsuser', ['allegato' => $allegato]);
          }
        } else {
          return new Response(["Attachment not found"], Response::HTTP_NOT_FOUND);
        }
        break;

      case 'POST':
        try {

          $uploadedFile = $request->files->get('file');
          if (is_null($uploadedFile)) {
            $this->logger->error(LogConstants::ALLEGATO_UPLOAD_ERROR, $request->request->all());
            return new JsonResponse(['status' => 'error', 'message' => LogConstants::ALLEGATO_UPLOAD_ERROR], Response::HTTP_INTERNAL_SERVER_ERROR);
          }

          if (!is_file($uploadedFile->getRealPath())) {
            $this->logger->error(LogConstants::ALLEGATO_FILE_NOT_FOUND, $request->request->all());
            return new JsonResponse(['status' => 'error', 'message' => LogConstants::ALLEGATO_FILE_NOT_FOUND], Response::HTTP_NOT_FOUND);
          }

          $allegato = new Allegato();
          $allegato->setFile($uploadedFile);
          $description = $request->get('description') ?? Allegato::DEFAULT_DESCRIPTION;
          $allegato->setDescription($description);
          $allegato->setOriginalFilename($request->get('name'));
          $user  = $this->getUser();
          if ($user instanceof CPSUser) {
            $allegato->setOwner($user);
          }
          $allegato->setHash(hash('sha256', $session->getId()));
          $this->entityManager->persist($allegato);
          $this->entityManager->flush();

          $data = [
            'id' => $allegato->getId(),
          ];
          return new JsonResponse($data);
        } catch (\Exception $e) {
          $this->logger->error($e->getMessage());
          return new JsonResponse(['status' => 'error', 'message' => 'Whoops, looks like something went wrong'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        break;

      case 'DELETE':
        $fileName = str_replace('/', '', $request->get('form'));
        $file = $this->entityManager->getRepository('App\Entity\Allegato')->findOneBy(['originalFilename' => $fileName]);
        if ($file instanceof Allegato) {

          if ($file->getOwner() != $this->getUser() && $file->getHash() != hash('sha256', $session->getId())) {
            return new Response('', Response::HTTP_FORBIDDEN);
          }

          $applications = $file->getPratiche();
          /** @var Pratica $item */
          foreach ($applications as $item) {
            $item->removeAllegato($file);
            $this->entityManager->persist($item);
          }
          $this->entityManager->remove($file);
          $this->entityManager->flush();;

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
   * @param Request $request
   * @param Pratica $pratica
   * @return mixed
   * @throws \Exception
   * @Route("/pratiche/allegati/upload/scia/{id}",name="allegati_scia_create_cpsuser", methods={"POST"})
   */
  public function cpsUserUploadAllegatoSciaAction(Request $request, Pratica $pratica)
  {

    /** @var UploadedFile $uploadedFile */
    $uploadedFile = $request->files->get('file');
    if (is_null($uploadedFile)) {
      $this->logger->error(LogConstants::ALLEGATO_UPLOAD_ERROR, $request->request->all());
      return new JsonResponse(['status' => 'error', 'message' => LogConstants::ALLEGATO_UPLOAD_ERROR], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    if (!is_file($uploadedFile->getRealPath())) {
      $this->logger->error(LogConstants::ALLEGATO_FILE_NOT_FOUND, $request->request->all());
      return new JsonResponse(['status' => 'error', 'message' => LogConstants::ALLEGATO_FILE_NOT_FOUND], Response::HTTP_NOT_FOUND);
    }

    if ($uploadedFile->getSize() <= 0) {
      return new JsonResponse(['status' => 'error', 'message' => LogConstants::ALLEGATO_FILE_SIZE_ERROR], Response::HTTP_NOT_ACCEPTABLE);
    }

    $pathParts = pathinfo($uploadedFile->getRealPath());

    /*$targetFile = $dirname . '/' . $pratica->getId() . time();
    exec("openssl smime -verify -inform DER -in {$uploadedFile->getRealPath()} -noverify > {$targetFile} 2>&1");

    if (!in_array(mime_content_type($targetFile), array('application/pdf', 'application/octet-stream' ))) {
      unlink($targetFile);
      return new JsonResponse('Sono permessi solo file pdf firmati', Response::HTTP_BAD_REQUEST);
    }*/

    /*$content = file_get_contents($uploadedFile->getRealPath());
    @($parser = new \TCPDF_PARSER(ltrim($content)));
    list($xref, $data) = $parser->getParsedData();
    unset($parser);

    if (!isset($xref['trailer']['encrypt'])) {
      //throw new \Exception('Secured pdf file are currently not supported.');
      return new JsonResponse('Sono permessi solo file protetti dalla modifica', Response::HTTP_BAD_REQUEST);
    }*/


    if ($pratica->getStatus() == Pratica::STATUS_DRAFT_FOR_INTEGRATION) {
      $integrationRequest = $pratica->getRichiestaDiIntegrazioneAttiva();
      if ( !$integrationRequest instanceof RichiestaIntegrazione ) {
        throw new \Exception('Integration request not found.');
      }
      $allegato = new Integrazione();
      $allegato->setIdRichiestaIntegrazione($integrationRequest->getId());
    } else {
      $allegato = new AllegatoScia();
    }

    $allegato->setOriginalFilename($uploadedFile->getClientOriginalName());
    $allegato->setFile($uploadedFile);

    $description = $request->get('description') ?? Allegato::DEFAULT_DESCRIPTION;
    $allegato->setDescription($description);

    $allegato->setOwner($this->getUser());
    $this->entityManager->persist($allegato);
    $this->entityManager->flush();

    $data = [
      'name' => $allegato->getOriginalFilename(),
      'url' => '#',
      'id' => $allegato->getId(),
      'index' => $request->get('index') ?? null,
      'type' => $request->get('type') ?? null,
    ];

    /*if ($this->getParameter('generate_p7m_thumbnail') == true) {
      $p7mThumbnailService = $this->get('ocsdc.p7m_thumbnailer_service');
      if ($p7mThumbnailService->createThumbnailForAllegato($allegato)) {
        $data['url'] = $this->get('router')->generate('allegati_download_thumbnail_cpsuser',
          ['allegato' => $allegato->getId()]);
      }
    }*/

    //unlink($targetFile);
    return new JsonResponse($data);
  }

  /**
   * @param Request $request
   * @Route("/operatori/allegati/upload/{id}",name="allegati_upload_operatore", methods={"POST"})
   * @return mixed
   */
  public function operatoreAllegatoUploadAction(Request $request, Pratica $pratica)
  {
    if ($pratica->getStatus() !== Pratica::STATUS_PENDING && $pratica->getStatus() !== Pratica::STATUS_PENDING_AFTER_INTEGRATION){
      return new JsonResponse("Pratica {$pratica->getId()} is not pending", Response::HTTP_BAD_REQUEST);
    }

    /** @var OperatoreUser $user */
    $user = $this->getUser();

    $isEnabled = in_array($pratica->getServizio()->getId(), $user->getServiziAbilitati()->toArray());
    if (!$isEnabled) {
      return new JsonResponse("User can not read pratica {$pratica->getId()}", Response::HTTP_BAD_REQUEST);
    }

    if ($pratica->getOperatore()->getId() !== $user->getId()) {
      return new JsonResponse("User can not access pratica {$pratica->getId()}", Response::HTTP_BAD_REQUEST);
    }

    $uploadedFile = $request->files->get('file');
    if (!in_array($uploadedFile->getMimeType(), $this->allowedExtensions) || !array_key_exists($uploadedFile->getClientOriginalExtension(),$this->allowedExtensions)) {
      if (!in_array($uploadedFile->getClientMimeType(), $this->allowedExtensions)) {
        $this->logger->error('mime_type_invalid', [
          'mime_type' => $uploadedFile->getMimeType(),
          'client_mime_type' => $uploadedFile->getClientMimeType(),
        ]);
        return new JsonResponse($this->translator->trans(ValidMimeType::TRANSLATION_ID), Response::HTTP_BAD_REQUEST);
      }
    }

    $allegato = new AllegatoOperatore();
    $allegato->setOriginalFilename($uploadedFile->getClientOriginalName());
    $allegato->setFile($uploadedFile);
    $allegato->setDescription($request->get('description') ?? $uploadedFile->getClientOriginalName());
    $allegato->setOwner($pratica->getUser());
    $this->entityManager->persist($allegato);
    $this->entityManager->flush();

    $data = [
      'name' => $allegato->getOriginalFilename(),
      'url' => '#',
      'id' => $allegato->getId(),
    ];

    return new JsonResponse($data);
  }

  /**
   * @param Request $request
   * @Route("/pratiche/allegati/message/upload/{id}",name="cps_user_allegato_messaggio_upload", methods={"POST"})
   * @Route("/operatori/allegati/message/upload/{id}",name="operatore_allegato_messaggio_upload", methods={"POST"})
   * @return JsonResponse
   * @throws \Exception
   */
  public function allegatoMessaggioUploadAction(Request $request, Pratica $pratica)
  {
    if (!in_array($pratica->getStatus(), [Pratica::STATUS_PENDING, Pratica::STATUS_DRAFT_FOR_INTEGRATION, Pratica::STATUS_PENDING_AFTER_INTEGRATION])){
      return new JsonResponse($this->translator->trans('operatori.messaggi.pratica_id'). ' '.$pratica->getId(). ' ' .$this->translator->trans('operatori.messaggi.errore_carica_allegato'), Response::HTTP_BAD_REQUEST);
    }

    /** @var User $user */
    $user = $this->getUser();

    if ($user instanceof CPSUser) {
      if ($pratica->getUser()->getId() !== $user->getId()) {
        return new JsonResponse("User can not access pratica {$pratica->getId()}", Response::HTTP_BAD_REQUEST);
      }
    } else if ($user instanceof OperatoreUser) {
      $isEnabled = in_array($pratica->getServizio()->getId(), $user->getServiziAbilitati()->toArray());
      if (!$isEnabled) {
        return new JsonResponse("User can not read pratica {$pratica->getId()}", Response::HTTP_BAD_REQUEST);
      }
    } else {
      return new JsonResponse("User can not read pratica {$pratica->getId()}", Response::HTTP_BAD_REQUEST);
    }

    /** @var UploadedFile $uploadedFile */
    $uploadedFile = $request->files->get('file');

    if (!in_array($uploadedFile->getMimeType(), $this->allowedExtensions) || !array_key_exists($uploadedFile->getClientOriginalExtension(),$this->allowedExtensions)) {
      if (!in_array($uploadedFile->getClientMimeType(), $this->allowedExtensions)) {
        $this->logger->error('mime_type_invalid', [
          'mime_type' => $uploadedFile->getMimeType(),
          'client_mime_type' => $uploadedFile->getClientMimeType(),
        ]);
        return new JsonResponse($this->translator->trans(ValidMimeType::TRANSLATION_ID), Response::HTTP_BAD_REQUEST);
      }
    }


    $allegato = new AllegatoMessaggio();
    $allegato->setOriginalFilename($uploadedFile->getClientOriginalName());
    $allegato->setFile($uploadedFile);
    $allegato->setDescription($request->get('description') ?? $uploadedFile->getClientOriginalName());
    $allegato->setOwner($pratica->getUser());

    // Imposto riferimento a richiesta integrazione attiva
    if ($pratica->getStatus() == Pratica::STATUS_DRAFT_FOR_INTEGRATION) {
      $integrationRequest = $pratica->getRichiestaDiIntegrazioneAttiva();
      if ( !$integrationRequest instanceof RichiestaIntegrazione ) {
        return new JsonResponse('Integration request not found.');
      }
      $allegato->setIdRichiestaIntegrazione($integrationRequest->getId());
    }

    $this->entityManager->persist($allegato);
    $this->entityManager->flush();

    $data = [
      'name' => $allegato->getOriginalFilename(),
      'url' => '#',
      'id' => $allegato->getId(),
    ];

    return new JsonResponse($data);
  }

  /**
   * @param Allegato $allegato
   * @Route("/allegati/{allegato}", name="allegati_download", methods={"GET"})
   * @Route("/pratiche/allegati/{allegato}", name="allegati_download_cpsuser", methods={"GET"})
   * @Route("/operatori/allegati/{allegato}", name="allegati_download_operatore", methods={"GET"})
   * @Route("/operatori/risposta/{allegato}", name="risposta_download_operatore", methods={"GET"})
   * @return Response
   */
  public function allegatoDownloadAction(Allegato $allegato): Response
  {
    $this->denyAccessUnlessGranted(AttachmentVoter::DOWNLOAD, $allegato);
    try {
      return $this->fileService->download($allegato);
    } catch (FileNotFoundException $e) {
      return new Response(["Attachment not found"], Response::HTTP_NOT_FOUND);
    }
  }

  /**
   * @param Allegato $allegato
   * @Route("/allegati/{allegato}", name="allegati_delete", methods={"DELETE"})
   * @return Response
   */
  public function allegatoDeleteAction(Allegato $allegato)
  {
    $this->denyAccessUnlessGranted(AttachmentVoter::DELETE, $allegato);
    $this->entityManager->getRepository('App\Entity\Allegato');
    $this->entityManager->remove($allegato);
    $this->entityManager->flush();
    return new JsonResponse(null, Response::HTTP_NO_CONTENT);
  }

  /**
   * @Route("/pratiche/allegati/", name="allegati_list_cpsuser")
   */
  public function cpsUserListAllegatiAction()
  {
    $user = $this->getUser();
    $allegati = [];
    if ($user instanceof CPSUser) {
      /** @var Query $query */
      $query = $this->getDoctrine()
        ->getManager()
        ->createQuery("SELECT allegato
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

    return $this->render( 'Allegato/cpsUserListAllegati.html.twig',  [
      'allegati' => $allegati,
      'user' => $this->getUser(),
    ]);
  }


  /**
   * @param Request $request
   * @param Allegato $allegato
   * @Route("/pratiche/allegati/{allegato}/delete",name="allegati_delete_cpsuser", methods={"DELETE"})
   * @return RedirectResponse
   */
  public function cpsUserDeleteAllegatoAction(Request $request, Allegato $allegato)
  {
    $deleteForm = $this->createDeleteFormForAllegato($allegato);
    if ($deleteForm instanceof Form) {
      $deleteForm->handleRequest($request);

      if ($this->canDeleteAllegato($allegato) && $deleteForm->isValid()) {
        $this->entityManager->remove($allegato);
        $this->entityManager->flush();
        $this->session->getFlashBag()
          ->add('info', $this->translator->trans('allegato.cancellato'));
        $this->logger->info(LogConstants::ALLEGATO_CANCELLAZIONE_PERMESSA, [
          'allegato' => $allegato,
          'user' => $this->getUser(),
        ]);
      }
    }

    return new RedirectResponse($this->router->generate('allegati_list_cpsuser'));
  }

  private function canDeleteAllegato(Allegato $allegato)
  {
    return $allegato->getOwner() === $this->getUser()
      && $allegato->getPratiche()->count() == 0
      && !is_subclass_of($allegato, Allegato::class);
  }

  /**
   * @param Allegato $allegato
   * @return \Symfony\Component\Form\Form|null
   */
  private function createDeleteFormForAllegato($allegato)
  {
    if ($this->canDeleteAllegato($allegato)) {
      return $this->createFormBuilder(array('id' => $allegato->getId()))
        ->add('id', HiddenType::class)
        ->add($this->translator->trans('elimina'), SubmitType::class, ['attr' => ['class' => 'btn btn-xs btn-danger']])
        ->setAction($this->router->generate('allegati_delete_cpsuser',
          ['allegato' => $allegato->getId()]))
        ->setMethod('DELETE')
        ->getForm();
    }
    return null;
  }
}
