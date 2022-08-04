<?php

namespace App\Command;

use App\Entity\Erogatore;
use App\Entity\Servizio;
use App\Services\InstanceService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;


class HotFixMissingErogatoreCommand extends Command
{
  /** @var EntityManagerInterface */
  private $entityManager;

  /**
   * @var InstanceService
   */
  private $instanceService;

  public function __construct(EntityManagerInterface $entityManager, InstanceService $instanceService)
  {
    $this->entityManager = $entityManager;

    parent::__construct();
    $this->instanceService = $instanceService;
  }

  protected function configure()
  {
    $this
      ->setName('ocsdc:hotfix-erogatore')
      ->setDescription('Aggiunge un erogatore ai servizi che ne sprovvisti');
  }


  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $io = new SymfonyStyle($input, $output);

    $ente = $this->instanceService->getCurrentInstance();

    foreach ($this->getServizi() as $servizio) {
      if ( count($servizio->getErogatori()) < 1 ) {

        $erogatore = new Erogatore();
        $erogatore->setName('Erogatore di '.$servizio->getName().' per '.$ente->getName());
        $erogatore->addEnte($ente);
        $this->entityManager->persist($erogatore);
        $servizio->activateForErogatore($erogatore);

        $this->entityManager->persist($servizio);
        $this->entityManager->flush();

        $io->success('Fixed Service ' . $servizio->getName());

      }
    }
  }


  /**
   * @return Servizio[]
   */
  private function getServizi()
  {
    $repo = $this->entityManager->getRepository('App\Entity\Servizio');
    return $repo->findAll();
  }
}
