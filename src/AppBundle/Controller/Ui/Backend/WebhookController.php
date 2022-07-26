<?php

namespace AppBundle\Controller\Ui\Backend;

use AppBundle\Entity\Pratica;
use AppBundle\Entity\ServiceGroup;
use AppBundle\Entity\Webhook;
use AppBundle\Services\InstanceService;
use AppBundle\Services\WebhookService;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class WebhookController
 * @Route("/admin/webhook")
 */
class WebhookController extends Controller
{
  /**
   * @var EntityManagerInterface
   */
  private $entityManager;
  /**
   * @var InstanceService
   */
  private $instanceService;
  /**
   * @var WebhookService
   */
  private $webhookService;
  /**
   * @var TranslatorInterface
   */
  private $translator;

  /**
   * @param EntityManagerInterface $entityManager
   * @param InstanceService $instanceService
   * @param WebhookService $webhookService
   * @param TranslatorInterface $translator
   */
  public function __construct(EntityManagerInterface $entityManager, InstanceService $instanceService, WebhookService $webhookService, TranslatorInterface $translator)
  {
    $this->entityManager = $entityManager;
    $this->instanceService = $instanceService;
    $this->webhookService = $webhookService;
    $this->translator = $translator;
  }


  /**
   * @Route("/", name="admin_webhook_index")
   * @Method("GET")
   */
  public function indexWebhooksAction()
  {

    $servizi = $this->instanceService->getServices();
    $services = [];
    $services ['all'] = $this->translator->trans('tutti');
    foreach ($servizi as $s) {
      $services[$s->getId()] = $s->getName();
    }

    $items = $this->entityManager->getRepository('AppBundle:Webhook')->findAll();

    return $this->render( '@App/Admin/indexWebhook.html.twig', [
      'user'  => $this->getUser(),
      'statuses' => Webhook::TRIGGERS,
      'services' => $services,
      'items' => $items
    ]);
  }

  /**
   * @Route("/new", name="admin_webhook_new")
   * @Method({"GET", "POST"})
   * @param Request $request
   * @return RedirectResponse|Response|null
   */
  public function newWebhookAction(Request $request)
  {
    $webhook = new Webhook();
    $form = $this->createForm('AppBundle\Form\Admin\Ente\WebhookType', $webhook);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      $webhook->setEnte($this->instanceService->getCurrentInstance());
      $this->entityManager->persist($webhook);
      $this->entityManager->flush();

      $this->addFlash('feedback', $this->translator->trans('operatori.create_webhook_success'));
      return $this->redirectToRoute('admin_webhook_index');
    }

    return $this->render( '@App/Admin/editWebhook.html.twig', [
      'user'  => $this->getUser(),
      'item' => $webhook,
      'form' => $form->createView(),
    ]);
  }

  /**
   * @Route("/{id}/edit", name="admin_webhook_edit")
   * @Method({"GET", "POST"})
   * @param Request $request
   * @param Webhook $webhook
   * @return RedirectResponse|Response|null
   */
  public function editWebhookAction(Request $request, Webhook $webhook)
  {
    $form = $this->createForm('AppBundle\Form\Admin\Ente\WebhookType', $webhook);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      $this->entityManager->flush();
      try {
        $this->testWebhook($request, $webhook);
      } catch (\Exception $e) {
        $error = new FormError($e->getMessage());
        $form->addError($error);
      }

      //return $this->redirectToRoute('admin_webhook_edit', array('id' => $webhook->getId()));
    }

    $templateVariables = [
      'user'  => $this->getUser(),
      'item' => $webhook,
      'form' => $form->createView(),
      'test' => true
    ];

    if ($request->request->has('application_id')) {
      $templateVariables ['application_id'] = $request->request->get('application_id');
    }

    return $this->render( '@App/Admin/editWebhook.html.twig', $templateVariables);
  }

  /**
   * @Route("/{id}/delete", name="admin_webhook_delete")
   * @Method({"GET", "POST", "DELETE"})
   */
  public function deleteServiceGroupAction(Request $request, Webhook $webhook)
  {
    try {
      $em = $this->getDoctrine()->getManager();
      $em->remove($webhook);
      $em->flush();
      $this->addFlash('feedback', $this->translator->trans('operatori.delete_webhook_success'));
      return $this->redirectToRoute('admin_webhook_index');

    } catch (ForeignKeyConstraintViolationException $exception) {
      $this->addFlash('warning', $this->translator->trans('operatori.delete_webhook_error'));
      return $this->redirectToRoute('admin_service_group_index');
    }
  }


  private function testWebhook(Request $request, Webhook $webhook)
  {

    if ($request->request->has('test')) {
      try {
        $applicationRepo = $this->entityManager->getRepository('AppBundle:Pratica');
        $application = $applicationRepo->find($request->request->get('application_id'));
        if ($application instanceof Pratica) {
          $this->webhookService->applicationWebhook(
            [
              'pratica' => $application->getId(),
              'webhook' => $webhook->getId()
            ]
          );
        }
      } catch (DriverException $e) {
        throw new \Exception('Id pratica non corretto o pratica non presente');
      } catch (\Exception $e) {
        throw new \Exception($e->getMessage());
      }
    }
  }
}
