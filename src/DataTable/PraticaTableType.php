<?php

namespace App\DataTable;


use App\Entity\Pratica;
use Omines\DataTablesBundle\Adapter\Doctrine\ORMAdapter;
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
        'template' => 'Pratiche/table/_id.html.twig',
      ])
      ->add('status', MapColumn::class, [
        'label' => 'general.stato',
        'orderable' => false,
        'searchable' => false,
        'map' => [
          '1000' => 'status_draft',
        ],
      ])
      ->add('servizio', TextColumn::class, [
        'label' => 'pratica.servizio',
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
        'label' => 'pratica.data_di_creazione',
        //'format' => 'd-m-Y H:i',
        'orderable' => true,
        'searchable' => false
      ])
      ->add('actions', TwigColumn::class, [
        'label' => '',
        'className' => 'text-right',
        'orderable' => false,
        'searchable' => false,
        'template' => 'Pratiche/table/_actions.html.twig',
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
