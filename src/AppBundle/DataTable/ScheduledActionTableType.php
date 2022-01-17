<?php

namespace AppBundle\DataTable;


use AppBundle\Entity\ScheduledAction;
use Doctrine\ORM\QueryBuilder;
use Omines\DataTablesBundle\Adapter\Doctrine\ORMAdapter;
use Omines\DataTablesBundle\Column\DateTimeColumn;
use Omines\DataTablesBundle\Column\MapColumn;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\Column\TwigColumn;
use Omines\DataTablesBundle\DataTable;
use Omines\DataTablesBundle\DataTableTypeInterface;

class ScheduledActionTableType implements DataTableTypeInterface
{
  public function configure(DataTable $dataTable, array $options)
  {
    $dataTable
      ->add('id', TwigColumn::class, [
        'className' => 'text-truncate',
        'label' => 'Id',
        'orderable' => false,
        'searchable' => false,
        'template' => '@App/Admin/table/scheduledActions/_id.html.twig',
      ])
      ->add('type', MapColumn::class, [
        'label' => 'Tipo',
        'orderable' => false,
        'searchable' => true,
        'map' => [
          'createForPratica' => 'Creazione pdf',
          'protocollo.sendPratica' => 'Protocolla pratica',
          'protocollo.sendAllegati' => 'Protocolla allegati integrazione',
          'protocollo.sendRitiro' => 'Protocolla ritiro',
          'protocollo.sendRichiesteIntegrazione' => 'Protocolla richiesta integrazione',
          'protocollo.refreshPratica' => 'Protocolla esito',
          'protocollo.uploadFile' => 'Protocolla allegati',
          'giscom.sendPratica' => 'Invio pratica Giscomn',
          'giscom.askCFs' => 'Richiesta codici fiscali Giscom',
          'application_webhook' => 'Webhook',
          'application_payment_reminder' => 'Promemoria pagamento',
        ],
      ])
      ->add('params', TwigColumn::class, [
        'className' => 'text-truncate',
        'label' => 'Parametri',
        'orderable' => false,
        'searchable' => true,
        'template' => '@App/Admin/table/scheduledActions/_params.html.twig',
      ])
      ->add('hostname', TextColumn::class, [
        'label' => 'Host',
        'orderable' => false,
        'searchable' => true
      ])
      ->add('retry', TextColumn::class, [
        'label' => '#',
        'orderable' => true,
        'searchable' => false
      ])
      ->add('status', TwigColumn::class, [
        'label' => 'Stato',
        'orderable' => false,
        'searchable' => false,
        'template' => '@App/Admin/table/scheduledActions/_status.html.twig',
      ])
      ->add('log', TextColumn::class, [
        'label' => 'Log',
        'visible' => false,
        'orderable' => false,
        'searchable' => false
      ])
      ->add('createdAt', DateTimeColumn::class, [
        'label' => 'Data di creazione',
        'format' => 'd-m-Y H:i',
        'orderable' => true,
        'searchable' => false
      ])
      ->add('updatedAt', DateTimeColumn::class, [
        'label' => 'Ultimo aggiornamento',
        'format' => 'd-m-Y H:i',
        'orderable' => true,
        'searchable' => false
      ])
      ->createAdapter(ORMAdapter::class, [
        'entity' => ScheduledAction::class,
      ]);
  }
}
