<?php

namespace App\Command;

use App\Entity\AdminUser;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

/**
 * Class OperatoreCreateCommand
 */
class AdministratorCreateCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('ocsdc:crea-admin')
            ->setDescription('Crea un record nella tabella utente di tipo admin');
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

        $user = (new AdminUser())
            ->setUsername($username)
            ->setPlainPassword($password)
            ->setEmail($email)
            ->setNome($nome)
            ->setCognome($cognome)
            ->setEnabled(true);

        try {
            $this->userManager->updateUser($user);
            $output->writeln('Generato admin ' . $user->getFullName());

            return 0;
        } catch (\Exception $e) {
            $output->writeln('Errore: ' . $e->getMessage());

            return 1;
        }
    }
}
