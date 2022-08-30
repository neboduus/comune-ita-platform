<?php

namespace App\Command;

use App\Entity\Ente;
use App\Entity\Servizio;
use App\Protocollo\PiTreProtocolloParameters;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Cache\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\Translation\TranslatorInterface;


class FixPeoPaymentSettingsCommand extends Command
{

  private $fixedValue = [
    'total_amounts' => '2,00',
    'gateways' => [
      'bollo' => [
        'identifier' => 'bollo',
        'parameters' => null,
      ],
    ],
  ];

  private $slugServices = [
    'autorizzazione-paesaggistica-sindaco',
    'domanda-permesso-di-costruire',
    'domanda-permesso-di-costruire-in-sanatoria',
  ];


  /**
   * @var EntityManagerInterface
   */
  private $entityManager;

  /**
   * @param EntityManagerInterface $entityManager
   */
  public function __construct(EntityManagerInterface $entityManager)
  {
    $this->entityManager = $entityManager;
    parent::__construct();
  }

  protected function configure()
  {
    $this
      ->setName('ocsdc:peo:fix-payment-settings')
      ->setDescription('Corregge le impostazioni di pagamento per i servizi Peo');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {

    $io = new SymfonyStyle($input, $output);
    $repo = $this->entityManager->getRepository('App\Entity\Servizio');
    $services = $repo->findBy(['slug' => $this->slugServices]);

    /** @var Servizio $s */
    if (count($services) > 0) {
      try {
        foreach ($services as $s) {
          $s->setPaymentParameters($this->fixedValue);
          $this->entityManager->persist($s);
          $io->success('Fixed service '.$s->getName());
        }
        $this->entityManager->flush();
      } catch (\Exception $e) {
        $io->error($e->getMessage());
      }
    } else {
      $io->note('No services to fix.');
    }

    return 0;

  }

}
