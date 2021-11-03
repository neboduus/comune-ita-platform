<?php

namespace AppBundle\DataTable;


use AppBundle\Entity\OperatoreUser;
use AppBundle\Entity\ScheduledAction;
use AppBundle\Entity\Servizio;
use Doctrine\ORM\QueryBuilder;
use Omines\DataTablesBundle\Adapter\Doctrine\ORMAdapter;
use Omines\DataTablesBundle\Column\DateTimeColumn;
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
        'template' => '@App/Admin/table/services/_id.html.twig',
      ])
      ->add('name', TextColumn::class, [
        'label' => 'Nome',
        'orderable' => true,
        'searchable' => true
      ])
      ->add('status', MapColumn::class, [
        'label' => 'Stato',
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
        'template' => '@App/Admin/table/services/_actions.html.twig',
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
              ->setParameter('allowedServices', $user->getServiziAbilitati());
          }

        },
      ])
      ->addOrderBy('name', DataTable::SORT_ASCENDING);
  }
}