<?php

namespace App\Command;

use App\Entity\OperatoreUser;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Routing\RouterInterface;

class SecureUserCommand extends Command
{
  /** @var EntityManagerInterface */
  private $entityManager;

  private $logger;

  private $router;

  private $scheme;

  private $host;

  private $inactiveUserLifeTime;

  public function __construct(
    EntityManagerInterface $entityManager,
    LoggerInterface $logger,
    RouterInterface $router,
    string $scheme,
    string $host,
    $inactiveUserLifeTime
  )
  {
    $this->entityManager = $entityManager;
    $this->logger = $logger;
    $this->router = $router;
    $this->scheme = $scheme;
    $this->host = $host;
    $this->inactiveUserLifeTime = (int)$inactiveUserLifeTime;

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
    $this->logger->info('Starting a scheduled action');

    $interval = (new \DateTime())->sub(new \DateInterval('P' . $this->inactiveUserLifeTime . 'D'));

    // Operatori da disabilitare
    $operators = $this->entityManager->getRepository(OperatoreUser::class)
      ->createQueryBuilder('o')
      ->where('o.enabled = true')
      ->andWhere('o.systemUser = false')
      ->andWhere('o.lastChangePassword < :interval')
      ->setParameter('interval', $interval)
      ->getQuery()->getResult();

    /** @var OperatoreUser $operator */
    foreach ($operators as $operator) {
      $operator->setEnabled(false);
      $this->entityManager->persist($operator);
      $this->entityManager->flush();
    }

    return 0;
  }
}
