<?php

namespace App\Controller\Rest;

use App\Entity\Folder;
use App\Services\InstanceService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Symfony\Component\Form\FormInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
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
   * @SWG\Parameter(
   *     name="Authorization",
   *     in="header",
   *     description="The authentication Bearer",
   *     required=true,
   *     type="string"
   * )
   * * @SWG\Parameter(
   *     name="cf",
   *     in="query",
   *     type="string",
   *     description="Fiscal code of the folder's owner"
   * )
   * * @SWG\Parameter(
   *     name="title",
   *     in="query",
   *     type="string",
   *     description="Folder's title"
   * )
   *
   * @SWG\Response(
   *     response=200,
   *     description="Retrieve list of Folders",
   *     @SWG\Schema(
   *         type="array",
   *         @SWG\Items(ref=@Model(type=Folder::class))
   *     )
   * )
   * @SWG\Tag(name="folders")
   * @param Request $request
   * @return View
   */
  public function getFoldersAction(Request $request)
  {
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
   * Retreive a Folder
   * @Rest\Get("/{id}", name="folder_api_get")
   *
   * @SWG\Parameter(
   *     name="Authorization",
   *     in="header",
   *     description="The authentication Bearer",
   *     required=true,
   *     type="string"
   * )
   *
   * @SWG\Response(
   *     response=200,
   *     description="Retreive a Folder",
   *     @Model(type=Folder::class)
   * )
   *
   * @SWG\Response(
   *     response=404,
   *     description="Folder not found"
   * )
   * @SWG\Tag(name="folders")
   *
   * @param $id
   * @return View
   */
  public function getFolderAction($id)
  {
    try {
      $repository = $this->getDoctrine()->getRepository('App:Folder');
      $folder = $repository->find($id);
      if ($folder === null) {
        return $this->view("Object not found", Response::HTTP_NOT_FOUND);
      }

      return $this->view($folder, Response::HTTP_OK);
    } catch (\Exception $e) {
      return $this->view("Object not found", Response::HTTP_NOT_FOUND);
    }
  }

  /**
   * Create a Folder
   * @Rest\Post(name="folders_api_post")
   *
   * @SWG\Parameter(
   *     name="Authorization",
   *     in="header",
   *     description="The authentication Bearer",
   *     required=true,
   *     type="string"
   * )
   *
   * @SWG\Parameter(
   *     name="Folders",
   *     in="body",
   *     type="json",
   *     description="The Folder to create",
   *     required=true,
   *     @SWG\Schema(
   *         type="object",
   *         ref=@Model(type=Folder::class)
   *     )
   * )
   *
   * @SWG\Response(
   *     response=201,
   *     description="Create a Folder"
   * )
   *
   * @SWG\Response(
   *     response=400,
   *     description="Bad request"
   * )
   * @SWG\Tag(name="folders")
   *
   * @param Request $request
   * @return View
   * @throws \Exception
   */
  public function postFolderAction(Request $request)
  {
    $folder = new Folder();
    $folder->setTenant($this->is->getCurrentInstance());

    $form = $this->createForm('App\Form\FolderType', $folder);
    $this->processForm($request, $form);
    if ($form->isSubmitted() && !$form->isValid()) {
      $errors = $this->getErrorsFromForm($form);
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
        'description' => $e->getMessage()
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
   * @SWG\Parameter(
   *     name="Authorization",
   *     in="header",
   *     description="The authentication Bearer",
   *     required=true,
   *     type="string"
   * )
   *
   * @SWG\Parameter(
   *     name="Folder",
   *     in="body",
   *     type="json",
   *     description="The Folder to edit",
   *     required=true,
   *     @SWG\Schema(
   *         type="object",
   *         ref=@Model(type=Folder::class)
   *     )
   * )
   *
   * @SWG\Response(
   *     response=200,
   *     description="Edit full Folder"
   * )
   *
   * @SWG\Response(
   *     response=400,
   *     description="Bad request"
   * )
   *
   * @SWG\Response(
   *     response=404,
   *     description="Not found"
   * )
   * @SWG\Tag(name="folders")
   *
   * @param $id
   * @param Request $request
   * @return View
   */
  public function putFolderAction($id, Request $request)
  {
    $repository = $this->getDoctrine()->getRepository('App:Folder');
    $folder = $repository->find($id);

    if (!$folder) {
      return $this->view("Object not found", Response::HTTP_NOT_FOUND);
    }

    $form = $this->createForm('App\Form\FolderType', $folder);
    $this->processForm($request, $form);

    if ($form->isSubmitted() && !$form->isValid()) {
      $errors = $this->getErrorsFromForm($form);
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
        'title' => $e->getMessage()
      ];
      $this->logger->error(
        $e->getMessage(),
        ['request' => $request]
      );
      return $this->view($data, Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    return $this->view("Object Modified Successfully", Response::HTTP_OK);
  }

  /**
   * Patch a Folders
   * @Rest\Patch("/{id}", name="folders_api_patch")
   *
   * @SWG\Parameter(
   *     name="Authorization",
   *     in="header",
   *     description="The authentication Bearer",
   *     required=true,
   *     type="string"
   * )
   *
   * @SWG\Parameter(
   *     name="Folder",
   *     in="body",
   *     type="json",
   *     description="The Folder to patch",
   *     required=true,
   *     @SWG\Schema(
   *         type="object",
   *         ref=@Model(type=Folder::class)
   *     )
   * )
   *
   * @SWG\Response(
   *     response=200,
   *     description="Patch a Folder"
   * )
   *
   * @SWG\Response(
   *     response=400,
   *     description="Bad request"
   * )
   *
   * @SWG\Response(
   *     response=404,
   *     description="Not found"
   * )
   * @SWG\Tag(name="folders")
   *
   * @param $id
   * @param Request $request
   * @return View
   * @throws \Exception
   */
  public function patchFolderAction($id, Request $request)
  {

    $repository = $this->getDoctrine()->getRepository('App:Folder');
    $folder = $repository->find($id);

    if (!$folder) {
      return $this->view("Object not found", Response::HTTP_NOT_FOUND);
    }
    $form = $this->createForm('App\Form\FolderType', $folder);
    $this->processForm($request, $form);

    if ($form->isSubmitted() && !$form->isValid()) {
      $errors = $this->getErrorsFromForm($form);
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
        'title' => 'There was an error during save process'
      ];
      $this->logger->error(
        $e->getMessage(),
        ['request' => $request]
      );
      return $this->view($data, Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    return $this->view("Object Patched Successfully", Response::HTTP_OK);
  }

  /**
   * Delete a Folder
   * @Rest\Delete("/{id}", name="folders_api_delete")
   *
   * @SWG\Parameter(
   *     name="Authorization",
   *     in="header",
   *     description="The authentication Bearer",
   *     required=true,
   *     type="string"
   * )
   *
   * @SWG\Response(
   *     response=204,
   *     description="The resource was deleted successfully."
   * )
   * @SWG\Tag(name="folders")
   *
   * @param $id
   * @return View
   */
  public function deleteAction($id)
  {
    $folder = $this->getDoctrine()->getRepository('App:Folder')->find($id);
    if ($folder) {
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

  /**
   * @param FormInterface $form
   * @return array
   */
  private function getErrorsFromForm(FormInterface $form)
  {
    $errors = array();
    foreach ($form->getErrors() as $error) {
      $errors[] = $error->getMessage();
    }
    foreach ($form->all() as $childForm) {
      if ($childForm instanceof FormInterface) {
        if ($childErrors = $this->getErrorsFromForm($childForm)) {
          $errors[] = $childErrors;
        }
      }
    }
    return $errors;
  }
}
