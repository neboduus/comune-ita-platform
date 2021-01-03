<?php


namespace App\Controller;


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
use App\Services\InstanceService;
use App\Services\ModuloPdfBuilderService;
use Doctrine\ORM\Query;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as Controller;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Vich\UploaderBundle\Mapping\PropertyMapping;
use Vich\UploaderBundle\Mapping\PropertyMappingFactory;
use Vich\UploaderBundle\Naming\DirectoryNamerInterface;

/**
 * Class AllegatoController
 * Dobbiamo fornire gli allegati sia agli operatori che agli utenti, quindi non montiamo la rotta sotto una url generica
 * Lasciamo il compito a ogni singola action
 * @Route("")
 */
class AllegatoController extends Controller
{

  /** @var TranslatorInterface $translator */
  private $translator;

  /** @var RouterInterface */
  private $router;

  /** @var LoggerInterface */
  private $logger;

  /** @var ValidatorInterface */
  private $validator;

  /** @var ModuloPdfBuilderService */
  private $moduloPdfBuilderService;

  /** @var DirectoryNamerInterface */
  private $directoryNamer;

  /** @var PropertyMappingFactory */
  private $propertyMappingFactory;

  /** @var InstanceService */
  private $instanceService;

  /** @var SessionInterface */
  private $session;

  /**
   * AllegatoController constructor.
   * @param TranslatorInterface $translator
   * @param RouterInterface $router
   * @param LoggerInterface $logger
   * @param ValidatorInterface $validator
   * @param ModuloPdfBuilderService $moduloPdfBuilderService
   * @param DirectoryNamerInterface $directoryNamer
   * @param PropertyMappingFactory $propertyMappingFactory
   * @param InstanceService $instanceService
   * @param SessionInterface $session
   */
  public function __construct(
    TranslatorInterface $translator,
    RouterInterface $router,
    LoggerInterface $logger,
    ValidatorInterface $validator,
    ModuloPdfBuilderService $moduloPdfBuilderService,
    DirectoryNamerInterface $directoryNamer,
    PropertyMappingFactory $propertyMappingFactory,
    InstanceService $instanceService,
    SessionInterface $session
  ) {
    $this->translator = $translator;
    $this->router = $router;
    $this->logger = $logger;
    $this->validator = $validator;
    $this->moduloPdfBuilderService = $moduloPdfBuilderService;
    $this->directoryNamer = $directoryNamer;
    $this->propertyMappingFactory = $propertyMappingFactory;
    $this->instanceService = $instanceService;
  }

  /**
   * @param Request $request
   * @Route("/pratiche/allegati/new",name="allegati_create_cpsuser")
   * @return mixed
   */
  public function cpsUserCreateAllegatoAction(Request $request)
  {
    $allegato = new Allegato();

    $form = $this->createForm(
      AllegatoType::class,
      $allegato,
      ['helper' => new TestiAccompagnatoriProcedura($this->translator, $this->instanceService->getPrefix())]
    );
    $form->add($this->translator->trans('salva'), SubmitType::class);

    $form->handleRequest($request);
    if ($form->isSubmitted() && $form->isValid()) {
      $allegato->setOwner($this->getUser());
      $em = $this->getDoctrine()->getManager();
      $em->persist($allegato);
      $em->flush();

      return new RedirectResponse($this->router->generate('allegati_list_cpsuser'));
    }

    return $this->render(
      'Allegato/cpsUserCreateAllegato.html.twig',
      [
        'form' => $form->createView(),
        'user' => $this->getUser(),
      ]
    );
  }

