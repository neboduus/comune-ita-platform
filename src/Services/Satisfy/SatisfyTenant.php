<?php

namespace App\Services\Satisfy;

use Doctrine\ORM\EntityManagerInterface;
use GraphQL\Client;
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
}
