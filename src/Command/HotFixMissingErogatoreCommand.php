<?php

namespace App\Command;

use App\Entity\Erogatore;
use App\Entity\Servizio;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class HotFixMissingErogatoreCommand extends AbstractCommand
{
    /**
     * @var SymfonyStyle
     */
    private $io;

    protected function configure()
    {
        $this
            ->setName('ocsdc:hotfix-erogatore')
            ->setDescription('Aggiunge un erogatore ai servizi che ne sprovvisti');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);
        $ente = $this->instanceService->getCurrentInstance();

        foreach ($this->getServizi() as $servizio) {
            if (count($servizio->getErogatori()) < 1) {
                $erogatore = new Erogatore();
                $erogatore->setName('Erogatore di ' . $servizio->getName() . ' per ' . $ente->getName());
                $erogatore->addEnte($ente);
                $this->em->persist($erogatore);
                $servizio->activateForErogatore($erogatore);

                $this->em->persist($servizio);
                $this->em->flush();

                $output->writeln('Fixed Service ' . $servizio->getName());
            }
        }

        return 0;
    }

    /**
     * @return object[]|Servizio[]
     */
    private function getServizi()
    {
        return $this->em->getRepository('App:Servizio')->findAll();
    }
}