  /**
   * @param Request $request
   * @Route("/allegati",name="allegati_upload")
   * @return mixed
   */
  public function uploadAllegatoAction(Request $request)
  {
    $em = $this->getDoctrine()->getManager();
    if (!$this->session->isStarted()) {
      $this->session->start();
    }

    switch ($request->getMethod()) {

      case 'GET':
        $fileName = str_replace('/', '', $request->get('form'));
        $file = $em->getRepository('App:Allegato')->findOneBy(['originalFilename' => $fileName]);
        if ($file instanceof Allegato) {
          if ($file->getHash() == hash('sha256', $this->session->getId())) {
            return $this->createBinaryResponseForAllegato($file);
          } else {
            return $this->redirectToRoute('allegati_download_cpsuser', ['allegato' => $file]);
          }
        } else {
          return new Response('', Response::HTTP_NOT_FOUND);
        }
        break;

      case 'POST':
        try {

          $uploadedFile = $request->files->get('file');
          if (is_null($uploadedFile)) {
            $this->logger->error(LogConstants::ALLEGATO_UPLOAD_ERROR, $request->request->all());

            return new JsonResponse(
              ['status' => 'error', 'message' => LogConstants::ALLEGATO_UPLOAD_ERROR],
              Response::HTTP_INTERNAL_SERVER_ERROR
            );
          }

          if (!is_file($uploadedFile->getRealPath())) {
            $this->logger->error(LogConstants::ALLEGATO_FILE_NOT_FOUND, $request->request->all());

            return new JsonResponse(
              ['status' => 'error', 'message' => LogConstants::ALLEGATO_FILE_NOT_FOUND],
              Response::HTTP_NOT_FOUND
            );
          }

          $allegato = new Allegato();
          $allegato->setFile($uploadedFile);
          $description = $request->get('description') ?? 'Allegato senza descrizione';
          $allegato->setDescription($description);
          $allegato->setOriginalFilename($request->get('name'));
          $user = $this->getUser();
          if ($user instanceof CPSUser || $user instanceof User) {
            $allegato->setOwner($user);
          }
          $allegato->setHash(hash('sha256', $this->session->getId()));
          $em->persist($allegato);
          $em->flush();

          $data = [
            'id' => $allegato->getId(),
          ];

          return new JsonResponse($data);
        } catch (\Exception $e) {
          return new JsonResponse(
            ['status' => 'error', 'message' => 'Whoops, looks like something went wrong'],
            Response::HTTP_INTERNAL_SERVER_ERROR
          );
        }

        break;

      case 'DELETE':
        $fileName = str_replace('/', '', $request->get('form'));
        $file = $em->getRepository('App:Allegato')->findOneBy(['originalFilename' => $fileName]);
        if ($file instanceof Allegato) {

          if ($file->getOwner() != $this->getUser() && $file->getHash() != hash('sha256', $this->session->getId())) {
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
   * @param Request $request
   * @Route("/pratiche/allegati/upload/scia/{id}",name="allegati_scia_create_cpsuser", methods={"POST"})
   * @return mixed
   * @throws \Exception
   */
  public function cpsUserUploadAllegatoSciaAction(Request $request, Pratica $pratica)
  {

    $uploadedFile = $request->files->get('file');
    if (is_null($uploadedFile)) {
      $this->logger->error(LogConstants::ALLEGATO_UPLOAD_ERROR, $request->request->all());

      return new JsonResponse(
        ['status' => 'error', 'message' => LogConstants::ALLEGATO_UPLOAD_ERROR],
        Response::HTTP_INTERNAL_SERVER_ERROR
      );
    }

    if (!is_file($uploadedFile->getRealPath())) {
      $this->logger->error(LogConstants::ALLEGATO_FILE_NOT_FOUND, $request->request->all());

      return new JsonResponse(
        ['status' => 'error', 'message' => LogConstants::ALLEGATO_FILE_NOT_FOUND],
        Response::HTTP_NOT_FOUND
      );
    }

    $uploadedFile = $request->files->get('file');
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
      if (!$integrationRequest instanceof RichiestaIntegrazione) {
        throw new \Exception('Integration request not found.');
      }
      $allegato = new Integrazione();
      $allegato->setIdRichiestaIntegrazione($integrationRequest->getId());
    } else {
      $allegato = new AllegatoScia();
    }

    $allegato->setOriginalFilename($uploadedFile->getClientOriginalName());
    $allegato->setFile($uploadedFile);

    $description = $request->get('description') ?? 'Allegato senza descrizione';
    $allegato->setDescription($description);

    $allegato->setOwner($this->getUser());
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
   * @param Allegato $allegato
   * @Route("/pratiche/allegati/{allegato}", name="allegati_download_cpsuser")
   * @return BinaryFileResponse
   * @throws NotFoundHttpException
   */
  public function cpsUserAllegatoDownloadAction(Request $request, Allegato $allegato)
  {

    $user = $this->getUser();
    $canDownload = $allegato->getOwner() === $user;
    if (!$canDownload) {
      $pratica = $allegato->getPratiche()->first();
      if ($pratica instanceof Pratica) {
        if ($user instanceof CPSUser) {
          $relatedCFs = $pratica->getRelatedCFs();
          $canDownload = (is_array($relatedCFs) && in_array(
              $user->getCodiceFiscale(),
              $relatedCFs
            ) || $pratica->getUser() == $user);
        } elseif ($this->session->isStarted() && $this->session->has(Pratica::HASH_SESSION_KEY)) {
          $canDownload = $pratica->isValidHash(
            $this->session->get(Pratica::HASH_SESSION_KEY),
            $this->getParameter('hash_validity')
          );
        }
      }
    }
    if ($canDownload) {
      $this->logger->info(
        LogConstants::ALLEGATO_DOWNLOAD_PERMESSO_CPSUSER,
        [
          'user' => $user ? $user->getId() : $allegato->getHash(),
          'originalFileName' => $allegato->getOriginalFilename(),
          'allegato' => $allegato->getId(),
        ]
      );

      return $this->createBinaryResponseForAllegato($allegato);
    }
    $this->logUnauthorizedAccessAttempt($allegato, $this->logger);
    throw new NotFoundHttpException(); //security by obscurity
  }

  /**
   * @param Request $request
   * @Route("/operatori/allegati/upload/{id}",name="allegati_upload_operatore", methods={"POST"})
   * @return mixed
   */
  public function operatoreAllegatoUploadAction(Request $request, Pratica $pratica)
  {
    if ($pratica->getStatus() !== Pratica::STATUS_PENDING && $pratica->getStatus(
      ) !== Pratica::STATUS_PENDING_AFTER_INTEGRATION) {
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
    $pathParts = pathinfo($uploadedFile->getRealPath());
    $dirname = $pathParts['dirname'];
    $targetFile = $dirname.'/'.$pratica->getId().time();

    $allegato = new AllegatoOperatore();
    $allegato->setOriginalFilename($uploadedFile->getClientOriginalName());
    $allegato->setFile($uploadedFile);
    $allegato->setDescription($request->get('description') ?? $uploadedFile->getClientOriginalName());
    $allegato->setOwner($pratica->getUser());

    $violations = $this->validator->validate($allegato);

    if ($violations->count() > 0) {
      $messages = [];
      /** @var ConstraintViolationInterface $violation */
      foreach ($violations as $violation) {
        $messages[] = $violation->getMessage();
      }

      return new JsonResponse(implode(', ', $messages), Response::HTTP_BAD_REQUEST);
    }

    $em = $this->getDoctrine()->getManager();
    $em->persist($allegato);

    $em->flush();

    $data = [
      'name' => $allegato->getOriginalFilename(),
      'url' => '#',
      'id' => $allegato->getId(),
    ];

    @unlink($targetFile);

    return new JsonResponse($data);
  }

  /**
   * @param Request $request
   * @Route("/pratiche/allegati/message/upload/{id}",name="cps_user_allegato_messaggio_upload", methods={"POST"})
   * @return mixed
   */
  public function cpsUserAllegatoMessaggioUploadAction(Request $request, Pratica $pratica)
  {

    if (! in_array($pratica->getStatus(), [Pratica::STATUS_PENDING, Pratica::STATUS_DRAFT_FOR_INTEGRATION, Pratica::STATUS_PENDING_AFTER_INTEGRATION])){
      return new JsonResponse("Lo pratica con id: {$pratica->getId()} si trova in uno stato in cui non possono essere allegati file", Response::HTTP_BAD_REQUEST);
    }

    /** @var CPSUser $user */
    $user = $this->getUser();

    if ($pratica->getUser()->getId() !== $user->getId()) {
      return new JsonResponse("User can not access pratica {$pratica->getId()}", Response::HTTP_BAD_REQUEST);
    }

    $uploadedFile = $request->files->get('file');
    $pathParts = pathinfo($uploadedFile->getRealPath());
    $dirname = $pathParts['dirname'];
    $targetFile = $dirname.'/'.$pratica->getId().time();

    $allegato = new AllegatoMessaggio();
    $allegato->setOriginalFilename($uploadedFile->getClientOriginalName());
    $allegato->setFile($uploadedFile);
    $allegato->setDescription($request->get('description') ?? $uploadedFile->getClientOriginalName());
    $allegato->setOwner($pratica->getUser());

    $violations = $this->validator->validate($allegato);

    if ($violations->count() > 0) {
      $messages = [];
      /** @var ConstraintViolationInterface $violation */
      foreach ($violations as $violation) {
        $messages[] = $violation->getMessage();
      }

      return new JsonResponse(implode(', ', $messages), Response::HTTP_BAD_REQUEST);
    }

    $em = $this->getDoctrine()->getManager();
    $em->persist($allegato);

    $em->flush();


    $data = [
      'name' => $allegato->getOriginalFilename(),
      'url' => '#',
      'id' => $allegato->getId(),
    ];

    @unlink($targetFile);

    return new JsonResponse($data);
  }

  /**
   * @param Request $request
   * @Route("/operatori/allegati/message/upload/{id}",name="operatore_allegato_messaggio_upload", methods={"POST"})
   * @return mixed
   */
  public function operatoreAllegatoMessaggioUploadAction(Request $request, Pratica $pratica)
  {

    if (! in_array($pratica->getStatus(), [Pratica::STATUS_PENDING, Pratica::STATUS_DRAFT_FOR_INTEGRATION, Pratica::STATUS_PENDING_AFTER_INTEGRATION])){
      return new JsonResponse("Lo pratica con id: {$pratica->getId()} si trova in uno stato in cui non possono essere allegati file", Response::HTTP_BAD_REQUEST);
    }

    /** @var OperatoreUser $user */
    $user = $this->getUser();

    $isEnabled = in_array($pratica->getServizio()->getId(), $user->getServiziAbilitati()->toArray());
    if (!$isEnabled) {
      return new JsonResponse("User can not read pratica {$pratica->getId()}", Response::HTTP_BAD_REQUEST);
    }

    $uploadedFile = $request->files->get('file');
    $pathParts = pathinfo($uploadedFile->getRealPath());
    $dirname = $pathParts['dirname'];
    $targetFile = $dirname.'/'.$pratica->getId().time();

    $allegato = new AllegatoMessaggio();
    $allegato->setOriginalFilename($uploadedFile->getClientOriginalName());
    $allegato->setFile($uploadedFile);
    $allegato->setDescription($request->get('description') ?? $uploadedFile->getClientOriginalName());
    $allegato->setOwner($pratica->getUser());

    $violations = $this->validator->validate($allegato);

    if ($violations->count() > 0) {
      $messages = [];
      /** @var ConstraintViolationInterface $violation */
      foreach ($violations as $violation) {
        $messages[] = $violation->getMessage();
      }

      return new JsonResponse(implode(', ', $messages), Response::HTTP_BAD_REQUEST);
    }

    $em = $this->getDoctrine()->getManager();
    $em->persist($allegato);

    $em->flush();


    $data = [
      'name' => $allegato->getOriginalFilename(),
      'url' => '#',
      'id' => $allegato->getId(),
    ];

    @unlink($targetFile);

    return new JsonResponse($data);
  }

  /**
   * @param Request $request
   * @param Allegato $allegato
   * @Route("/operatori/allegati/{allegato}", name="allegati_download_operatore")
   * @return BinaryFileResponse
   * @throws NotFoundHttpException
   */
  public function operatoreAllegatoDownloadAction(Request $request, Allegato $allegato)
  {
    $user = $this->getUser();
    $isOperatoreAmongstTheAllowedOnes = false;
    $becauseOfPratiche = [];

    foreach ($allegato->getPratiche() as $pratica) {
      $isOperatoreEnabled = in_array($pratica->getServizio()->getId(), $user->getServiziAbilitati()->toArray());
      if ($pratica->getOperatore() === $user || $isOperatoreEnabled) {
        $becauseOfPratiche[] = $pratica->getId();
        $isOperatoreAmongstTheAllowedOnes = true;
      }
    }

    if ($isOperatoreAmongstTheAllowedOnes) {
      $this->logger->info(
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
    $this->logUnauthorizedAccessAttempt($allegato, $this->logger);
    throw new NotFoundHttpException(); //security by obscurity
  }

  /**
   * @param Request $request
   * @param Allegato $allegato
   * @Route("/operatori/risposta/{allegato}", name="risposta_download_operatore")
   * @return BinaryFileResponse
   * @throws NotFoundHttpException
   */
  public function operatoreRispostaDownloadAction(Request $request, Allegato $allegato)
  {
    $user = $this->getUser();
    $isOperatoreAmongstTheAllowedOnes = false;
    $becauseOfPratiche = [];

    $repo = $this->getDoctrine()->getRepository('App:Pratica');
    $pratiche = $repo->findBy(
      array('rispostaOperatore' => $allegato)
    );

    foreach ($pratiche as $pratica) {
      if (in_array($pratica->getServizio()->getId(), $user->getServiziAbilitati()->toArray())) {
        $becauseOfPratiche[] = $pratica->getId();
        $isOperatoreAmongstTheAllowedOnes = true;
      }
    }

    if ($isOperatoreAmongstTheAllowedOnes) {
      $this->logger->info(
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
    $this->logUnauthorizedAccessAttempt($allegato, $this->logger);
    throw new UnauthorizedHttpException();
  }

  /**
   * @Route("/operatori/{pratica}/risposta_non_firmata",name="allegati_download_risposta_non_firmata")
   * @param Pratica $pratica
   */
  public function scaricaRispostaNonFirmata(Pratica $pratica)
  {

    if ($pratica->getOperatore() !== $this->getUser()) {
      throw new AccessDeniedHttpException();
    }

    if ($pratica->getEsito() === null) {
      throw new NotFoundHttpException();
    }

    $unsignedResponse = $this->moduloPdfBuilderService->createUnsignedResponseForPratica($pratica);

    return $this->createBinaryResponseForAllegato($unsignedResponse);
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
        ->createQuery(
          "SELECT allegato
                FROM App\Entity\Allegato allegato
                WHERE (allegato INSTANCE OF App\Entity\Allegato OR allegato INSTANCE OF App\Entity\AllegatoScia)
                AND (allegato NOT INSTANCE OF App\Entity\ModuloCompilato )
                AND allegato.owner = :user
                ORDER BY allegato.filename ASC"
        )
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

    return $this->render(
      'Allegato/cpsUserListAllegati.html.twig',
      [
        'allegati' => $allegati,
        'user' => $this->getUser(),
      ]
    );
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
        $em = $this->getDoctrine()->getManager();
        $em->remove($allegato);
        $em->flush();
        $this->session->getFlashBag()
          ->add('info', $this->translator->trans('allegato.cancellato'));
        $this->logger->info(
          LogConstants::ALLEGATO_CANCELLAZIONE_PERMESSA,
          [
            'allegato' => $allegato,
            'user' => $this->getUser(),
          ]
        );
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
   * @return BinaryFileResponse
   */
  private function createBinaryResponseForAllegato(Allegato $allegato)
  {
    $filename = $allegato->getFilename();
    $directoryNamer = $this->directoryNamer;
    /** @var PropertyMapping $mapping */
    $mapping = $this->propertyMappingFactory->fromObject($allegato)[0];
    $destDir = $mapping->getUploadDestination().'/'.$directoryNamer->directoryName($allegato, $mapping);
    $filePath = $destDir.DIRECTORY_SEPARATOR.$filename;

    $filename = $allegato->getOriginalFilename();
    $filenameParts = explode('.', $filename);
    if (end($filenameParts) != $allegato->getFile()->getExtension()) {
      $filename = $allegato->getOriginalFilename().'.'.$allegato->getFile()->getExtension();
    }

    return new BinaryFileResponse(
      $filePath,
      200,
      [
        'Content-type' => 'application/octet-stream',
        'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
      ]
    );
  }

  /**
   * @param Allegato $allegato
   * @param LoggerInterface $logger
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
   * @param Allegato $allegato
   * @return \Symfony\Component\Form\Form|null
   */
  private function createDeleteFormForAllegato($allegato)
  {
    if ($this->canDeleteAllegato($allegato)) {
      return $this->createFormBuilder(array('id' => $allegato->getId()))
        ->add('id', HiddenType::class)
        ->add('elimina', SubmitType::class, ['attr' => ['class' => 'btn btn-xs btn-danger']])
        ->setAction(
          $this->router->generate(
            'allegati_delete_cpsuser',
            ['allegato' => $allegato->getId()]
          )
        )
        ->setMethod('DELETE')
        ->getForm();
    }

    return null;
  }
}