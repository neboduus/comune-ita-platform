<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Cache\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

/**
 * Class RegenerateFolderSlugsCommand
 */
class RegenerateFolderSlugsCommand extends ContainerAwareCommand
{
  protected function configure()
  {
    $this
      ->setName('ocsdc:rigenera-folder-slug')
      ->setDescription('Rigenera gli slug delle cartelle');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $helper = $this->getHelper('question');

    $em = $this->getContainer()->get('doctrine')->getManager();
    $repo = $em->getRepository('AppBundle:Ente');
    $entiEntites = $repo->findAll();
    $enti = [];
    foreach($entiEntites as $entiEntity){
      $enti[] = $entiEntity->getName();
    }
    $question = new ChoiceQuestion('Seleziona ente di riferimento', $enti, 0);
    $enteName = $helper->ask($input, $output, $question);
    $ente = $repo->findOneByName($enteName);
    if (!$ente){
      throw new InvalidArgumentException("Ente $enteName non trovato");
    }

    $repo = $em->getRepository('AppBundle:Folder');

    $folders = $repo->findBy(['slug' => null]);
    foreach ($folders as $folder) {
      $folder->setSlug($folder->getTitle());
      $em->persist($folder);
    }
    $em->flush();

    $output->writeln('OK: slug generati');
  }

}
