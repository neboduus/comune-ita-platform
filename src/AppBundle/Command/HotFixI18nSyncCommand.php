<?php

namespace AppBundle\Command;

use AppBundle\DataFixtures\ORM\LoadData;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\FetchMode;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PropertyAccess\PropertyAccess;

class HotFixI18nSyncCommand extends ContainerAwareCommand
{
  private $defaultLocale = 'it';
  private $servicesI18nFields = [
    'name' => 'name',
    'description' => 'description',
    'who' => 'who',
    'howto' => 'howto',
    'special_cases' => 'specialCases',
    'more_info' => 'moreInfo',
    'compilation_info' => 'compilationInfo',
    'final_indications' => 'finalIndications',
    //'feedback_messages' => 'feedbackMessages'
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

    $this->symfonyStyle = new SymfonyStyle($input, $output);
    try {

      $dryRun = $input->getOption('dry-run');

      /** @var EntityManager $entityManager */
      $entityManager = $this->getContainer()->get('doctrine')->getManager();

      foreach ($this->servicesI18nFields as $k => $v) {
        $sql = "select s.id, s." . $k . ", e.content from servizio as s
              left join ext_translations as e on s.id::text = e.foreign_key and e.field = '" . $v . "' and e.locale = '" . $this->defaultLocale . "'
              where s." . $k . "::text != e.content";

        try {

          $stmt = $entityManager->getConnection()->executeQuery($sql);
          $result = $stmt->fetchAll(FetchMode::ASSOCIATIVE);
          $this->symfonyStyle->note('Servizi da sincronizzare per il campo:' . $k);
          $this->symfonyStyle->note($result);

          if (!$dryRun) {
            foreach ($result as $r) {
              $sql = "update servizio as s set " . $k . " =
                      (select t.content from ext_translations as t where t.field = '" . $v . "' and t.foreign_key = s.id::text and t.locale = '" . $this->defaultLocale . "')
                      where s.id = '" . $r['id'] . "'";
              $entityManager->getConnection()->executeQuery($sql);

              $this->symfonyStyle->note('Aggiornato servizio: ' . $r['id'] . ' campo: ' . $k);
            }
          }
        } catch (DBALException $e) {
          $this->symfonyStyle->error('Errore in aggiornamento servizio: ' . $r['id'] . ' campo: ' . $k . ' - ' . $e->getMessage());
        }
      }

    } catch (\Exception $e) {
      $this->symfonyStyle->error('Error: ' . $e->getMessage());
      return 1;
    }
  }
}
