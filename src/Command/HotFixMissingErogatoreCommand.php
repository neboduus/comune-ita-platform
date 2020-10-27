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
  /**
   * @var InstanceService
   */
  protected $instanceService;
  /**
   * @var EntityManager
   */
  private $em;

  public function __construct(EntityManagerInterface $entityManager, InstanceService $instanceService)
  {
    $this->em = $entityManager;
    $this->instanceService = $instanceService;
    parent::__construct();
  }

  protected function configure()
  {
    $this
      ->setName('ocsdc:hotfix-erogatore')
      ->setDescription('Aggiunge un erogatore ai servizi che ne sprovvisti');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $ente = $this->instanceService->getCurrentInstance();

    foreach ($this->getServizi() as $servizio) {
      if (count($servizio->getErogatori()) < 1) {

        $erogatore = new Erogatore();
        $erogatore->setName('Erogatore di ' . $servizio->getName() . ' per ' . $ente->getName());
        $erogatore->addEnte($ente);
        $this->em->persist($erogatore);
        $servizio->activateForErogatore($erogatore);

        $this->em->persist($servizio);
        $this->em->flush();

        $output->writeln('Fixed Service ' . $servizio->getName());
      }
    }

    return 0;
  }

  /**
   * @return Servizio[]
   */
  private function getServizi()
  {
    $repo = $this->em->getRepository('App:Servizio');

    return $repo->findAll();
  }
}
