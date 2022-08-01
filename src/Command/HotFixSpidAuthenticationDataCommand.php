<?php

namespace App\Command;

use App\Dto\UserAuthenticationData;
use App\Entity\CPSUser;
use App\Entity\Pratica;
use App\Entity\UserSession;
use Doctrine\DBAL\FetchMode;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class HotFixSpidAuthenticationDataCommand extends ContainerAwareCommand
{
  protected function configure()
  {
    $this
      ->setName('ocsdc:hotfix-spid_in_authentication_data')
      ->setDescription('Corregge il campo spidCode in sessioni_utente/authentication_data');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    /** @var EntityManager $entityManager */
    $entityManager = $this->getContainer()->get('doctrine')->getManager();
    $io = new SymfonyStyle($input, $output);
    $repository = $entityManager->getRepository(UserSession::class);

    $output->writeln(
      'Cerco le righe che hanno il valore authentication_data impostato a cps ma i valori x509certificate_* vuoti'
    );
    // utilizzo la ricerca su casting in text authentication_data::TEXT
    // perché non è detto che sia stato fatto girare lo script ocsdc:hotfix-authentication_data_storage
    $sql = "select *
                from sessioni_utente
                where authentication_data::TEXT like '%cps%'
                    and session_data ->> 'x509certificate_issuerdn' is null
                    and session_data ->> 'x509certificate_subjectdn' is null
                    and session_data ->> 'x509certificate_base64' is null;";
    $stmt = $entityManager->getConnection()->prepare($sql);
    $stmt->execute();
    $rowsWithError = $stmt->fetchAll(FetchMode::ASSOCIATIVE);
    $output->writeln('Ho trovato '.count($rowsWithError).' righe');
    $rowsWithErrorCount = count($rowsWithError);
    $fixedRows = 0;
    $fixedData = [];

    if ($rowsWithErrorCount > 0) {
      $userIdList = [];
      foreach ($rowsWithError as $row) {
        $userIdList[] = $row['user_id'];
      }
      $userIdList = array_unique($userIdList);
      $output->writeln('Le righe coinvolgono '.count($userIdList).' utenti');

      // cerco se gli utenti coinvolti hanno effettuato un accesso con spid a sistema corretto
      $sql = "select distinct user_id, session_data ->> 'spidCode' as spidCode
                from sessioni_utente
                where user_id in ( '".implode("','", $userIdList)."')
                and session_data ->> 'spidCode' is not null";
      $stmt = $entityManager->getConnection()->prepare($sql);
      $stmt->execute();
      $usersWithSpidCode = $stmt->fetchAll(FetchMode::ASSOCIATIVE);

      if (count($usersWithSpidCode) === 0) {
        $output->writeln('Non trovo lo spidCode di nessuno di questi utenti.');
        if ($io->confirm('Vuoi che corregga comunque il valore authenticationMethod per le righe errate?')) {
          foreach ($rowsWithError as $row) {
            $id = $row['id'];
            /** @var UserSession $session */
            $session = $repository->find($id);
            $authenticationData = $session->getAuthenticationData();
            if ($authenticationData['authenticationMethod'] == CPSUser::IDP_CPS_OR_CNS
              && empty($authenticationData['certificateIssuer'])
              && empty($authenticationData['certificateSubject'])
              && empty($authenticationData['certificate'])) {
              $newAuthenticationData = [];
              foreach ($authenticationData as $key => $value) {
                if ($key == 'authenticationMethod') {
                  $value = CPSUser::IDP_SPID;
                }
                $newAuthenticationData[$key] = $value;
              }
              $output->writeln("Correggo $id solo per authenticationMethod");
              $newData = UserAuthenticationData::fromArray($newAuthenticationData);
              $fixedData[$id] = $newData;
              $session->setAuthenticationData($newData);
              $entityManager->persist($session);
              $fixedRows++;
            }
          }

          $entityManager->flush();
        }

      } else {

        $output->writeln('Ho trovato lo spidCode per '.count($usersWithSpidCode).' utenti.');
        $update = $io->confirm('Aggiorno lo spidCode per i '.count($usersWithSpidCode).' utenti che ho trovato.');
        if ($update) {
          $updateAll = $io->confirm(
            'Vuoi che corregga comunque il valore authenticationMethod per le righe errate anche per gli utenti che non ho trovato?'
          );

          $usersSpid = [];
          foreach ($usersWithSpidCode as $row) {
            $usersSpid[$row['user_id']] = $row['spidcode'];
          }

          foreach ($rowsWithError as $row) {
            $id = $row['id'];
            /** @var UserSession $session */
            $session = $repository->find($id);
            $authenticationData = $session->getAuthenticationData();
            if ($authenticationData['authenticationMethod'] == CPSUser::IDP_CPS_OR_CNS
              && empty($authenticationData['certificateIssuer'])
              && empty($authenticationData['certificateSubject'])
              && empty($authenticationData['certificate'])) {
              $newAuthenticationData = [];
              foreach ($authenticationData as $key => $value) {
                if ($key == 'authenticationMethod') {
                  $value = CPSUser::IDP_SPID;
                }
                $newAuthenticationData[$key] = $value;
              }

              if (isset($usersSpid[$row['user_id']])) {
                // aggiungo il valore spid trovato per l'utente
                $newAuthenticationData['spidCode'] = $usersSpid[$row['user_id']];
                $newAuthenticationData['spidLevel'] = 2;
                $newData = UserAuthenticationData::fromArray($newAuthenticationData);
                $output->writeln("Correggo $id con dati validi");
                $fixedData[$id] = $newData;
                $session->setAuthenticationData($newData);
                $entityManager->persist($session);
                $fixedRows++;
              } elseif ($updateAll) {
                $newData = UserAuthenticationData::fromArray($newAuthenticationData);
                $output->writeln("Correggo $id solo per authenticationMethod");
                $fixedData[$id] = $newData;
                $session->setAuthenticationData($newData);
                $entityManager->persist($session);
                $fixedRows++;
              }
            }
          }
          $entityManager->flush();
        }
      }
    }
    $output->writeln("Corrette $fixedRows righe di $rowsWithErrorCount");

    if (count($fixedData) > 0) {
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

    return 0;
  }
}
