<?php

namespace App\Command;

use App\DataFixtures\Tenant\LoadDataFixtures;
use App\Entity\Ente;
use App\Entity\OperatoreUser;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\UserRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LoadServiziCommand extends Command
{
    private $manager;

    private $loader;

    private $userManager;

    public function __construct(EntityManagerInterface $manager, LoadDataFixtures $loadDataFixtures, UserRepository $userManager)
    {
        $this->manager = $manager;
        $this->loader = $loadDataFixtures;
        $this->userManager = $userManager;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('ocsdc:carica-servizi')
            ->setDescription('Carica Servizi, enti e associazioni fra i deu, dal foglio excel');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $manager = $this->manager;
        $loader = $this->loader;

        $loader->loadAsili($manager);
        $loader->loadEnti($manager);
        $loader->loadCategories($manager);
        $loader->loadServizi($manager);
        $loader->loadTerminiUtilizzo($manager);
        $counters = $loader->getCounters();
        $output->writeln('Servizi caricati: ' . $counters['servizi']['new']);
        $output->writeln('Servizi aggiornati: ' . $counters['servizi']['updated']);


        $repo = $manager->getRepository('App:Ente');
        /** @var Ente[] $entiEntites */
        $entiEntites = $repo->findAll();
        $ente = null;
        foreach ($entiEntites as $entiEntity) {
            if (strpos(strtolower($entiEntity->getName()), 'ville') !== false) {
                $ente = $entiEntity;
            }
        }
        if ($ente) {
            $output->writeln('Creo utente di demo (test/test) per ' . $ente->getName());
            $um = $this->userManager;
            $user = (new OperatoreUser())
                ->setEnte($ente)
                ->setUsername('test')
                ->setPlainPassword('test')
                ->setEmail('gabriele.francescotto@opencontent.it')
                ->setNome('Mario')
                ->setCognome('Rossi')
                ->setEnabled(true);

            try {
                $um->updateUser($user);
                $output->writeln('Ok: generato nuovo operatore');
            } catch (\Exception $e) {
                $output->writeln('Errore: ' . $e->getMessage());
            }
        }

        return 0;
    }
}
