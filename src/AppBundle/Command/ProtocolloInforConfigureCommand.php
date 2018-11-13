<?php

namespace AppBundle\Command;

use AppBundle\Entity\Ente;
use AppBundle\Entity\Servizio;
use AppBundle\Protocollo\PiTreProtocolloParameters;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Cache\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;


class ProtocolloInforConfigureCommand extends ContainerAwareCommand
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var SymfonyStyle
     */
    private $io;

    private $shouldLoadFromFile;
    private $parsed;

    protected function configure()
    {
        $this
            ->setName('ocsdc:configura-protocollo-infor')
            ->addOption('file', 'f', InputOption::VALUE_OPTIONAL, 'Load data from file instead of interactively')
            ->setDescription('Configura i parametri di Protocollo Infor (va eseguito su una singola istanza con la flag `--instance=comune-di-...` ');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $args = $input->getOptions();
        if (!$args['instance']) {
            throw new InvalidArgumentException("Il comune deve essere passato come arogmento , usa `--instance=comune-di-....`");
        }

        $this->shouldLoadFromFile = $input->getOption('file');
        if($this->shouldLoadFromFile) {
            $output->writeln("<info>Loading data from file $this->shouldLoadFromFile</info>");
            if (!file_exists($this->shouldLoadFromFile)) {
                $output->writeln("<error>$this->shouldLoadFromFile file not found</error>");
                die(1);
            }
            $this->parsed = json_decode(file_get_contents($this->shouldLoadFromFile), true);
        }

        $this->em = $this->getContainer()->get('doctrine')->getManager();
        $this->io = new SymfonyStyle($input, $output);

        $ente = $this->chooseEnte();
        $this->showEnteCurrentParameters($ente);
        $this->configureEnte($ente);

    }

    private function configureEnte(Ente $ente)
    {
        $servizio = $this->chooseServizio();
        $this->storeData($ente, $servizio);
        $this->showEnteCurrentParameters($ente);

        if ($this->io->confirm('Continuo?')) {
            $this->configureEnte($ente);
        }
    }

    /**
     * @return Ente
     */
    private function chooseEnte()
    {
        return $this->getEnti()[0];
    }

    /**
     * @return Servizio
     */
    private function chooseServizio()
    {
        $servizi = [];
        foreach ($this->getServizi() as $servizioEntity) {
            $servizi[] = $servizioEntity->getName();
        }
        $servizioName = $this->io->choice('Seleziona il servizio da configurare', $servizi);
        $servizio = $this->em->getRepository('AppBundle:Servizio')->findOneByName($servizioName);
        if (!$servizio) {
            throw new InvalidArgumentException("Servizio $servizioName non trovato");
        }
        return $servizio;
    }

    private function storeData(Ente $ente, Servizio $servizio)
    {
        $data = [];
        if ($this->shouldLoadFromFile) {
            $parametri = $ente->getProtocolloParametersPerServizio($servizio);

            if($parametri) {
                $confirmation = $this->io->confirm("Il servizio è già configurato con questi valori: " . json_encode($parametri) . " vuoi sovrascriverli?", false);
                if (!$confirmation) {
                    $this->io->error('Exiting');
                    die(0);
                }
            }
            $data = $this->parsed;

        } else {
            $this->io->title("Inserisci parametri per {$servizio->getName()} di {$ente->getName()}");

            $keys = [
                'recipientIDArray' => 554,
                'recipientTypeIDArray' => 'R',
                'codeNodeClassification' => 1,
                'codeAdm' => 'CCT_CAL',
                'trasmissionIDArray' => 'CCT_CAL',
                'instance' => 'treville_test'
            ];
            $currentParameters = new PiTreProtocolloParameters((array)$ente->getProtocolloParametersPerServizio($servizio));

            foreach ($keys as $key => $default) {
                // Se è già stato impostato un valore per il parametro corrente lo suggersico altrimenti suggerisco il default
                $suggestion = $currentParameters->has($key) ? $currentParameters->get($key) : $default;
                $data[$key] = $this->io->ask("Inserisci $key", $suggestion);
            }
        }

        $ente->setProtocolloParametersPerServizio($data, $servizio);
        $this->em->flush($ente);
    }


    /**
     * @return Servizio[]
     */
    private function getServizi()
    {
        $repo = $this->em->getRepository('AppBundle:Servizio');

        return $repo->findAll();
    }

    /**
     * @return Ente[]
     */
    private function getEnti()
    {
        $repo = $this->em->getRepository('AppBundle:Ente');

        return $repo->findAll();
    }

    private function showEnteCurrentParameters(Ente $ente)
    {
        $this->io->title("Valori correnti per ente {$ente->getName()}");
        $headers = ['', 'Servizio', 'InforConfig'];
        $rows = [];
        foreach ($this->getServizi() as $index => $servizio) {
            $parameters = json_encode($ente->getProtocolloParametersPerServizio($servizio));
            $rows[] = [$index, $servizio->getName(), $parameters];
        }
        $this->io->table($headers, $rows);
    }
}
