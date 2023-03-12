<?php

namespace App\Services\Satisfy;

use App\Entity\Ente;
use Doctrine\ORM\EntityManagerInterface;
use GraphQL\Client;
use GraphQL\Exception\QueryError;
use GraphQL\Mutation;
use GraphQL\Variable;
use Psr\Log\LoggerInterface;

class SatisfyTenant
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

    $this->client = new Client($this->apiUrl, ['x-hasura-admin-secret' => $this->secret]);
    $this->entityManager = $entityManager;
    $this->logger = $logger;

  }

  public function createEntryPoint(Ente $ente, $email, $password)
  {
    $entryPoint = [
      'id' => $ente->getId(),
      'name' => $ente->getName(),
      'contact_mail' => $email,
      'max_num_entrypoints' => 5,
      'password_hash' => $password,
    ];

    $mutation = (new Mutation('insert_tenants_one'))
      ->setVariables([new Variable('entrypoint', 'tenants_insert_input', true)])
      ->setArguments(['object' => '$entrypoint'])
      ->setSelectionSet(['id']);
    $variables = ['entrypoint' => $entryPoint];

    try {
      $results = $this->client->runQuery($mutation, true, $variables);
    } catch (QueryError $exception) {
      // Catch query error and display error details
      throw new \Exception("Errore durante la creazione dell' entrypoint satisfy con id: " . $ente->getId(), $exception->getErrorDetails());
    }

    // Reformat the results to an array and get the results of part of the array
    $results->reformatResults(true);
    $data = $results->getData();
    if (!empty($data['insert_tenants_one'])) {
      $entrypointId = $data['insert_tenants_one']['id'];
      $ente->setSatisfyEntrypointId($entrypointId);
      $this->entityManager->persist($ente);
      $this->entityManager->flush();
      return $data['insert_tenants_one']['id'];
    }

    return null;
  }
}
