<?php

namespace App\Command;

use App\DataFixtures\ORM\LoadData;
use App\Entity\OperatoreUser;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LoadServiziCommand extends Command{
    protected function configure()
    {
        $this
            ->setName('ocsdc:carica-servizi')
            ->setDescription('Carica Servizi, enti e associazioni fra i deu, dal foglio excel');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $manager = $this->getContainer()->get('doctrine')->getManager();

        $loader = new LoadData();
        $loader->setContainer($this->getContainer());

        $loader->loadEnti($manager);
        $loader->loadCategories($manager);
        $loader->loadServizi($manager);
        $loader->loadTerminiUtilizzo($manager);
        $counters = $loader->getCounters();
        $output->writeln('Servizi caricati: '.$counters['servizi']['new']);
        $output->writeln('Servizi aggiornati: '.$counters['servizi']['updated']);



        $em = $this->getContainer()->get('doctrine')->getManager();
        $repo = $em->getRepository('App\Entity\Ente');
        $entiEntites = $repo->findAll();
        $ente = null;
        foreach($entiEntites as $entiEntity){
            if (strpos(strtolower($entiEntity->getName()), 'ville') !== false){
                $ente = $entiEntity;
            }
        }
        if ($ente) {
            $output->writeln('Creo utente di demo (test/test) per ' . $ente->getName());
            $um = $this->getContainer()->get('fos_user.user_manager');
            $user = (new OperatoreUser())
                ->setUsername('test')
                ->setPlainPassword('test')
                ->setEmail('gabriele.francescotto@opencontent.it')
                ->setNome('Mario')
                ->setEnte($ente)
                ->setCognome('Rossi')
                ->setEnabled(true);

            try {
                $um->updateUser($user);
                $output->writeln('Ok: generato nuovo operatore');
            } catch (\Exception $e) {
                $output->writeln('Errore: ' . $e->getMessage());
            }
        }
    }
}
