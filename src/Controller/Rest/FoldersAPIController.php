<?php

namespace App\Controller\Rest;

use App\Entity\Folder;
use App\Security\Voters\FolderVoter;
use App\Services\InstanceService;
use App\Utils\FormUtils;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Symfony\Component\Form\FormInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class FoldersAPIController
 * @property EntityManagerInterface em
 * @property InstanceService is
 * @package App\Controller
 * @Route("/folders")
 */
class FoldersAPIController extends AbstractFOSRestController
{
  const CURRENT_API_VERSION = '1.0';

  private $em;
  private $is;
  /**
   * @var TranslatorInterface $translator
   */
  private $translator;

  /** @var LoggerInterface */
  private $logger;

  public function __construct(TranslatorInterface $translator, EntityManagerInterface $em, InstanceService $is, LoggerInterface $logger)
  {
    $this->translator = $translator;
    $this->em = $em;
    $this->is = $is;
    $this->logger = $logger;
  }

  /**
   * List all Folders
   * @Rest\Get("", name="folders_api_list")
   *
   * @Security(name="Bearer")
   * @OA\Parameter(
   *     name="cf",
   *     in="query",
   *      @OA\Schema(
   *          type="string"
   *      ),
   *     description="Fiscal code of the folder's owner"
   * )
   * @OA\Parameter(
   *     name="title",
   *     in="query",
   *      @OA\Schema(
   *          type="string"
   *      ),
   *     description="Folder's title"
   * )
   *
   * @OA\Response(
   *     response=200,
   *     description="Retrieve list of Folders",
   *     @OA\JsonContent(
   *         type="array",
   *         @OA\Items(ref=@Model(type=Folder::class, groups={"read"}))
   *     )
   * )
   *
   * @OA\Response(
   *     response=403,
   *     description="Access denied"
   * )
   *
   * @OA\Tag(name="folders")
   * @param Request $request
   * @return View
   */
  public function getFoldersAction(Request $request): View
  {
    $this->denyAccessUnlessGranted(['ROLE_OPERATORE','ROLE_ADMIN']);

    $cf = $request->query->get('cf');
    $title = $request->query->get('title');

    $qb = $this->em->createQueryBuilder()
      ->select('folder')
      ->from('App:Folder', 'folder')
      ->leftJoin('folder.owner', 'owner');

    if (isset($cf)) {
      $qb->andWhere('lower(owner.codiceFiscale) = :cf')
        ->setParameter('cf', strtolower($cf));
    }

    if (isset($title)) {
      $qb->andWhere('lower(folder.title) = :title')
        ->setParameter('title', strtolower($title));
    }

    $folders = $qb
      ->getQuery()
      ->getResult();

    return $this->view($folders, Response::HTTP_OK);
  }

  /**
   * Retrieve a Folder
   * @Rest\Get("/{id}", name="folder_api_get")
   *
   * @Security(name="Bearer")
   *
   * @OA\Response(
   *     response=200,
   *     description="Retrieve a Folder",
   *     @Model(type=Folder::class, groups={"read"})
   * )
   *
   * @OA\Response(
   *     response=403,
   *     description="Access denied"
   * )
   *
   * @OA\Response(
   *     response=404,
   *     description="Folder not found"
   * )
   * @OA\Tag(name="folders")
   *
   * @param $id
   * @return View
   */
  public function getFolderAction($id): View
  {
    try {
      $repository = $this->getDoctrine()->getRepository('App\Entity\Folder');
      $folder = $repository->find($id);

      if ($folder === null) {
        return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
      }
      $this->denyAccessUnlessGranted(FolderVoter::VIEW, $folder);

      return $this->view($folder, Response::HTTP_OK);
    } catch (\Exception $e) {
      return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
    }
  }

  /**
   * Create a Folder
   * @Rest\Post(name="folders_api_post")
   *
   * @Security(name="Bearer")
   *
   * @OA\RequestBody(
   *     description="The Folder to create",
   *     required=true,
   *     @OA\MediaType(
   *         mediaType="application/json",
   *         @OA\Schema(
   *             type="object",
   *             ref=@Model(type=Folder::class, groups={"write"})
   *         )
   *     )
   * )
   *
   * @OA\Response(
   *     response=201,
   *     description="Create a Folder"
   * )
   *
   * @OA\Response(
   *     response=400,
   *     description="Bad request"
   * )
   *
   * @OA\Response(
   *     response=403,
   *     description="Access denied"
   * )
   *
   * @OA\Tag(name="folders")
   *
   * @param Request $request
   * @return View
   * @throws \Exception
   */
  public function postFolderAction(Request $request): View
  {
    $this->denyAccessUnlessGranted(['ROLE_OPERATORE','ROLE_ADMIN']);
    $folder = new Folder();
    $folder->setTenant($this->is->getCurrentInstance());

    $form = $this->createForm('App\Form\FolderType', $folder);
    $this->processForm($request, $form);
    if ($form->isSubmitted() && !$form->isValid()) {
      $errors = FormUtils::getErrorsFromForm($form);
      $data = [
        'type' => 'validation_error',
        'title' => 'There was a validation error',
        'errors' => $errors
      ];
      return $this->view($data, Response::HTTP_BAD_REQUEST);
    }
    $em = $this->getDoctrine()->getManager();

    try {
      $em->persist($folder);
      $em->flush();

    } catch (\Exception $e) {
      $data = [
        'type' => 'error',
        'title' => 'There was an error during save process',
        'description' => 'Contact technical support at support@opencontent.it'
      ];
      $this->logger->error(
        $e->getMessage(),
        ['request' => $request]
      );
      return $this->view($data, Response::HTTP_INTERNAL_SERVER_ERROR);
    }
    return $this->view($folder, Response::HTTP_CREATED);
  }

