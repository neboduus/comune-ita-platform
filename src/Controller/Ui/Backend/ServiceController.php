<?php

namespace App\Controller\Ui\Backend;

use App\DataTable\ScheduledActionTableType;
use App\DataTable\ServiceTableType;
use App\Entity\OperatoreUser;
use App\Entity\Servizio;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Omines\DataTablesBundle\Controller\DataTablesTrait;
use Omines\DataTablesBundle\DataTableFactory;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;


class ServiceController extends Controller
{
  use DataTablesTrait;

  /**
   * @var DataTableFactory
   */
  private $dataTableFactory;
  /**
   * @var TranslatorInterface
   */
  private $translator;
  /**
   * @var EntityManagerInterface
   */
  private $entityManager;

  /**
   * @param DataTableFactory $dataTableFactory
   * @param EntityManagerInterface $entityManager
   * @param TranslatorInterface $translator
   */
  public function __construct(DataTableFactory $dataTableFactory, EntityManagerInterface $entityManager, TranslatorInterface $translator)
  {
    $this->dataTableFactory = $dataTableFactory;
    $this->translator = $translator;
    $this->entityManager = $entityManager;
  }


  /**
   * Lists all services entities.
   * @Route("/operatori/services", name="backend_services_index")
   * @Method({"GET", "POST"})
   */
  public function indexServicesAction(Request $request)
  {

    $user = $this->getUser();

    $statuses = [
      Servizio::STATUS_CANCELLED => $this->translator->trans('servizio.statutes.bozza'),
      Servizio::STATUS_AVAILABLE => $this->translator->trans('servizio.statutes.pubblicato'),
      Servizio::STATUS_SUSPENDED => $this->translator->trans('servizio.statutes.sospeso'),
      Servizio::STATUS_PRIVATE => $this->translator->trans('servizio.statutes.privato'),
      Servizio::STATUS_SCHEDULED => $this->translator->trans('servizio.statutes.schedulato'),
    ];

    $repo = $this->entityManager->getRepository(Servizio::class);
    /** @var QueryBuilder $query */
    $qb = $repo->createQueryBuilder('servizio');

    if ($user instanceof OperatoreUser) {
      $qb
        ->andWhere('servizio IN (:allowedServices)')
        ->setParameter('allowedServices', $user->getServiziAbilitati())
        ->andWhere('servizio.status IN (:availableStatuses) OR (servizio.status = :statusScheduled AND servizio.scheduledFrom <= :now AND servizio.scheduledTo >= :now)')
        ->setParameter('availableStatuses', array_values([Servizio::STATUS_AVAILABLE, Servizio::STATUS_PRIVATE]))
        ->setParameter('statusScheduled', Servizio::STATUS_SCHEDULED)
        ->setParameter('now', new \DateTime())
        ->andWhere('(servizio.paymentRequired IS NULL OR servizio.paymentRequired NOT IN (:immediate_payment))')
        ->setParameter('immediate_payment', Servizio::PAYMENT_REQUIRED)
      ;
    }

    $items = $qb->getQuery()->getResult();


    return $this->render('Operatori/indexServices.html.twig', [
      'user' => $user,
      'items' => $items,
      'statuses' => $statuses
    ]);

  }


  /**
   * Lists all services entities.
   * @Route("/operatori/services-ajx", name="backend_services_index_ajx")
   * @Method({"GET", "POST"})
   */
  public function indexServicesAjxAction(Request $request)
  {
    /** @var User $user */
    $user = $this->getUser();

    $table = $this->dataTableFactory->createFromType(ServiceTableType::class, [
      'user' => $user
    ])
      ->handleRequest($request);

    if ($table->isCallback()) {
      return $table->getResponse();
    }

    return $this->render('Operatori/indexServicesAjx.html.twig', [
      'user' => $this->getUser(),
      'datatable' => $table
    ]);

  }



}
