<?php

namespace App\Services\Manager;

use App\Entity\Categoria;
use App\Entity\GeographicArea;
use App\Entity\Recipient;
use App\Entity\ServiceGroup;
use App\Entity\Servizio;
use App\Event\KafkaEvent;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use function Aws\boolean_value;

class ServiceManager
{

  /**
   * @var EntityManagerInterface
   */
  private $entityManager;

  /**
   * @var EventDispatcherInterface
   */
  private $dispatcher;

  /**
   * CategoryManager constructor.
   * @param EntityManagerInterface $entityManager
   * @param EventDispatcherInterface $dispatcher
   */
  public function __construct(EntityManagerInterface $entityManager, EventDispatcherInterface $dispatcher)
  {
    $this->entityManager = $entityManager;
    $this->dispatcher = $dispatcher;
  }

  /**
   * @param Request $request
   * @return array|int|mixed|string
   */
  public function getServices(Request $request)
  {

    $searchText = $request->get('q', false);
    $status = $request->get('status', false);
    $serviceGroupId = $request->get('service_group_id', false);
    $categoryIds = $request->get('topics_id', false);
    $recipientIds = $request->get('recipient_id', false);
    $geographicAreaIds = $request->get('geographic_area_id', false);
    $grouped = boolean_value($request->get('grouped', true));

    $criteria['locale'] = $request->getLocale();

    if ($searchText) {
      $criteria['q'] = $searchText;
    }

    if ($status && in_array($status, Servizio::PUBLIC_STATUSES)) {
      $criteria['status'] = [$status];
    } else {
      $criteria['status'] = Servizio::PUBLIC_STATUSES;
    }
    $criteria['grouped'] = $grouped;

    $repoServices = $this->entityManager->getRepository(Servizio::class);

    if ($serviceGroupId) {
      $serviceGroupRepo = $this->entityManager->getRepository('App\Entity\ServiceGroup');
      $serviceGroup = $serviceGroupRepo->find($serviceGroupId);
      if (!$serviceGroup instanceof ServiceGroup) {
        throw new NotFoundHttpException("Service group not found");
      }
      $criteria['serviceGroup'] = $serviceGroupId;
    }

    if ($categoryIds) {
      $categoriesRepo = $this->entityManager->getRepository('App\Entity\Categoria');
      if (is_array($categoryIds)) {
        foreach ($categoryIds as $id) {
          $category = $categoriesRepo->find($id);
          if (!$category instanceof Categoria) {
            throw new NotFoundHttpException("Category {$id} not found");
          }
        }
      } else {
        $category = $categoriesRepo->find($categoryIds);
        if (!$category instanceof Categoria) {
          throw new NotFoundHttpException("Category {$categoryIds} not found");
        }
      }
      $criteria['topics'] = $categoryIds;
    }

    if ($recipientIds) {
      $recipientsRepo = $this->entityManager->getRepository('App\Entity\Recipient');
      if (is_array($recipientIds)) {
        foreach ($recipientIds as $id) {
          $recipient = $recipientsRepo->find($id);
          if (!$recipient instanceof Recipient) {
            throw new NotFoundHttpException("Recipient {$id} not found");
          }
        }
      } else {
        $recipient = $recipientsRepo->find($recipientIds);
        if (!$recipient instanceof Recipient) {
          throw new NotFoundHttpException("Recipient {$recipientIds} not found");
        }
      }
      $criteria['recipients'] = $recipientIds;
    }

    if ($geographicAreaIds) {
      $geographicAreaRepo = $this->entityManager->getRepository('App\Entity\GeographicArea');
      if (is_array($geographicAreaIds)) {
        foreach ($geographicAreaIds as $id) {
          $geographicArea = $geographicAreaRepo->find($id);
          if (!$geographicArea instanceof GeographicArea) {
            throw new NotFoundHttpException("Geographic {$id} area not found");
          }
        }
      } else {
        $geographicArea = $geographicAreaRepo->find($geographicAreaIds);
        if (!$geographicArea instanceof GeographicArea) {
          throw new NotFoundHttpException("Geographic {$id} area not found");
        }
      }
      $criteria['geographic_areas'] = $geographicAreaIds;
    }

    $services = $repoServices->findByCriteria($criteria);
    return $services;
  }

