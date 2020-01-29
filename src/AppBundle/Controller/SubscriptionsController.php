<?php


namespace AppBundle\Controller;

use AppBundle\BackOffice\SubcriptionsBackOffice;
use AppBundle\Entity\Subscription;
use AppBundle\Entity\SubscriptionService;

use Doctrine\ORM\QueryBuilder;
use Omines\DataTablesBundle\Adapter\Doctrine\ORMAdapter;
use Omines\DataTablesBundle\Column\DateTimeColumn;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\Controller\DataTablesTrait;
use stdClass;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;


/**
 * Class SubscriptionsController
 */
class SubscriptionsController extends Controller
{
  use DataTablesTrait;
  private $subscriptionsBackOffice;

  public function __construct(SubcriptionsBackOffice $subscriptionsBackOffice)
  {
    $this->subscriptionsBackOffice = $subscriptionsBackOffice;
  }

  /**
   * Lists all subscriptions entities.
   * @Template()
   * @Route("/operatori/subscriptions/{subscriptionService}", name="operatori_subscriptions")
   */
  public function showSubscriptionsAction(Request $request, SubscriptionService $subscriptionService)
  {

    $table = $this->createDataTable()
      ->add('name', TextColumn::class, ['label' => 'name', 'field' => 'subscriber.name', 'searchable' => true])
      ->add('surname', TextColumn::class, ['label' => 'surname', 'field' => 'subscriber.surname', 'searchable' => true])
      ->add('fiscal_code', TextColumn::class, ['label' => 'fiscal_code', 'field' => 'subscriber.fiscal_code', 'searchable' => true])
      ->add('email', TextColumn::class, ['label' => 'email', 'field' => 'subscriber.email', 'searchable' => true])
      ->add('created_at', DateTimeColumn::class, ['label' => 'created_at', 'format' => 'd/m/Y', 'searchable' => false])
      ->createAdapter(ORMAdapter::class, [
        'entity' => Subscription::class,
        'query' => function (QueryBuilder $builder) use ($subscriptionService) {
          $builder
            ->select('subscription')
            ->addSelect('subscriber')
            ->from(Subscription::class, 'subscription')
            ->leftJoin('subscription.subscriber', 'subscriber')
            ->leftJoin('subscription.subscription_service', 'subscription_service')
            ->andWhere('subscription.subscription_service = :subscription_service')
            ->setParameter('subscription_service', $subscriptionService);
        },
      ])
      ->handleRequest($request);

    if ($table->isCallback()) {
      return $table->getResponse();
    }

    return array(
      'datatable' => $table, 'subscriptionService' => $subscriptionService
    );
  }

  /**
   * @param Request $request
   * @Route("/operatori/subscriptions/{subscriptionService}/upload",name="operatori_importa_csv_iscrizioni")
   * @Method("POST")
   * @return mixed
   * @throws \Exception
   */
  public function iscrizioniCsvUploadAction(Request $request, SubscriptionService $subscriptionService)
  {
    $uploadedFile = $request->files->get('upload');
    if (empty($uploadedFile)) {
      return new Response('Error: no file imported', Response::HTTP_UNPROCESSABLE_ENTITY,
        ['content-type' => 'text/plain']);
    }

    if ($uploadedFile->getMimeType() != 'text/csv' && ($uploadedFile->getMimeType() == 'text/plain' && $uploadedFile->guessClientExtension() != 'csv')) {
      return new Response('Invalid file', Response::HTTP_UNPROCESSABLE_ENTITY,
        ['content-type' => 'text/plain']);
    }
    $rows = $this->csv_to_array($uploadedFile->getPathname());

    // create response object
    $response = new stdClass();
    $response->errors = [];

    // If subscriptions limits exceedes available space skip import
    if ($subscriptionService->getSubscribersLimit() && $subscriptionService->getSubscribersLimit() - $subscriptionService->getSubscriptions()->count() < count($rows)) {
      $response->errors[] = ['error' => 'Il numero di iscrizioni è superiore al numero massimo consentito'];
    } else {
      foreach ($rows as $row) {
        // No code provided: set default to current subscription service
        if (!array_key_exists('code', $row)) {
          $row['code'] = $subscriptionService->getCode();
        }
        if ($row['code'] == $subscriptionService->getCode()) {
          $subscription = $this->subscriptionsBackOffice->execute($row);
          if (!$subscription instanceof Subscription) {
            // error
            $response->errors[] = $subscription;
          }
        }
      }
    }

    // Remove duplicates
    $response->errors = array_map('unserialize', array_unique(array_map('serialize', $response->errors)));
    if (count($response->errors) > 0) {
      return new Response(json_encode($response), Response::HTTP_BAD_REQUEST, ['content-type' => 'application/json']);
    } else {
      return new Response("Subscriptions correctly imported", Response::HTTP_OK, ['content-type' => 'text/plain']);
    }
  }

  protected function csv_to_array($filename = '', $delimiter = ',', $enclosure = '"')
  {
    if (!file_exists($filename) || !is_readable($filename))
      return FALSE;
    $header = NULL;
    $data = array();
    if (($handle = fopen($filename, 'r')) !== FALSE) {
      while (($row = fgetcsv($handle, 0, $delimiter)) !== FALSE) {
        if (!$header) {
          $temp = array();
          foreach ($row as $r) {
            $temp [] = $r;
          }
          $header = $temp;
        } else {
          $data[] = array_combine($header, $row);
        }
      }
      fclose($handle);
    }
    return $data;
  }
}
