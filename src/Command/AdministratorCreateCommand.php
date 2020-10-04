<?php

namespace App\Command;

use App\Entity\AdminUser;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

/**
 * Class OperatoreCreateCommand
 */
class AdministratorCreateCommand extends ContainerAwareCommand
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

        $um = $this->getContainer()->get('fos_user.user_manager');

        $user = (new AdminUser())
            ->setUsername($username)
            ->setPlainPassword($password)
            ->setEmail($email)
            ->setNome($nome)
            ->setCognome($cognome)
            ->setEnabled(true);

        try {
            $um->updateUser($user);
            $output->writeln('Ok: generato nuovo admin');
        } catch (\Exception $e) {
            $output->writeln('Errore: ' . $e->getMessage());
        }
    }

}