  /**
   * @return array|array[]
   */
  public function getFacets()
  {
    $results = [
      'topics_id' => [],
      'recipient_id' => [],
      'geographic_area_id' => [],
    ];

    $this->getNotSharedFacets($results);
    $this->getSharedFacets($results);

    return $results;
  }

  /**
   * @param Servizio $servizio
   */
  public function save(Servizio $servizio)
  {
    $this->entityManager->persist($servizio);
    $this->entityManager->flush();

    $this->dispatcher->dispatch(new KafkaEvent($servizio), KafkaEvent::NAME);
  }

  private function getNotSharedFacets(&$results)
  {
    $categoriesRepo = $this->entityManager->getRepository('App\Entity\Categoria');
    /** @var QueryBuilder $qb */
    $qb = $categoriesRepo->createQueryBuilder('c');
    $qb->select('c.id', 'c.name')
      ->join('c.services', 's')
      ->where('s.status IN (:status)')
      ->setParameter(':status', Servizio::PUBLIC_STATUSES)
      ->andWhere('s.sharedWithGroup = :sharedWithGroup')
      ->setParameter('sharedWithGroup', false)
      ->orderBy('c.name', 'ASC')
      ->groupBy('c.id');
    $categories = $qb->getQuery()->getResult();
    foreach ($categories as $item) {
      if (!isset($results['topics_id'][$item['id']])) {
        $results['topics_id'][$item['id']] = [
          'id' => $item['id'],
          'name' => $item['name'],
        ];
      }
    }

    $recipientsRepo = $this->entityManager->getRepository('App\Entity\Recipient');
    /** @var QueryBuilder $qb */
    $qb = $recipientsRepo->createQueryBuilder('r');
    $qb->select('r.id', 'r.name')
      ->join('r.services', 's')
      ->where('s.status IN (:status)')
      ->setParameter(':status', Servizio::PUBLIC_STATUSES)
      ->andWhere('s.sharedWithGroup = :sharedWithGroup')
      ->setParameter('sharedWithGroup', false)
      ->orderBy('r.name', 'ASC')
      ->groupBy('r.id');
    $recipients = $qb->getQuery()->getResult();
    foreach ($recipients as $item) {
      if (!isset($results['recipient_id'][$item['id']])) {
        $results['recipient_id'][$item['id']] = [
          'id' => $item['id'],
          'name' => $item['name'],
        ];
      }
    }

    $geographicAreasRepo = $this->entityManager->getRepository('App\Entity\GeographicArea');
    /** @var QueryBuilder $qb */
    $qb = $geographicAreasRepo->createQueryBuilder('g');
    $qb->select('g.id', 'g.name')
      ->join('g.services', 's')
      ->where('s.status IN (:status)')
      ->setParameter(':status', Servizio::PUBLIC_STATUSES)
      ->andWhere('s.sharedWithGroup = :sharedWithGroup')
      ->setParameter('sharedWithGroup', false)
      ->orderBy('g.name', 'ASC')
      ->groupBy('g.id');
    $geographicAreas = $qb->getQuery()->getResult();
    foreach ($geographicAreas as $item) {
      if (!isset($results['geographic_area_id'][$item['id']])) {
        $results['geographic_area_id'][$item['id']] = [
          'id' => $item['id'],
          'name' => $item['name'],
        ];
      }
    }
  }

