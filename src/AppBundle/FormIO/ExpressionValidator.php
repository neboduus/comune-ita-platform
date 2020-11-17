<?php

namespace AppBundle\FormIO;

use AppBundle\Entity\FormIO;
use AppBundle\Entity\Servizio;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormFactoryInterface;

class ExpressionValidator
{
  /**
   * @var FormFactory
   */
  private $formFactory;

  private $schemaFactory;

  private $entityManager;

  private $logger;

  public function __construct(
    FormFactoryInterface $formFactory,
    SchemaFactoryInterface $schemaFactory,
    EntityManagerInterface $entityManager,
    LoggerInterface $logger
  ) {
    $this->formFactory = $formFactory;
    $this->schemaFactory = $schemaFactory;
    $this->entityManager = $entityManager;
    $this->logger = $logger;
  }

  /**
   * @param string $formIOId
   * @param string $value
   * @param string $expression
   * @param string $message
   * @return string[]
   */
  public function validateData($formIOId, $value, $expression, $message)
  {
    if (empty($expression)) {
      return [];
    }

    $value = json_decode($value, true);

    if (null === $value || '' === $value) {
      return [];
    }

    $schema = $this->schemaFactory->createFromFormId($formIOId);
    $submission = $schema->getDataBuilder()->setDataFromArray($value)->toFullFilledFlatArray();

    $applications = new class($this->entityManager) {

      private $entityManager;

      public function __construct(EntityManagerInterface $entityManager)
      {
        $this->entityManager = $entityManager;
      }

      public function getQueryBuilder($parameters)
      {
        $qb = $this->entityManager->createQueryBuilder()
          ->select('pratica')
          ->from(FormIO::class, 'pratica');

        if (!empty($parameters['status'])) {
          $qb->andWhere('pratica.status IN (:status)')
            ->setParameter('status', (array)$parameters['status']);
        }

        if (!empty($parameters['service'])) {
          $qb->andWhere('servizio.slug in (:service)')
            ->leftJoin('pratica.servizio', 'servizio')
            ->setParameter('service', (array)$parameters['service']);
        }

        if (!empty($parameters['data'])) {
          foreach ($parameters['data'] as $field => $value) {
            $fieldValueKey = str_replace('.', '', $field);
            $qb->andWhere("LOWER(FORMIO_JSON_FIELD(pratica.dematerializedForms $field)) = :{$fieldValueKey}")
              ->setParameter($fieldValueKey, strtolower($value));
          }
        }

        if (!empty($parameters['id'])) {
          $qb->andWhere('pratica.id IN (:id)')
            ->setParameter('id', (array)$parameters['id']);
        }

        return $qb;
      }

      public function count($parameters)
      {
        return $this->getQueryBuilder($parameters)->select('count(pratica.id)')
          ->getQuery()->getSingleScalarResult();
      }

      public function find($parameters)
      {
        $offset = isset($parameters['offset']) ? $parameters['offset'] : 0;
        $limit = isset($parameters['limit']) ? $parameters['limit'] : 10;

        $qb = $this->getQueryBuilder($parameters)->select('pratica.id');
        if (isset($parameters['sort'], $parameters['order'])) {
          $qb->orderBy('pratica.'.$parameters['sort'], strtolower($parameters['order']));
        } else {
          $qb->orderBy('pratica.submissionTime', 'desc');
        }
        $qb->addOrderBy('pratica.id', 'desc');

        return $qb->setFirstResult($offset)
          ->setMaxResults($limit)
          ->getQuery()->execute();
      }
    };

    $expressionLanguage = new ExpressionLanguage();

    try {
      $evaluation = $expressionLanguage->evaluate(
        $expression,
        [
          'applications' => $applications,
          'submission' => $submission,
        ]
      );

      $this->logger->info(__METHOD__ . ' return ' . var_export($evaluation, true), ['expression' => $expression, 'submission' => $submission]);
    } catch (\Throwable $e) {
      $evaluation = false;
      $message = 'Il sistema di validazione ha restituito un errore: i dati inseriti non sono validi';
      $this->logger->error($e->getMessage(), ['formIOId' => $formIOId, 'submission' => $submission]);
    }

    return $evaluation ? [] : [$message];
  }
}
