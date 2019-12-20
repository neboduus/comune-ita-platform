<?php

namespace AppBundle\Controller;


use AppBundle\Entity\Allegato;
use AppBundle\Entity\AuditLog;
use AppBundle\Entity\Ente;
use AppBundle\Entity\Erogatore;
use AppBundle\Entity\OperatoreUser;
use AppBundle\Entity\Pratica;

use AppBundle\Entity\Servizio;
use AppBundle\Form\Base\MessageType;
use AppBundle\Form\Operatore\Base\PraticaOperatoreFlow;
use AppBundle\Logging\LogConstants;
use AppBundle\Services\FormServerApiAdapterService;
use AppBundle\Services\InstanceService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

use Omines\DataTablesBundle\Adapter\ArrayAdapter;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\Column\DateTimeColumn;
use Omines\DataTablesBundle\Controller\DataTablesTrait;
use Omines\DataTablesBundle\Adapter\Doctrine\ORMAdapter;

use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;


/**
 * Class AdmninController
 * @Route("/admin")
 */
class AdminController extends Controller
{
  use DataTablesTrait;

  /**
   * @Route("/", name="admin_index")
   * @Template()
   * @param Request $request
   * @return array
   */
  public function indexAction(Request $request)
  {
    return array(
      'user' => $this->getUser()
    );
  }

  /**
   * @Route("/ente", name="admin_edit_ente")
   * @Template()
   * @param Request $request
   * @return array
   */
  public function editEnteAction(Request $request)
  {
    $entityManager = $this->getDoctrine()->getManager();
    $ente = $this->container->get('ocsdc.instance_service')->getCurrentInstance();
    $form = $this->createForm('AppBundle\Form\Admin\Ente\EnteType', $ente);
    $form->handleRequest($request);
    if ($form->isSubmitted() && $form->isValid()) {
      // $form->getData() holds the submitted values
      // but, the original `$task` variable has also been updated
      $ente = $form->getData();

      // ... perform some action, such as saving the task to the database
      // for example, if Task is a Doctrine entity, save it!

      $entityManager->persist($ente);
      $entityManager->flush();

      return $this->redirectToRoute('admin_edit_ente');
    }

    return array(
      'user' => $this->getUser(),
      'ente' => $ente,
      'form' => $form->createView()
    );
  }


  /**
   * Lists all operatoreUser entities.
   * @Template()
   * @Route("/operatore", name="admin_operatore_index")
   * @Method("GET")
   */
  public function indexOperatoreAction()
  {
    $em = $this->getDoctrine()->getManager();

    $operatoreUsers = $em->getRepository('AppBundle:OperatoreUser')->findAll();

    return array(
      'user' => $this->getUser(),
      'operatoreUsers' => $operatoreUsers,
    );
  }

  /**
   * Creates a new operatoreUser entity.
   * @Template()
   * @Route("/operatore/new", name="admin_operatore_new")
   * @Method({"GET", "POST"})
   */
  public function newOperatoreAction(Request $request)
  {
    $operatoreUser = new Operatoreuser();
    $form = $this->createForm('AppBundle\Form\OperatoreUserType', $operatoreUser);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      $em = $this->getDoctrine()->getManager();
      $ente = $this->container->get('ocsdc.instance_service')->getCurrentInstance();

      $tokenGenerator = $this->get('fos_user.util.token_generator');

      $operatoreUser
        ->setEnte($ente)
        ->setPlainPassword(md5(time()))
        ->setConfirmationToken($tokenGenerator->generateToken())
        ->setPasswordRequestedAt(new \DateTime())
        ->setEnabled(true);
      $em->persist($operatoreUser);
      $em->flush();

      $mailer = $this->get('fos_user.mailer');
      $mailer->sendResettingEmailMessage($operatoreUser);

      return $this->redirectToRoute('admin_operatore_show', array('id' => $operatoreUser->getId()));
    }

