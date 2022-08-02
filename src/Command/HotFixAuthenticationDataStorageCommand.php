<?php

namespace App\Command;

use App\Entity\Pratica;
use App\Entity\UserSession;
use Doctrine\DBAL\FetchMode;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class HotFixAuthenticationDataStorageCommand extends Command
{
  protected function configure()
  {
    $this
      ->setName('ocsdc:hotfix-authentication_data_storage')
      ->setDescription('Corregge il tipo di dato nel campo sessioni_utente/authentication_data');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    /** @var EntityManager $entityManager */
    $entityManager = $this->getContainer()->get('doctrine')->getManager();
    $repository = $entityManager->getRepository(UserSession::class);
    $sql = "select * from sessioni_utente";
    $stmt = $entityManager->getConnection()->prepare($sql);
    $stmt->execute();
    $rows = $stmt->fetchAll(FetchMode::ASSOCIATIVE);
    $fixedData = [];
    foreach ($rows as $row){
      $id = $row['id'];
      /** @var UserSession $session */
      $session = $repository->find($id);
      $authenticationData = $session->getAuthenticationData();
      $fixedData[$id] = $authenticationData;
      $session->setAuthenticationData($authenticationData);
      $output->writeln("Correggo $id");
      $entityManager->persist($session);
    }
    $entityManager->flush();

    $output->writeln("Aggiorno le pratiche");
    $repository = $entityManager->getRepository(Pratica::class);
    $sql = "select id, session_data_id from pratica where session_data_id in ( '".implode("','", array_keys($fixedData))."')";
    $stmt = $entityManager->getConnection()->prepare($sql);
    $stmt->execute();
    $rows = $stmt->fetchAll(FetchMode::ASSOCIATIVE);
    foreach ($rows as $row) {
      $id = $row['id'];
      /** @var Pratica $pratica */
      $pratica = $repository->find($id);
      $pratica->setAuthenticationData($fixedData[$row['session_data_id']]);
      $entityManager->persist($pratica);
    }
    $entityManager->flush();
  }
}