  private function getSharedFacets(&$results)
  {
    $categoriesRepo = $this->entityManager->getRepository('App\Entity\Categoria');
    /** @var QueryBuilder $qb */
    $qb = $categoriesRepo->createQueryBuilder('c');
    $qb->select('c.id', 'c.name')
      ->join('c.servicesGroup', 'g')
      ->join('g.services', 's')
      ->where('s.status IN (:status)')
      ->setParameter(':status', Servizio::PUBLIC_STATUSES)
      ->andWhere('s.serviceGroup IS NOT NULL')
      ->andWhere('s.sharedWithGroup = :sharedWithGroup')
      ->setParameter('sharedWithGroup', true)
      ->orderBy('c.name', 'ASC')
      ->groupBy('c.id');
    $categories = $qb->getQuery()->getResult();
    foreach ($categories as $item) {
      if (!isset($results['topics_id'][$item['id']])) {
        $results['topics_id'][$item['id']] = [
          'id' => $item['id'],
          'name' => $item['name'],
        ];
      }
    }

    $recipientsRepo = $this->entityManager->getRepository('App\Entity\Recipient');
    /** @var QueryBuilder $qb */
    $qb = $recipientsRepo->createQueryBuilder('r');
    $qb->select('r.id', 'r.name')
      ->join('r.servicesGroup', 'g')
      ->join('g.services', 's')
      ->where('s.status IN (:status)')
      ->setParameter(':status', Servizio::PUBLIC_STATUSES)
      ->andWhere('s.serviceGroup IS NOT NULL')
      ->andWhere('s.sharedWithGroup = :sharedWithGroup')
      ->setParameter('sharedWithGroup', true)
      ->orderBy('r.name', 'ASC')
      ->groupBy('r.id');
    $recipients = $qb->getQuery()->getResult();
    foreach ($recipients as $item) {
      if (!isset($results['recipient_id'][$item['id']])) {
        $results['recipient_id'][$item['id']] = [
          'id' => $item['id'],
          'name' => $item['name'],
        ];
      }
    }

    $geographicAreasRepo = $this->entityManager->getRepository('App\Entity\GeographicArea');
    /** @var QueryBuilder $qb */
    $qb = $geographicAreasRepo->createQueryBuilder('g');
    $qb->select('g.id', 'g.name')
      ->join('g.servicesGroup', 'sg')
      ->join('sg.services', 's')
      ->where('s.status IN (:status)')
      ->setParameter(':status', Servizio::PUBLIC_STATUSES)
      ->andWhere('s.serviceGroup IS NOT NULL')
      ->andWhere('s.sharedWithGroup = :sharedWithGroup')
      ->setParameter('sharedWithGroup', true)
      ->orderBy('g.name', 'ASC')
      ->groupBy('g.id');
    $geographicAreas = $qb->getQuery()->getResult();
    foreach ($geographicAreas as $item) {
      if (!isset($results['geographic_area_id'][$item['id']])) {
        $results['geographic_area_id'][$item['id']] = [
          'id' => $item['id'],
          'name' => $item['name'],
        ];
      }
    }
  }

  /**
   * @param array $recipientIds
   * @return ArrayCollection
   */
  public function getRecipientsByIds(array $recipientIds): ArrayCollection
  {
    $recipients = new ArrayCollection();
    $repository = $this->entityManager->getRepository(Recipient::class);
    foreach ($recipientIds as $recipientId) {
      $recipient = $repository->find($recipientId);
      if ($recipient) {
        $recipients->add($recipient);
      }
    }
    return $recipients;
  }


  /**
   * @param array $geographicAreasIds
   * @return ArrayCollection
   */
  public function getGeographicAreasByIds(array $geographicAreasIds): ArrayCollection
  {
    $areas = new ArrayCollection();
    $repository = $this->entityManager->getRepository(GeographicArea::class);
    foreach ($geographicAreasIds as $geographicAreasId) {
      $area = $repository->find($geographicAreasId);
      if ($area) {
        $areas->add($area);
      }
    }
    return $areas;
  }

}
