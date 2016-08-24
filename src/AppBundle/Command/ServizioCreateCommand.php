<?php
namespace AppBundle\Command;

use AppBundle\Entity\Servizio;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ServizioCreateCommand
 */
class ServizioCreateCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('ocsdc:crea-servizio')
            ->setDescription('Crea un record nella tabella servizi')
            ->setHelp("Crea un record nella tabella servizi, lo marca come disabilitato, va abilitato a mano una volta che si è creato il flow corrispondente.")
            ->addArgument('slug', InputArgument::REQUIRED)
            ->addArgument('name', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln([
            'Crea Servizi',
            '============',
            '',
        ]);

        $output->writeln('Slug: '.$input->getArgument('slug'));

        $output->writeln('Retrieving the db connection');
        $em = $this->getContainer()->get('doctrine')->getManager();
        $newServizio = new Servizio();
        $newServizio->setSlug($input->getArgument('slug'));
        $newServizio->setName($input->getArgument('name'));

        $em->persist($newServizio);
        $em->flush();

        $output->writeln(sprintf('Servizio: %s creato, manca il flusso', $input->getArgument('slug')));
    }
}
