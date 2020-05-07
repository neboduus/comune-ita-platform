<?php

namespace App\Controller\Rest;

use App\Entity\AdminUser;
use App\Entity\CPSUser;
use App\Entity\Document;
use App\Entity\OperatoreUser;
use App\Multitenancy\TenantAwareFOSRestController;
use App\Services\InstanceService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\FormInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use App\Multitenancy\Annotations\MustHaveTenant;

/**
 * Class DocumentsAPIController
 * @property EntityManager em
 * @property InstanceService is
 * @package App\Controller
 * @Route("/documents")
 * @MustHaveTenant()
 */
class DocumentsAPIController extends TenantAwareFOSRestController
{
    const CURRENT_API_VERSION = '1.0';

    private $em;

    private $is;

    private $rootDir;

    private $translator;

    public function __construct(TranslatorInterface $translator, $rootDir, EntityManagerInterface $em, InstanceService $is)
    {
        $this->translator = $translator;
        $this->rootDir = $rootDir; //@todo a che serve?
        $this->em = $em;
        $this->is = $is;
    }

    /**
     * List all Documents
     * @Rest\Get("", name="documents_api_list")
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
     *     name="cf",
     *     in="query",
     *     type="string",
     *     description="Fiscal code of the document's owner"
     * )
     * @SWG\Parameter(
     *     name="title",
     *     in="query",
     *     type="string",
     *     description="Document's title"
     * )
     * @SWG\Parameter(
     *     name="folder-title",
     *     in="query",
     *     type="string",
     *     description="Document's folder title"
     * )
     * @SWG\Parameter(
     *     name="folder",
     *     in="query",
     *     type="string",
     *     description="Document's folder id"
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="Retrieve list of documents",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=Document::class))
     *     )
     * )
     * @SWG\Tag(name="documents")
     * @param Request $request
     * @return View
     */
    public function getDocuments(Request $request)
    {
        $cf = $request->query->get('cf');
        $title = $request->query->get('title');
        $folderTitle = $request->query->get('folder-title');
        $folder = $request->query->get('folder');

        $qb = $this->em->createQueryBuilder()
            ->select('document')
            ->from('App:Document', 'document')
            ->leftJoin('document.folder', 'folder')
            ->leftJoin('document.owner', 'owner');

        if (isset($cf)) {
            $qb->andWhere('lower(owner.codiceFiscale) = :cf')
                ->setParameter('cf', strtolower($cf));
        }

        if (isset($title)) {
            $qb->andWhere('lower(document.title) = :title')
                ->setParameter('title', strtolower($title));
        }

        if (isset($folderTitle)) {
            $qb->andWhere('lower(folder.title) = :folder-title')
                ->setParameter('folder-title', strtolower($folderTitle));
        }
        if (isset($folder)) {
            $qb->andWhere('folder.id = :folder')
                ->setParameter('folder', $folder);
        }
        $documents = $qb
            ->getQuery()
            ->getResult();

        return $this->view($documents, Response::HTTP_OK);
    }

