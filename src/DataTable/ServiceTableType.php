<?php

namespace App\DataTable;


use App\Entity\OperatoreUser;
use App\Entity\Servizio;
use Doctrine\ORM\QueryBuilder;
use Omines\DataTablesBundle\Adapter\Doctrine\ORMAdapter;
use Omines\DataTablesBundle\Column\MapColumn;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\Column\TwigColumn;
use Omines\DataTablesBundle\DataTable;
use Omines\DataTablesBundle\DataTableTypeInterface;
use Symfony\Component\Translation\TranslatorInterface;

class ServiceTableType implements DataTableTypeInterface
{

  public function configure(DataTable $dataTable, array $options)
  {
    $dataTable
      ->add('id', TwigColumn::class, [
        'className' => 'text-truncate',
        'label' => 'Id',
        'orderable' => false,
        'searchable' => false,
        'template' => 'Admin/table/services/_id.html.twig',
      ])
      ->add('name', TextColumn::class, [
        'label' => 'Nome',
        'orderable' => true,
        'searchable' => true
      ])
      ->add('serviceGroup', TwigColumn::class, [
        'label' => 'Gruppo',
        'orderable' => false,
        'searchable' => false,
        'template' => 'Admin/table/services/_service_group.html.twig',
      ])
      ->add('topics', TwigColumn::class, [
        'label' => 'Categoria',
        'orderable' => false,
        'searchable' => false,
        'template' => 'Admin/table/services/_topics.html.twig',
      ])
      ->add('status', MapColumn::class, [
        'label' => 'general.stato',
        'orderable' => false,
        'searchable' => false,
        'map' => [
          '0' => 'Bozza',
          '1' => 'Pubblicato',
          '2' => 'Sospeso',
          '3' => 'Privato',
          '4' => 'Programmato',
        ],
      ])
      ->add('actions', TwigColumn::class, [
        'label' => '',
        'className' => 'w-25 text-right',
        'orderable' => false,
        'searchable' => false,
        'template' => 'Admin/table/services/_actions.html.twig',
      ])
      ->createAdapter(ORMAdapter::class, [
        'entity' => Servizio::class,
        'query' => function (QueryBuilder $builder) use ($options): void {
          $qb = $builder
            ->select('servizio')
            ->from(Servizio::class, 'servizio');

          if (isset($options['user']) && $options['user'] instanceof OperatoreUser) {
            /** @var OperatoreUser $user */
            $user = $options['user'];

            $qb
              ->andWhere('servizio IN (:allowedServices)')
              ->setParameter('allowedServices', $user->getServiziAbilitati())
              ->andWhere('servizio.status IN (:availableStatuses) OR (servizio.status = :statusScheduled AND servizio.scheduledFrom <= :now AND servizio.scheduledTo >= :now)')
              ->setParameter('availableStatuses', array_values([Servizio::STATUS_AVAILABLE, Servizio::STATUS_PRIVATE]))
              ->setParameter('statusScheduled', Servizio::STATUS_SCHEDULED)
              ->setParameter('now', new \DateTime());
          }
        },
      ])
      ->addOrderBy('name', DataTable::SORT_ASCENDING);
  }
}
