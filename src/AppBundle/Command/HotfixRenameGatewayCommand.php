<?php

namespace AppBundle\Command;

use AppBundle\Entity\PaymentGateway;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;



class HotfixRenameGatewayCommand extends ContainerAwareCommand
{

  protected function configure()
  {
    $this
      ->setName('ocsdc:hotfix:rename-gateway')
      ->addOption('identifier', null, InputOption::VALUE_REQUIRED, 'Identifier of the gateway')
      ->addOption('name', null, InputOption::VALUE_REQUIRED, 'New name of the gateway')
      ->setDescription('Rinomina un gateway');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $this->symfonyStyle = new SymfonyStyle($input, $output);

    /** @var EntityManagerInterface $entityManager */
    $entityManager = $this->getContainer()->get('doctrine')->getManager();
    $repo = $entityManager->getRepository(PaymentGateway::class);


    $gateway = $repo->findOneBy(array('identifier' => $input->getOption('identifier')));
    if (!$gateway instanceof PaymentGateway) {
      $this->symfonyStyle->error("Gateway di pagamento non trovato ");
      exit(-1);
    }

    $gateway->setName($input->getOption('name'));
    $entityManager->persist($gateway);
    $entityManager->flush();
  }
}