    return array(
      'user' => $this->getUser(),
      'operatoreUser' => $operatoreUser,
      'form' => $form->createView(),
    );
  }

  /**
   * Finds and displays a operatoreUser entity.
   * @Template()
   * @Route("/operatore/{id}", name="admin_operatore_show")
   * @Method("GET")
   */
  public function showOperatoreAction(OperatoreUser $operatoreUser)
  {
    $deleteForm = $this->createDeleteForm($operatoreUser);

    return array(
      'user' => $this->getUser(),
      'operatoreUser' => $operatoreUser,
      'delete_form' => $deleteForm->createView(),
    );
  }

  /**
   * Displays a form to edit an existing operatoreUser entity.
   * @Template()
   * @Route("/operatore/{id}/edit", name="admin_operatore_edit")
   * @Method({"GET", "POST"})
   */
  public function editOperatoreAction(Request $request, OperatoreUser $operatoreUser)
  {
    $deleteForm = $this->createDeleteForm($operatoreUser);
    $editForm = $this->createForm('AppBundle\Form\OperatoreUserType', $operatoreUser);
    $editForm->handleRequest($request);

    if ($editForm->isSubmitted() && $editForm->isValid()) {
      $this->getDoctrine()->getManager()->flush();

      return $this->redirectToRoute('admin_operatore_edit', array('id' => $operatoreUser->getId()));
    }

    return array(
      'user' => $this->getUser(),
      'operatoreUser' => $operatoreUser,
      'edit_form' => $editForm->createView(),
      'delete_form' => $deleteForm->createView(),
    );
  }

  /**
   * Send password reset hash to user.
   * @Template()
   * @Route("/operatore/{id}/resetpassword", name="admin_operatore_reset_password")
   * @Method({"GET", "POST"})
   */
  public function resetPasswordOperatoreAction(Request $request, OperatoreUser $operatoreUser)
  {
    $em = $this->getDoctrine()->getManager();
    $tokenGenerator = $this->get('fos_user.util.token_generator');
    $operatoreUser
      ->setConfirmationToken($tokenGenerator->generateToken())
      ->setPasswordRequestedAt(new \DateTime());
    $em->persist($operatoreUser);
    $em->flush();

    $mailer = $this->get('fos_user.mailer');
    $mailer->sendResettingEmailMessage($operatoreUser);

    return $this->redirectToRoute('admin_operatore_edit', array('id' => $operatoreUser->getId()));
  }

  /**
   * Deletes a operatoreUser entity.
   * @Template()
   * @Route("/operatore/{id}", name="admin_operatore_delete")
   * @Method("DELETE")
   */
  public function deleteAction(Request $request, OperatoreUser $operatoreUser)
  {
    $form = $this->createDeleteForm($operatoreUser);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      $em = $this->getDoctrine()->getManager();
      $em->remove($operatoreUser);
      $em->flush();
    }

    return $this->redirectToRoute('admin_operatore_index');
  }

  /**
   * Creates a form to delete a operatoreUser entity.
   *
   * @param OperatoreUser $operatoreUser The operatoreUser entity
   *
   * @return \Symfony\Component\Form\Form The form
   */
  private function createDeleteForm(OperatoreUser $operatoreUser)
  {
    return $this->createFormBuilder()
      ->setAction($this->generateUrl('admin_operatore_delete', array('id' => $operatoreUser->getId())))
      ->setMethod('DELETE')
      ->getForm();
  }


  /**
   * Lists all operatoreLogs entities.
   * @Template()
   * @Route("/logs", name="admin_logs_index")
   * @Method({"GET", "POST"})
   */
  public function indexLogsAction(Request $request)
  {
    $table = $this->createDataTable()
      ->add('type', TextColumn::class, ['label' => 'Evento'])
      ->add('eventTime', DateTimeColumn::class, ['label' => 'Data', 'format' => 'd-m-Y H:i'])
      ->add('user', TextColumn::class, ['label' => 'Utente'])
      ->add('ip', TextColumn::class, ['label' => 'Ip'])
      ->createAdapter(ORMAdapter::class, [
        'entity' => AuditLog::class,
      ])
      ->handleRequest($request);

    if ($table->isCallback()) {
      return $table->getResponse();
    }

    return array(
      'user' => $this->getUser(),
      'datatable' => $table
    );
  }


  /**
   * Lists all operatoreUser entities.
   * @Template()
   * @Route("/servizio", name="admin_servizio_index")
   * @Method("GET")
   */
  public function indexServizioAction()
  {
    $statuses = [
      Servizio::STATUS_CANCELLED => 'Bozza',
      Servizio::STATUS_AVAILABLE => 'Pubblicato',
      Servizio::STATUS_SUSPENDED => 'Sospeso'
    ];

    $em = $this->getDoctrine()->getManager();
    $items = $em->getRepository('AppBundle:Servizio')->findAll();

    return array(
      'user' => $this->getUser(),
      'items' => $items,
      'statuses' => $statuses
    );
  }

  /**
   * Lists all operatoreUser entities.
   * @Route("/servizio/list", name="admin_servizio_list")
   * @Method("GET")
   */
  public function listServizioAction()
  {

    $em = $this->getDoctrine()->getManager();
    $items = $em->getRepository('AppBundle:Servizio')->findBy(['praticaFCQN' => '\AppBundle\Entity\FormIO']);

    $data = [];
    foreach ($items as $s) {
      $data []= [
        'id' => $s->getId(),
        'title' => $s->getName(),
        'description' => $s->getDescription()
      ];
    }

    return new JsonResponse($data);
  }

  /**
   * @Route("/servizio/{servizio}/edit", name="admin_servizio_edit")
   * @ParamConverter("servizio", class="AppBundle:Servizio")
   * @Template()
   * @param Servizio $servizio
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function editServizioAction(Servizio $servizio)
  {
    $user = $this->getUser();
    $flowService = $this->get('ocsdc.form.flow.service');
    $flowService->setInstanceKey($user->getId());
    $flowService->bind($servizio);

    $form = $flowService->createForm();
    if ($flowService->isValid($form)) {

      $flowService->saveCurrentStepData($form);

      if ($flowService->nextStep()) {
        $this->getDoctrine()->getManager()->flush();
        $form = $flowService->createForm();
      } else {

        // Retrocompatibilità --> salvo i parametri dei protocollo nell'ente
        $ente = $servizio->getEnte();
        $ente->setProtocolloParametersPerServizio($servizio->getProtocolloParameters(), $servizio);

        $this->getDoctrine()->getManager()->flush();
        $flowService->getDataManager()->drop($flowService);
        $flowService->reset();

        $this->addFlash('feedback', 'Servizio modificato correttamente');

        return $this->redirectToRoute('admin_servizio_index', ['servizio' => $servizio]);
      }
    }

    return [
      'form' => $form->createView(),
      'servizio' => $flowService->getFormData(),
      'flow' => $flowService,
      'formserver_url' => $this->getParameter('formserver_public_url'),
      'user' => $user
    ];
  }

  /**
   * Creates a new Service entity.
   * @Route("/servizio/new", name="admin_service_new")
   * @Method({"GET", "POST"})
   */
  public function newServiceAction(Request $request)
  {

    $servizio = new Servizio();
    $ente = $this->container->get('ocsdc.instance_service')->getCurrentInstance();

    $servizio->setName('Nuovo Servizio ' . time());
    $servizio->setPraticaFCQN('\AppBundle\Entity\FormIO');
    $servizio->setPraticaFlowServiceName('ocsdc.form.flow.formio');
    $servizio->setEnte($ente);
    $servizio->setStatus(Servizio::STATUS_CANCELLED);

    // Erogatore
    $erogatore = new Erogatore();
    $erogatore->setName('Erogatore di ' . $servizio->getName() . ' per ' . $ente->getName());
    $erogatore->addEnte($ente);
    $this->getDoctrine()->getManager()->persist($erogatore);
    $servizio->activateForErogatore($erogatore);

    $this->getDoctrine()->getManager()->persist($servizio);
    $this->getDoctrine()->getManager()->flush();

    $user = $this->getUser();
    $flowService = $this->get('ocsdc.form.flow.service');

    $flowService->setInstanceKey($user->getId());

    $flowService->bind($servizio);

    $form = $flowService->createForm();
    if ($flowService->isValid($form)) {

      $flowService->saveCurrentStepData($form);
      //$servizio->setLastCompiledStep($flowService->getCurrentStepNumber());

      if ($flowService->nextStep()) {
        $this->getDoctrine()->getManager()->flush();
        $form = $flowService->createForm();
      } else {

        // Retrocompatibilità --> salvo i parametri dei protocollo nell'ente
        $ente = $servizio->getEnte();
        $ente->setProtocolloParametersPerServizio($servizio->getProtocolloParameters(), $servizio);
        $this->getDoctrine()->getManager()->flush();
        $flowService->getDataManager()->drop($flowService);
        $flowService->reset();

        $this->addFlash('feedback', 'Servizio creato correttamente');

        return $this->redirectToRoute('admin_servizio_index', ['servizio' => $servizio]);
      }
    }/* else {
      dump($form->getErrors());
      exit;
    }*/

    return $this->render('@App/Admin/editServizio.html.twig', [
      'form' => $form->createView(),
      'servizio' => $flowService->getFormData(),
      'flow' => $flowService,
      'formserver_url' => $this->getParameter('formserver_public_url'),
      'user' => $user
    ]);

  }

  /**
   * Deletes a service entity.
   * @Route("/servizio/{id}/delete", name="admin_servizio_delete")
   * @Method("GET")
   */
  public function deleteServiceAction(Request $request, Servizio $servizio)
  {

    try {
      if ($servizio->getPraticaFCQN() == '\AppBundle\Entity\FormIO') {
        $this->container->get('ocsdc.formserver')->deleteForm($servizio);
      }

      $em = $this->getDoctrine()->getManager();
      $em->remove($servizio);
      $em->flush();

      $this->addFlash('feedback', 'Servizio eliminato correttamente');

      return $this->redirectToRoute('admin_servizio_index');
    } catch (ForeignKeyConstraintViolationException $exception) {
      $this->addFlash('warning', 'Impossibile eliminare il servizio, ci sono delle pratiche collegate.');
      return $this->redirectToRoute('admin_servizio_index');
    }


  }

}


