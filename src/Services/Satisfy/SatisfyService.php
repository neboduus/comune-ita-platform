<?php

namespace App\Services\Satisfy;

use App\Entity\Servizio;
use Doctrine\ORM\EntityManagerInterface;
use GraphQL\Client;
use GraphQL\Exception\QueryError;
use GraphQL\Mutation;
use GraphQL\Query;
use GraphQL\Variable;
use Psr\Log\LoggerInterface;

class SatisfyService
{

  private string $apiUrl;
  private string $secret;
  private Client $client;
  private EntityManagerInterface $entityManager;
  private LoggerInterface $logger;

  public function __construct(string $apiUrl, string $secret, EntityManagerInterface $entityManager, LoggerInterface $logger)
  {

    $this->apiUrl = $apiUrl;
    $this->secret = $secret;

    $this->client = new Client(
      $this->apiUrl,
      ['x-hasura-admin-secret' => $this->secret]
    );
    $this->entityManager = $entityManager;
    $this->logger = $logger;

  }

  public function syncEntryPoint(Servizio $service, $storeChanges = true)
  {
        // Il secret non è stato impostato oppure il tenant non è abilitato all'utilizzo di satisfy
    if (empty($this->secret)) {
      $this->logger->info("Non è stato specificato un secret per l'utilizzo di satisfy" );
      return;
    }

    if (!$service->getEnte()->isSatisfyEnabled()) {
      $this->logger->info("Il tenant {$service->getEnte()->getName()} non è abilitato all'utilizzo di satisfy" );
      return;
    }

    $entrypointId = $service->getSatisfyEntrypointId() ?? $service->getId();
    // L'entrypointId è già presente su satisfy
    if ($this->checkEntryPoint($entrypointId)) {
      return;
    }

    if ($entrypointId = $this->createEntryPoint($service->getId(), $service->getEnte()->getSatisfyEntrypointId(), $service->getName())) {
      $service->setSatisfyEntrypointId($entrypointId);
      $this->entityManager->persist($service);
      if ($storeChanges) {
        $this->entityManager->flush();
      }
    }

  }

  private function checkEntryPoint(string $id)
  {
    $gql = (new Query('entrypoints_by_pk'))
      ->setArguments(['id' => $id])
      ->setSelectionSet(['id']);

    try {
      $results = $this->client->runQuery($gql);
    } catch (QueryError $exception) {

      // Catch query error and desplay error details
      $this->logger->error("Errore durante la verifica dell' entrypoint satisfy con id: " . $id, $exception->getErrorDetails());
    }

    // Reformat the results to an array and get the results of part of the array
    $results->reformatResults(true);

    $data = $results->getData();

    if (!empty($data['entrypoints_by_pk'])) {
      return $data['entrypoints_by_pk'];
    }

    return null;
  }

  private function createEntryPoint($id, $tenantId, $serviceName)
  {
    $entryPoint = [
      'id' => $id,
      'name' => 'Valutazione ' . $serviceName,
      'tenant_id' => $tenantId,
      'messages' => [
        'data' => [
          'language' => 'it_IT',
          'question' => 'Quanto è stato facile usare questo servizio?',
          'thanks_negative' => 'Grazie, il tuo parere ci aiuterà a migliorare il servizio!',
          'thanks_positive' => 'Grazie, il tuo parere ci aiuterà a migliorare il servizio!',
        ]
      ],
      'questions' => [
        'data' => [
          ['question_id' => '82974aed-f7a7-4d8d-a996-b27a2fb1734b'],
          ['question_id' => 'ad00425e-44d1-4ed1-9d52-274d45b9f1b0'],
        ]
      ]
    ];

    $mutation = (new Mutation('insert_entrypoints_one'))
      ->setVariables([new Variable('entrypoint', 'entrypoints_insert_input', true)])
      ->setArguments(['object' => '$entrypoint'])
      ->setSelectionSet(['id']);
    $variables = ['entrypoint' => $entryPoint];

    try {
      $results = $this->client->runQuery($mutation, true, $variables);
    } catch (QueryError $exception) {
      // Catch query error and desplay error details
      $this->logger->error("Errore durante la creazione dell' entrypoint satisfy con id: " . $id, $exception->getErrorDetails());
    }

    // Reformat the results to an array and get the results of part of the array
    $results->reformatResults(true);
    $data = $results->getData();
    if (!empty($data['insert_entrypoints_one'])) {
      return $data['insert_entrypoints_one']['id'];
    }

    return null;
  }

  public function deleteEntryPoint(Servizio $service)
  {
    $gql = (new Mutation('delete_entrypoints_by_pk'))
      ->setArguments(['id' => $service->getId()])
      ->setSelectionSet(['id']);

    try {
      $results = $this->client->runQuery($gql);
    } catch (QueryError $exception) {
      // Catch query error and desplay error details
      $this->logger->error("Errore durante l'eliminazione dell' entrypoint satisfy con id: " . $service->getId(), $exception->getErrorDetails());
      return false;
    }

    // Reformat the results to an array and get the results of part of the array
    $results->reformatResults(true);

    $data = $results->getData();

    if (!empty($data['delete_entrypoints_by_pk'])) {
      $service->setSatisfyEntrypointId(null);
      $this->entityManager->persist($service);
      $this->entityManager->flush();
      return true;
    }

    return false;
  }
}
