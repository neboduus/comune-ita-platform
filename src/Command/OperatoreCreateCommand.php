<?php

namespace App\Command;

use App\Entity\Ente;
use App\Entity\OperatoreUser;
use InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

/**
 * Class OperatoreCreateCommand
 */
class OperatoreCreateCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('ocsdc:crea-operatore')
            ->setDescription('Crea un record nella tabella utente di tipo operatore');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');

        $question = new Question('Inserisci il nome ', 'Mario');
        $nome = $helper->ask($input, $output, $question);

        $question = new Question('Inserisci il cognome ', 'Rossi');
        $cognome = $helper->ask($input, $output, $question);

        $question = new Question('Inserisci l\'indirizzo email ', 'gabriele@opencontent.it');
        $email = $helper->ask($input, $output, $question);

        $question = new Question('Inserisci lo username ', 'mariorossi');
        $username = $helper->ask($input, $output, $question);

        $question = new Question('Inserisci la password ', 'mariorossi');
        $password = $helper->ask($input, $output, $question);

        $repo = $this->em->getRepository('App:Ente');
        $enti = [];
        /** @var Ente $entiEntity */
        foreach ($repo->findAll() as $entiEntity) {
            $enti[] = $entiEntity->getName();
        }
        $question = new ChoiceQuestion('Seleziona ente di riferimento', $enti, 0);
        $enteName = $helper->ask($input, $output, $question);
        /** @var Ente $ente */
        $ente = $repo->findOneBy(['name' => $enteName]);
        if (!$ente) {
            throw new InvalidArgumentException("Ente $enteName non trovato");
        }

        $user = (new OperatoreUser())
            ->setEnte($ente)
            ->setUsername($username)
            ->setPlainPassword($password)
            ->setEmail($email)
            ->setNome($nome)
            ->setCognome($cognome)
            ->setEnabled(true);

        try {
            $this->userManager->updateUser($user);
            $output->writeln('Ok: generato nuovo operatore');

            return 0;
        } catch (\Exception $e) {
            $output->writeln('Errore: ' . $e->getMessage());

            return 1;
        }
    }
}