  /**
   * Edit full Folder
   * @Rest\Put("/{id}", name="folders_api_put")
   *
   * @Security(name="Bearer")
   *
   * @OA\RequestBody(
   *     description="The Folder to edit",
   *     required=true,
   *     @OA\MediaType(
   *         mediaType="application/json",
   *         @OA\Schema(
   *             type="object",
   *             ref=@Model(type=Folder::class, groups={"write"})
   *         )
   *     )
   * )
   *
   * @OA\Response(
   *     response=200,
   *     description="Edit full Folder"
   * )
   *
   * @OA\Response(
   *     response=400,
   *     description="Bad request"
   * )
   *
   * @OA\Response(
   *     response=403,
   *     description="Access denied"
   * )
   *
   * @OA\Response(
   *     response=404,
   *     description="Not found"
   * )
   * @OA\Tag(name="folders")
   *
   * @param $id
   * @param Request $request
   * @return View
   */
  public function putFolderAction($id, Request $request): View
  {
    $repository = $this->getDoctrine()->getRepository('App\Entity\Folder');
    $folder = $repository->find($id);

    if (!$folder) {
      return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
    }

    $this->denyAccessUnlessGranted(FolderVoter::EDIT, $folder);

    $form = $this->createForm('App\Form\FolderType', $folder);
    $this->processForm($request, $form);

    if ($form->isSubmitted() && !$form->isValid()) {
      $errors = FormUtils::getErrorsFromForm($form);
      $data = [
        'type' => 'put_validation_error',
        'title' => 'There was a validation error',
        'errors' => $errors
      ];
      return $this->view($data, Response::HTTP_BAD_REQUEST);
    }

    $em = $this->getDoctrine()->getManager();

    try {
      $em->persist($folder);
      $em->flush();
    } catch (\Exception $e) {

      $data = [
        'type' => 'error',
        'title' => 'There was an error during save process',
        'description' => 'Contact technical support at support@opencontent.it'
      ];
      $this->logger->error(
        $e->getMessage(),
        ['request' => $request]
      );
      return $this->view($data, Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    return $this->view(["Object Modified Successfully"], Response::HTTP_OK);
  }

  /**
   * Patch a Folders
   * @Rest\Patch("/{id}", name="folders_api_patch")
   *
   * @Security(name="Bearer")
   *
   * @OA\RequestBody(
   *     description="The Folder to patch",
   *     required=true,
   *     @OA\MediaType(
   *         mediaType="application/json",
   *         @OA\Schema(
   *             type="object",
   *             ref=@Model(type=Folder::class, groups={"write"})
   *         )
   *     )
   * )
   *
   * @OA\Response(
   *     response=200,
   *     description="Patch a Folder"
   * )
   *
   * @OA\Response(
   *     response=400,
   *     description="Bad request"
   * )
   *
   * @OA\Response(
   *     response=403,
   *     description="Access denied"
   * )
   *
   * @OA\Response(
   *     response=404,
   *     description="Not found"
   * )
   * @OA\Tag(name="folders")
   *
   * @param $id
   * @param Request $request
   * @return View
   * @throws \Exception
   */
  public function patchFolderAction($id, Request $request): View
  {

    $repository = $this->getDoctrine()->getRepository('App\Entity\Folder');
    $folder = $repository->find($id);

    if (!$folder) {
      return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
    }
    $this->denyAccessUnlessGranted(FolderVoter::EDIT, $folder);

    $form = $this->createForm('App\Form\FolderType', $folder);
    $this->processForm($request, $form);

    if ($form->isSubmitted() && !$form->isValid()) {
      $errors = FormUtils::getErrorsFromForm($form);
      $data = [
        'type' => 'validation_error',
        'title' => 'There was a validation error',
        'errors' => $errors
      ];
      return $this->view($data, Response::HTTP_BAD_REQUEST);
    }

    try {
      $em = $this->getDoctrine()->getManager();
      $em->persist($folder);
      $em->flush();
    } catch (\Exception $e) {

      $data = [
        'type' => 'error',
        'title' => 'There was an error during save process',
        'description' => 'Contact technical support at support@opencontent.it'
      ];
      $this->logger->error(
        $e->getMessage(),
        ['request' => $request]
      );
      return $this->view($data, Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    return $this->view(["Object Patched Successfully"], Response::HTTP_OK);
  }

  /**
   * Delete a Folder
   * @Rest\Delete("/{id}", name="folders_api_delete")
   *
   * @Security(name="Bearer")
   *
   * @OA\Response(
   *     response=204,
   *     description="The resource was deleted successfully."
   * )
   *
   * @OA\Response(
   *     response=403,
   *     description="Access denied"
   * )
   *
   * @OA\Tag(name="folders")
   *
   * @Method("DELETE")
   * @param $id
   * @return View
   */
  public function deleteAction($id): View
  {
    $folder = $this->getDoctrine()->getRepository('App\Entity\Folder')->find($id);
    if ($folder) {
      $this->denyAccessUnlessGranted(['ROLE_OPERATORE','ROLE_ADMIN']);
      // debated point: should we 404 on an unknown nickname?
      // or should we just return a nice 204 in all cases?
      // we're doing the latter
      $em = $this->getDoctrine()->getManager();
      $em->remove($folder);
      $em->flush();
    }
    return $this->view(null, Response::HTTP_NO_CONTENT);
  }

  /**
   * @param Request $request
   * @param FormInterface $form
   */
  private function processForm(Request $request, FormInterface $form)
  {
    $data = json_decode($request->getContent(), true);

    $clearMissing = $request->getMethod() != 'PATCH';
    $form->submit($data, $clearMissing);
  }
}
