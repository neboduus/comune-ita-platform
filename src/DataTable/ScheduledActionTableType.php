<?php

namespace App\DataTable;


use App\Entity\ScheduledAction;
use Doctrine\ORM\QueryBuilder;
use Omines\DataTablesBundle\Adapter\Doctrine\ORMAdapter;
use Omines\DataTablesBundle\Column\DateTimeColumn;
use Omines\DataTablesBundle\Column\MapColumn;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\Column\TwigColumn;
use Omines\DataTablesBundle\DataTable;
use Omines\DataTablesBundle\DataTableTypeInterface;
use Omines\DataTablesBundle\Filter\ChoiceFilter;

class ScheduledActionTableType implements DataTableTypeInterface
{
  public function configure(DataTable $dataTable, array $options)
  {

    $filter = new ChoiceFilter();
    $filter->set([
      'operator' => '=',
      'choices' => ['', '1', '3', '4']
    ]);

    $dataTable
      ->add('id', TwigColumn::class, [
        'className' => 'text-truncate',
        'label' => 'Id',
        'orderable' => false,
        'searchable' => false,
        'template' => '@App/Admin/table/scheduledActions/_id.html.twig',
      ])
      ->add('type', MapColumn::class, [
        'label' => 'type',
        'orderable' => false,
        'searchable' => true,
        'map' => [
          'createForPratica' => 'scheduled_actions.create_pdf',
          'protocollo.sendPratica' => 'scheduled_actions.protocollo_send_pratice',
          'protocollo.sendAllegati' => 'scheduled_actions.protocollo_send_attach',
          'protocollo.sendRitiro' => 'scheduled_actions.protocollo_send_withdraw',
          'protocollo.sendRichiesteIntegrazione' => 'scheduled_actions.protocollo_send_integrations',
          'protocollo.refreshPratica' => 'scheduled_actions.protocollo_refresh_pratice',
          'protocollo.uploadFile' => 'scheduled_actions.protocollo_upload_file',
          'giscom.sendPratica' => 'scheduled_actions.giscom_send_pratice',
          'giscom.askCFs' => 'scheduled_actions.giscom_askCFs',
          'application_webhook' => 'scheduled_actions.application_webhook',
          'application_payment_reminder' => 'scheduled_actions.application_payment_reminder',
          'produce_message' => 'scheduled_actions.produce_message',
        ],
      ])
      ->add('params', TwigColumn::class, [
        'className' => 'text-truncate',
        'label' => 'parameters',
        'orderable' => false,
        'searchable' => true,
        'template' => '@App/Admin/table/scheduledActions/_params.html.twig',
      ])
      ->add('hostname', TextColumn::class, [
        'label' => 'Host',
        'orderable' => false,
        'searchable' => false,
      ])
      ->add('retry', TextColumn::class, [
        'label' => '#',
        'orderable' => true,
        'searchable' => false,
      ])
      ->add('status', TwigColumn::class, [
        'label' => 'general.stato',
        'orderable' => false,
        'searchable' => true,
        'field' => 'scheduled_action.status',
        'filter' => $filter,
        'template' => '@App/Admin/table/scheduledActions/_status.html.twig',
      ])
      ->add('log', TextColumn::class, [
        'label' => 'Log',
        'visible' => false,
        'orderable' => false,
        'searchable' => false,
      ])
      ->add('createdAt', DateTimeColumn::class, [
        'label' => 'created_at',
        'format' => 'd/m/y H:i',
        'orderable' => true,
        'searchable' => false,
      ])
      ->add('updatedAt', DateTimeColumn::class, [
        'label' => 'updated_at',
        'format' => 'd/m/y H:i',
        'orderable' => true,
        'searchable' => false,
      ])
      ->add('actions', TwigColumn::class, [
        'label' => '',
        'orderable' => false,
        'searchable' => false,
        'template' => '@App/Admin/table/scheduledActions/_actions.html.twig',
      ])
      ->createAdapter(ORMAdapter::class, [
        'entity' => ScheduledAction::class,
        'query' => function (QueryBuilder $builder) use ($options): void {
          $qb = $builder
            ->select('scheduled_action')
            ->from(ScheduledAction::class, 'scheduled_action');

          if (isset($options['filters'])) {
            if (!empty($options['filters']['status'])) {
              $qb
                ->andWhere('scheduled_action.status = (:status)')
                ->setParameter('status', $options['filters']['status']);
            }
          }
        },
      ])
      ->addOrderBy('updatedAt', DataTable::SORT_DESCENDING);
  }
}
