<?php

namespace AppBundle\DataTable;


use AppBundle\Entity\Pratica;
use Doctrine\ORM\QueryBuilder;
use Omines\DataTablesBundle\Adapter\Doctrine\ORMAdapter;
use Omines\DataTablesBundle\Column\DateTimeColumn;
use Omines\DataTablesBundle\Column\MapColumn;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\Column\TwigColumn;
use Omines\DataTablesBundle\DataTable;
use Omines\DataTablesBundle\DataTableTypeInterface;

class PraticaTableType implements DataTableTypeInterface
{
  public function configure(DataTable $dataTable, array $options)
  {
    $dataTable
      ->add('id', TwigColumn::class, [
        'className' => 'text-truncate',
        'label' => 'Id',
        'orderable' => false,
        'searchable' => false,
        'template' => '@App/Pratiche/table/_id.html.twig',
      ])
      ->add('status', MapColumn::class, [
        'label' => 'Stato',
        'orderable' => false,
        'searchable' => false,
        'map' => [
          '1000' => 'Bozza',
        ],
      ])
      ->add('servizio', TextColumn::class, [
        'label' => 'Servizio',
        'orderable' => false,
        'searchable' => true,
        'field' => 'servizio.name'
      ])
      ->add('numeroProtocollo', TextColumn::class, [
        'label' => '# Prot',
        'orderable' => true,
        'searchable' => false
      ])
      ->add('creationTime', TextColumn::class, [
        'label' => 'Data di creazione',
        //'format' => 'd-m-Y H:i',
        'orderable' => true,
        'searchable' => false
      ])
      ->add('actions', TwigColumn::class, [
        'label' => '',
        'className' => 'text-right',
        'orderable' => false,
        'searchable' => false,
        'template' => '@App/Pratiche/table/_actions.html.twig',
      ])
      /*->add('updatedAt', DateTimeColumn::class, [
        'label' => 'Ultimo aggiornamento',
        'format' => 'd-m-Y H:i',
        'orderable' => true,
        'searchable' => false
      ])*/
      ->createAdapter(ORMAdapter::class, [
        'entity' => Pratica::class,
      ]);
  }
}
