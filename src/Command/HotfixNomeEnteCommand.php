<?php

namespace App\Command;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;


class HotfixNomeEnteCommand extends Command
{
    const STRING_FIND = 'Consorzio dei Comuni Trentini.con sede legale in Via Torre Verde, 23 Trento';
    const STRING_REPLACE = 'Comune di Tre Ville sede legale in Via Roma 4/A fraz. Ragoli 38095 Tre Ville (TN)';
  /**
   * @var EntityManager
   */
  private $em;

  /**
   * @var SymfonyStyle
   */
  private $io;

  public function __construct(EntityManagerInterface $entityManager) {
    $this->em = $entityManager;
    parent::__construct();
  }
    protected function configure()
    {
        $this
            ->setName('ocsdc:hotfix-nomente')
            ->setDescription('Sostituisce il nome Consorzio dei Comuni con Comune di Tre Ville in db');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $manager = $this->getContainer()->get('doctrine')->getManager();

        $terminiUtilizzoRepo = $manager->getRepository('App:TerminiUtilizzo');
        foreach($terminiUtilizzoRepo->findAll() as $terminiUtilizzo){
            $text = $terminiUtilizzo->getText();
            $newText = str_replace(self::STRING_FIND, self::STRING_REPLACE, $text);
            $terminiUtilizzo->setText($newText);
            $manager->persist($terminiUtilizzo);
        }

        $manager->flush();
    }
}
