<?php

namespace App\Command;

use App\Services\GiscomAPIAdapterService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

class SendPraticaToGiscomCommand extends BaseCommand
{
    private $manager;

    private $giscomAPIAdapterService;

    public function __construct(EntityManagerInterface $manager, GiscomAPIAdapterService $giscomAPIAdapterService, ?string $name = null)
    {
        $this->manager = $manager;
        $this->giscomAPIAdapterService = $giscomAPIAdapterService;
        parent::__construct($name);
    }

    protected function configure()
    {
        $this
            ->setName('ocsdc:giscom:send-pratica')
            ->setDescription('Invia una pratica a giscom');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');

        $question = new Question('Inserisci id della pratica: ', '');
        $applicationId = $helper->ask($input, $output, $question);

        $repository = $this->manager->getRepository('App:Pratica');
        $application = $repository->find($applicationId);

        if (!$application) {
            $output->writeln('La pratica passata non esiste');

            return 1;
        }

        $response = $this->giscomAPIAdapterService->sendPraticaToGiscom($application);

        $status = $response->getStatusCode();
        if ($status == 201 || $status == 204) {
            $output->writeln('La pratica è stata inviata correttamente');

            return 0;
        } else {
            dump($response);

            return 1;
        }
    }
}