    /**
     * Retreive a Document
     * @Rest\Get("/{id}", name="document_api_get")
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
     *     description="Retreive a Document",
     *     @Model(type=Document::class)
     * )
     *
     * @SWG\Response(
     *     response=404,
     *     description="Document not found"
     * )
     * @SWG\Tag(name="documents")
     *
     * @param $id
     * @return View
     */
    public function getDocument($id)
    {
        try {
            $repository = $this->getDoctrine()->getRepository('App:Document');
            $document = $repository->find($id);
            if ($document === null) {
                return $this->view("Object not found", Response::HTTP_NOT_FOUND);
            }

            return $this->view($document, Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->view("Object not found", Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * Create a Document
     * @Rest\Post(name="documents_api_post")
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
     *     name="Documents",
     *     in="body",
     *     type="json",
     *     description="The Document to create",
     *     required=true,
     *     @SWG\Schema(
     *         type="object",
     *         ref=@Model(type=Document::class),
     *         additionalProperties=true
     *     )
     * )
     *
     * @SWG\Response(
     *     response=201,
     *     description="Create a Document"
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="Bad request"
     * )
     * @SWG\Tag(name="documents")
     *
     * @param Request $request
     * @return View
     * @throws \Exception
     */
    public function postDocument(Request $request)
    {
        $document = new Document();
        $document->setTenant($this->is->getCurrentInstance());
        $document->setDownloadLink($this->generateUrl('document_download', ['id' => $document->getId()], UrlGeneratorInterface::ABSOLUTE_URL));

        $user = $this->getUser();

        if ($user instanceof AdminUser || $user instanceof OperatoreUser) {
            $document->setRecipientType(Document::RECIPIENT_TENANT);
        } elseif ($user instanceof CPSUser) {
            $document->setRecipientType(Document::RECIPIENT_USER);
        }


        $form = $this->createForm('App\Form\DocumentAPIType', $document);
        $this->processForm($request, $form);
        if (!$form->isValid()) {
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
            $em->persist($document);
            $em->flush();
        } catch (\Exception $e) {
            $data = [
                'type' => 'error',
                'title' => 'There was an error during save process',
                'description' => $e->getMessage()
            ];
            $this->get('logger')->error(
                $e->getMessage(),
                ['request' => $request]
            );
            return $this->view($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        return $this->view($document, Response::HTTP_CREATED);
    }

    /**
     * Edit full Document
     * @Rest\Put("/{id}", name="documents_api_put")
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
     *     name="Document",
     *     in="body",
     *     type="json",
     *     description="The Document to edit",
     *     required=true,
     *     @SWG\Schema(
     *         type="object",
     *         ref=@Model(type=Document::class)
     *     )
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="Edit full Document"
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
     * @SWG\Tag(name="documents")
     *
     * @param $id
     * @param Request $request
     * @return View
     */
    public function putDocument($id, Request $request)
    {
        $document = $this->em->getRepository('App:Document')->find($id);

        if (!$document) {
            return $this->view("Object not found", Response::HTTP_NOT_FOUND);
        }

        $form = $this->createForm('App\Form\DocumentAPIType', $document);
        $this->processForm($request, $form);

        if (!$form->isValid()) {
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
            $em->persist($document);
            $em->flush();
        } catch (\Exception $e) {
            $data = [
                'type' => 'error',
                'title' => $e->getMessage()
            ];
            $this->get('logger')->error(
                $e->getMessage(),
                ['request' => $request]
            );
            return $this->view($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->view("Object Modified Successfully", Response::HTTP_OK);
    }

    /**
     * Patch a Document
     * @Rest\Patch("/{id}", name="documents_api_patch")
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
     *     name="Document",
     *     in="body",
     *     type="json",
     *     description="The Document to patch",
     *     required=true,
     *     @SWG\Schema(
     *         type="object",
     *         ref=@Model(type=Document::class)
     *     )
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="Patch a Document"
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
     * @SWG\Tag(name="documents")
     *
     * @param $id
     * @param Request $request
     * @return View
     */
    public function patchDocument($id, Request $request)
    {
        $document = $this->em->getRepository('App:Document')->find($id);

        if (!$document) {
            return $this->view("Object not found", Response::HTTP_NOT_FOUND);
        }
        $form = $this->createForm('App\Form\DocumentAPIType', $document);
        $this->processForm($request, $form);

        if (!$form->isValid()) {
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
            $em->persist($document);
            $em->flush();
        } catch (\Exception $e) {
            $data = [
                'type' => 'error',
                'title' => 'There was an error during save process'
            ];
            $this->get('logger')->error(
                $e->getMessage(),
                ['request' => $request]
            );
            return $this->view($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->view("Object Patched Successfully", Response::HTTP_OK);
    }

    /**
     * Delete a Document
     * @Rest\Delete("/{id}", name="document_api_delete", methods={"DELETE"})
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
     * @SWG\Tag(name="documents")
     *
     * @param $id
     * @return View
     */
    public function deleteDocument($id)
    {
        $document = $this->getDoctrine()->getRepository('App:Document')->find($id);
        if ($document) {
            // debated point: should we 404 on an unknown nickname?
            // or should we just return a nice 204 in all cases?
            // we're doing the latter
            $em = $this->getDoctrine()->getManager();
            $em->remove($document);
            $em->flush();
        }
        return $this->view(null, Response::HTTP_NO_CONTENT);
    }


    /**
     * Download a document
     * @Rest\Get("/{id}/download", name="document_download")
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
     *     description="Download a document",
     * )
     *
     * @SWG\Response(
     *     response=404,
     *     description="Document not found"
     * )
     * @SWG\Tag(name="documents")
     *
     * @param $id
     * @return View|Response
     */
    public function downloadDocument($id)
    {
        $document = $this->em->getRepository('App:Document')->find($id);

        if (!$document) {
            return $this->view("Object not found", Response::HTTP_NOT_FOUND);
        }

        $extension = explode('.', $document->getOriginalFilename());
        $extension = end($extension);

        $fileName = '../var/uploads/documents/users/' .
            $document->getOwnerId() . DIRECTORY_SEPARATOR . $document->getFolderId() .
            DIRECTORY_SEPARATOR . $document->getId() . '.' . $extension;

        if (!file_exists($fileName) && $document->getAddress()) {
            $fileName = $document->getAddress();
        }

        try {
            $fileContent = file_get_contents($fileName);
        } catch (\Exception $exception) {
            return $this->view("File non trovato", Response::HTTP_BAD_REQUEST);
        }

        // Provide a name for your file with extension
        $filename = $document->getOriginalFilename();
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
