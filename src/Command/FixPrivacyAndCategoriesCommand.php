<?php

namespace App\Command;

use App\DataFixtures\ORM\LoadData;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class FixPrivacyAndCategoriesCommand extends Command
{
  /**
   * @var EntityManagerInterface
   */
  private $entityManager;

  /**
   * @param EntityManagerInterface $entityManager
   */
  public function __construct(EntityManagerInterface $entityManager)
  {
    parent::__construct();
    $this->entityManager = $entityManager;
  }

  protected function configure()
  {
    $this
      ->setName('ocsdc:fix-privacy-and-categories')
      ->setDescription('Importa categorie e privacy policy sul tentat se non prenseti');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {

    $categories = $this->entityManager->getRepository('App\Entity\Categoria')->findAll();

    $loader = new LoadData();
    if (empty($categories)) {
      $output->writeln('Importo le categorie');
      $loader->loadCategories($this->entityManager);
    }

    $privacy = $this->entityManager->getRepository('App\Entity\TerminiUtilizzo')->findAll();
    if (empty($privacy)) {
      $output->writeln('Importo i termini di utilizzo');
      $loader->loadTerminiUtilizzo($this->entityManager);
    }

    return 0;
  }
}
