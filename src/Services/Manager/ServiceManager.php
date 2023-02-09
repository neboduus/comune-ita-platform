<?php

namespace App\Services\Manager;

use App\Entity\Categoria;
use App\Entity\GeographicArea;
use App\Entity\Pratica;
use App\Entity\Recipient;
use App\Entity\ServiceGroup;
use App\Entity\Servizio;
use App\Entity\UserGroup;
use App\Event\KafkaEvent;
use App\Model\FeedbackMessage;
use App\Model\Service;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;
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
   * @var TranslatorInterface
   */
  private $translator;

  /**
   * @var false|string[]
   */
  private $locales;

  /**
   * CategoryManager constructor.
   * @param EntityManagerInterface $entityManager
   * @param EventDispatcherInterface $dispatcher
   * @param TranslatorInterface $translator
   * @param $locales
   */
  public function __construct(EntityManagerInterface $entityManager, EventDispatcherInterface $dispatcher, TranslatorInterface $translator, $locales)
  {
    $this->entityManager = $entityManager;
    $this->dispatcher = $dispatcher;
    $this->translator = $translator;
    $this->locales = explode('|', $locales);
  }

  /**
   * @param Request $request
   * @return array|int|mixed|string
   */
  public function getServices(Request $request)
  {

    $searchText = $request->get('q', false);
    $status = $request->get('status', false);
    $identifier = $request->get('identifier', false);
    $serviceGroupId = $request->get('service_group_id', false);
    $categoryIds = $request->get('topics_id', false);
    $recipientIds = $request->get('recipient_id', false);
    $geographicAreaIds = $request->get('geographic_area_id', false);
    $grouped = boolean_value($request->get('grouped', true));
    $limit = $request->get('limit', false);

    $criteria['order_by'] = $request->get('order_by', 'name');
    $criteria['ascending'] = boolean_value($request->get('ascending', true));
    $criteria['sticky'] = boolean_value($request->get('sticky'));
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

    if ($identifier) {
      $criteria['identifier'] = $identifier;
    }

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

    if ($limit) {
      $criteria['limit'] = $limit;
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
   * @return array
   */
  public function getDefaultFeedbackMessages(): array
  {
    $i18nMessages = [];
    foreach ($this->locales as $locale) {
      foreach (FeedbackMessage::STATUS_NAMES as $k => $v) {
        $tempMessage = null;
        $temp = new FeedbackMessage();
        $temp->setName($v);
        $temp->setTrigger($k);
        $temp->setSubject(
          $tempMessage['subject'] ?? $this->translator->trans('pratica.email.status_change.subject', [], null, $locale)
        );
        $temp->setMessage(
          $tempMessage['message'] ?? $this->translator->trans('messages.pratica.status.' . $k, [], null, $locale)
        );

        $defaultIsActive = true;
        if ($k == Pratica::STATUS_PENDING || $k == Pratica::STATUS_DRAFT) {
          $defaultIsActive = false;
        }
        $temp->setIsActive(
          $tempMessage['is_active'] ?? $defaultIsActive
        );
        $i18nMessages[$locale][$k] = $temp;
      }
    }
    return $i18nMessages;
  }

  public function checkServiceRelations(Service &$serviceDto)
  {
    $category = $this->entityManager->getRepository('App\Entity\Categoria')->findOneBy(['slug' => $serviceDto->getTopics()]);
    if ($category instanceof Categoria) {
      $serviceDto->setTopics($category);
    } else {
      $category = $this->entityManager->getRepository(Categoria::class)->findOneBy([], ['name' => 'ASC']);
      if ($category instanceof Categoria) {
        $serviceDto->setTopics($category);
      }
    }

    $serviceGroup = $this->entityManager->getRepository('App\Entity\ServiceGroup')->findOneBy(['slug' => $serviceDto->getServiceGroup()]);
    if ($serviceGroup instanceof ServiceGroup) {
      $serviceDto->setServiceGroup($serviceGroup);
    }

    $recipients = [];
    foreach ($serviceDto->getRecipients() as $r) {
      $recipient = $this->entityManager->getRepository('App\Entity\Recipient')->findOneBy(['name' => $r]);
      if ($recipient instanceof Recipient) {
        $recipients []= $recipient;
      }
    }
    $serviceDto->setRecipients($recipients);

    $userGroups = [];
    foreach ($serviceDto->getUserGroupIds() as $id) {
      $userGroup = $this->entityManager->getRepository('App\Entity\UserGroup')->findOneBy(['id' => $id]);
      if ($userGroup instanceof UserGroup) {
        $userGroups []= $userGroup;
      }
    }
    $serviceDto->setUserGroupIds($userGroups);

    $geographicAreas = [];
    foreach ($serviceDto->getGeographicAreas() as $g) {
      $geographicArea = $this->entityManager->getRepository('App\Entity\GeographicArea')->findOneBy(['name' => $g]);
      if ($geographicArea instanceof GeographicArea) {
        $geographicAreas []= $geographicArea;
      }
    }
    $serviceDto->setGeographicAreas($geographicAreas);
  }

  /**
   * Checks which of the pnrr service card fields are empty
   * @param Servizio|null $servizio
   * @return string
   */
  public function getMissingCardFields(?Servizio $servizio): string
  {

    if (!$servizio instanceof Servizio) {
      return '';
    }

    $requiredFields = [
      'servizio.a_chi_si_rivolge' => $servizio->getWho(),
      'servizio.how_to_do' => $servizio->getHowToDo(),
      'servizio.what_you_need' => $servizio->getWhatYouNeed(),
      'servizio.what_you_get' => $servizio->getWhatYouGet(),
      'servizio.times_and_deadlines' => $servizio->getTimesAndDeadlines(),
      'servizio.accedere' => $servizio->getHowto(),
      'user_group.title' => $servizio->getUserGroups()->count(),
      'servizio.conditions' => $servizio->getConditions()
    ];
    $emptyFields = [];
    foreach ($requiredFields as $key => $value) {
      if(empty($value)) {
        $emptyFields [] = $this->translator->trans($key);
      }
    }
    return implode(', ', $emptyFields);
  }

}
