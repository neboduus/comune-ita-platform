<?php

namespace App\Command;

use App\DataFixtures\ORM\LoadData;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

class FixPrivacyAndCategoriesCommand extends Command
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
    $categories = $entityManager->getRepository('App\Entity\Categoria')->findAll();

    $loader = new LoadData();
    $loader->setContainer($this->getContainer());
    if (empty($categories)) {
      $output->writeln('Importo le categorie');
      $loader->loadCategories($entityManager);
    }

    $privacy = $entityManager->getRepository('App\Entity\TerminiUtilizzo')->findAll();
    if (empty($privacy)) {
      $output->writeln('Importo i termini di utilizzo');
      $loader->loadTerminiUtilizzo($entityManager);
    }
  }
}
