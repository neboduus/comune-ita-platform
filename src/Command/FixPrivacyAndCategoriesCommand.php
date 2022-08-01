<?php

namespace App\Command;

use App\DataFixtures\ORM\LoadData;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

class FixPrivacyAndCategoriesCommand extends ContainerAwareCommand
{

  protected function configure()
  {
    $this
      ->setName('ocsdc:fix-privacy-and-categories')
      ->setDescription('Importa categorie e privacy policy sul tentat se non prenseti');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    /** @var EntityManager $entityManager */
    $entityManager = $this->getContainer()->get('doctrine')->getManager();
    $categories = $entityManager->getRepository('App:Categoria')->findAll();

    $loader = new LoadData();
    $loader->setContainer($this->getContainer());
    if (empty($categories)) {
      $output->writeln('Importo le categorie');
      $loader->loadCategories($entityManager);
    }

    $privacy = $entityManager->getRepository('App:TerminiUtilizzo')->findAll();
    if (empty($privacy)) {
      $output->writeln('Importo i termini di utilizzo');
      $loader->loadTerminiUtilizzo($entityManager);
    }
  }
}
