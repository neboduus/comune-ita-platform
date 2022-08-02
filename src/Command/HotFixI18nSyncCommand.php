<?php

namespace App\Command;

use App\DataFixtures\ORM\LoadData;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\FetchMode;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PropertyAccess\PropertyAccess;

class HotFixI18nSyncCommand extends Command
{
  private $defaultLocale = 'it';
  private $servicesI18nFields = [
    'name' => [
      'ext_field' => 'name',
      'type' => 'string',
    ],
    'description' => [
      'ext_field' => 'description',
      'type' => 'string',
    ],
    'who' => [
      'ext_field' => 'who',
      'type' => 'string',
    ],
    'howto' => [
      'ext_field' => 'howto',
      'type' => 'string',
    ],
    'special_cases' => [
      'ext_field' => 'specialCases',
      'type' => 'string',
    ],
    'more_info' => [
      'ext_field' => 'moreInfo',
      'type' => 'string',
    ],
    'compilation_info' => [
      'ext_field' => 'compilationInfo',
      'type' => 'string',
    ],
    'final_indications' => [
      'ext_field' => 'finalIndications',
      'type' => 'string',
    ],
    'feedback_messages' =>  [
      'ext_field' => 'feedbackMessages',
      'type' => 'json',
    ]
  ];

  protected function configure()
  {
    $this
      ->setName('ocsdc:i18n:sync')
      ->setDescription('Sincronizza i contenuti dalla tabella delle traduzioni alla tabella principale.')
      ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Dry run');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {

    $symfonyStyle = new SymfonyStyle($input, $output);
    try {

      $dryRun = $input->getOption('dry-run');

      /** @var EntityManager $entityManager */
      $entityManager = $this->getContainer()->get('doctrine')->getManager();

      foreach ($this->servicesI18nFields as $k => $v) {
        $sql = "select s.id, s.".$k.", e.content from servizio as s
              left join ext_translations as e on s.id::text = e.foreign_key and e.field = '".$v['ext_field']."' and e.locale = '".$this->defaultLocale."'
              where s.".$k."::text != e.content";

        try {

          $stmt = $entityManager->getConnection()->executeQuery($sql);
          $result = $stmt->fetchAll(FetchMode::ASSOCIATIVE);
          $symfonyStyle->note('Ci sono '. count($result) .' servizi da sincronizzare per il campo: '.$k);
          //$symfonyStyle->note(print_r($result, 1));

          if (!$dryRun) {
            if (!empty($result)) {
              foreach ($result as $r) {
                if ( $r[$k] != $r['content'] ) {
                  if ($v['type'] === 'json') {
                    $sql = "update servizio set " . $k . " = (
                        select t.content::json from ext_translations as t
                        where t.field = '" . $v['ext_field'] . "' and t.foreign_key = servizio.id::text and t.locale = '" . $this->defaultLocale . "')
                        where id = '" . $r['id'] . "'";
                  } else {
                    $sql = "update servizio set ".$k." =
                      (select t.content from ext_translations as t where t.field = '". $v['ext_field'] ."' and t.foreign_key = servizio.id::text and t.locale = '".$this->defaultLocale."')
                      where id = '".$r['id']."'";
                  }
                  $entityManager->getConnection()->executeQuery($sql);
                  $symfonyStyle->success('Aggiornato servizio: '.$r['id'].' campo: '.$k);
                }
              }
            }
          }
        } catch (DBALException $e) {
          $symfonyStyle->error(
            'Errore in aggiornamento servizio: '.$r['id'].' campo: '.$k.' - '.$e->getMessage()
          );
        }
      }

    } catch (\Exception $e) {
      $symfonyStyle->error('Error: '.$e->getMessage());

      return 1;
    }
  }
}
