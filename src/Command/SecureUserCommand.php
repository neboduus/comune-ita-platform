<?php

namespace App\Command;

use App\Entity\OperatoreUser;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Routing\RouterInterface;
use Doctrine\ORM\Query\ResultSetMapping;

class SecureUserCommand extends BaseCommand
{
    private $em;

    private $logger;

    private $router;

    private $scheme;

    private $host;

    private $passwordLifeTime;

    private $inactiveUserLifeTime;

    public function __construct(
        EntityManagerInterface $manager,
        LoggerInterface $logger,
        RouterInterface $router,
        string $scheme,
        string $host,
        int $passwordLifeTime,
        int $inactiveUserLifeTime
    ) {
        $this->em = $manager;
        $this->logger = $logger;
        $this->router = $router;
        $this->scheme = $scheme;
        $this->host = $host;
        $this->passwordLifeTime = $passwordLifeTime;
        $this->inactiveUserLifeTime = $inactiveUserLifeTime;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('ocsdc:user-secure:execute')
            ->setDescription('Execute security actions for user class');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $context = $this->router->getContext();
        $context->setHost($this->host);
        $context->setScheme($this->scheme);

        $this->disableOperators();
        //$this->expirePasswordOperators();
    }

    private function disableOperators()
    {
        // Operatori da disabilitare
        $rsm = new ResultSetMapping();
        $operators = $this->em
            ->createNativeQuery("SELECT * FROM utente WHERE type  = 'operatore' AND enabled = true AND last_change_password  < NOW() - INTERVAL '" . $this->inactiveUserLifeTime . " days'", $rsm)
            ->getResult();

        /** @var OperatoreUser $operator */
        foreach ($operators as $operator) {
            $operator->setEnabled(false);
            $this->em->persist($operator);
            $this->em->flush();
        }
    }

    private function expirePasswordOperators()
    {
        // Operatori da modficare la password
        $operators = $em
            ->createQuery("SELECT * FROM utente WHERE type  = 'operatore' AND enabled = true last_change_password  < NOW() - INTERVAL '" . $this->passwordLifeTime . " days'")
            ->getResult();

        /** @var OperatoreUser $operator */
        foreach ($operators as $operator) {
            $operator->setLastChangePassword(new \DateTime());
            $operator->setPassword($operator->getPassword().time());
            $em->persist($operator);
            $em->flush();
        }
    }
}
