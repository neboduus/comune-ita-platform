<?php

namespace AppBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Ramsey\Uuid\UuidInterface;

class IntegrazioneRepository extends EntityRepository
{

  public function findByIntegrationRequest($integrationRequestId)
  {
    $qb = $this->createQueryBuilder('a')
      ->where('JSON_FIELD(a.payload richiesta_integrazione) = :id')
      ->setParameter('id', $integrationRequestId);
    return $qb->getQuery()->getResult();
  }
}
