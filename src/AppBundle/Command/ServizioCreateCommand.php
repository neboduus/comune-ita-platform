<?php
namespace AppBundle\Command;

use AppBundle\Entity\Servizio;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Cache\Exception\InvalidArgumentException;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use AppBundle\Entity\Categoria;
use AppBundle\Entity\Ente;
use AppBundle\Entity\Erogatore;

/**
 * Class ServizioCreateCommand
 */
class ServizioCreateCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('ocsdc:crea-servizio')
            ->setDescription('Crea un record nella tabella servizi');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');

        // Name
        $question = new Question('Inserisci il nome del Servizio: ', '');
        $question->setValidator(function ($answer) {
            if (!is_string($answer) || empty($answer)) {
                throw new \RuntimeException(
                    'Inserimento obbligatoorio'
                );
            }
            return $answer;
        });
        $name = $helper->ask($input, $output, $question);

        // Enti
        // Lo imposto direttamente
        /*$codiciMeccanograficiEnti = array();
        $question = new Question('Inserisci Codice Meccanografico del Servizio (in caso di più valori separare con ##): ', $this->getContainer()->getParameter('codice_meccanografico'));
        $question->setValidator(function ($answer) {
            $codiciMeccanograficiEnti = explode('##', $answer);
            if ($this->getContainer()->hasParameter('codice_meccanografico') && !in_array( $this->getContainer()->getParameter('codice_meccanografico'), $codiciMeccanograficiEnti)) {
                throw new \RuntimeException(
                    'Codice meccanografico non presente per questa istanza'
                );
            }
            return $answer;
        });
        $codiciEnti = $helper->ask($input, $output, $question);*/

        // Handler
        $question = new Question('Inserisci l\'handler del Servizio: ', '');
        $question->setValidator(function ($answer) {
            if (!is_string($answer) || empty($answer)) {
                throw new \RuntimeException(
                    'Inserimento obbligatoorio'
                );
            }
            return $answer;
        });
        $handler = $helper->ask($input, $output, $question);

        // Area
        $question = new Question('Inserisci Area del Servizio: ', '');
        $question->setValidator(function ($answer) {
            if (empty($answer)) {
                throw new \RuntimeException(
                    'Inserimento obbligatoorio'
                );
            }
            if (!is_numeric($answer)) {
                throw new \RuntimeException(
                    'Sono ammessi solo numeri interi'
                );
            }
            return $answer;
        });
        $area = $helper->ask($input, $output, $question);

        // Description
        $question = new Question('Inserisci la descrizione del Servizio: ', '');
        $description = $helper->ask($input, $output, $question);

        // Istruzioni
        $question = new Question('Inserisci le istruzioni del Servizio: ', '');
        $istruzioni = $helper->ask($input, $output, $question);

        // fcqn
        $question = new Question('Inserisci fcqn del Servizio: ', '');
        $fcqn = $helper->ask($input, $output, $question);

        // flow
        $question = new Question('Inserisci flow del Servizio: ', '');
        $flow = $helper->ask($input, $output, $question);

        // flow operatore
        $question = new Question('Inserisci flow operatore del Servizio: ', '');
        $flowOperator = $helper->ask($input, $output, $question);



        $manager      = $this->getContainer()->get('doctrine')->getManager();
        $serviziRepo  = $manager->getRepository('AppBundle:Servizio');
        $categoryRepo = $manager->getRepository('AppBundle:Categoria');

        $servizio = $serviziRepo->findOneByName($name);
        if (!$servizio instanceof Servizio) {

            $servizio = new Servizio();
            $servizio
                ->setName($name)
                ->setHandler($handler)
                ->setDescription($description)
                ->setTestoIstruzioni($istruzioni)
                ->setPraticaFCQN($fcqn)
                ->setPraticaFlowServiceName($flow)
                ->setPraticaFlowOperatoreServiceName($flowOperator)
                ->setStatus(1);

            $area = $categoryRepo->findOneByTreeId($area);
            if ($area instanceof Categoria) {
                $servizio->setArea($area);
            }

            $manager->persist($servizio);

            $codiciMeccanograficiEnti = $this->getContainer()->getParameter('codice_meccanografico');
            $enti = $manager->getRepository('AppBundle:Ente')->findBy(['codiceMeccanografico' => $codiciMeccanograficiEnti]);
            foreach ($enti as $ente) {
                $erogatore = new Erogatore();
                $erogatore->setName('Erogatore di ' . $servizio->getName() . ' per ' . $ente->getName());
                $erogatore->addEnte($ente);
                $manager->persist($erogatore);
                $servizio->activateForErogatore($erogatore);
            }
            $manager->flush();


            $output->writeln("<info>Servizio creato correttamente</info>");

        } else {
            $output->writeln("<error>Servizio già presente, non verrà eseguita nessuan azione</error>");
        }


    }
}
