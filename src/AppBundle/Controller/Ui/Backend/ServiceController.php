<?php

namespace AppBundle\Controller\Ui\Backend;

use App\Entity\User;
use AppBundle\DataTable\ScheduledActionTableType;
use AppBundle\DataTable\ServiceTableType;
use Omines\DataTablesBundle\Controller\DataTablesTrait;
use Omines\DataTablesBundle\DataTableFactory;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;


class ServiceController extends Controller
{
  use DataTablesTrait;

  /**
   * @var DataTableFactory
   */
  private $dataTableFactory;

  /**
   * @param DataTableFactory $dataTableFactory
   */
  public function __construct(DataTableFactory $dataTableFactory)
  {
    $this->dataTableFactory = $dataTableFactory;
  }


  /**
   * Lists all services entities.
   * @Route("/operatori/services", name="backend_services_index")
   * @Method({"GET", "POST"})
   */
  public function indexServicesAction(Request $request)
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

    return $this->render('@App/Operatori/indexServices.html.twig', [
      'user' => $this->getUser(),
      'datatable' => $table
    ]);

  }



}
