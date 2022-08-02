<?php

namespace App\Command;

use App\Entity\Ente;
use App\Entity\Servizio;
use App\Protocollo\PiTreProtocolloParameters;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Cache\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;


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
   * @var EntityManager
   */
  private $em;

  /**
   * @var SymfonyStyle
   */
  private $io;

  protected function configure()
  {
    $this
      ->setName('ocsdc:peo:fix-payment-settings')
      ->setDescription('Corregge le impostazioni di pagamento per i servizi Peo');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $this->em = $this->getContainer()->get('doctrine')->getManager();
    $this->io = new SymfonyStyle($input, $output);

    $locale = $this->getContainer()->getParameter('locale');
    $this->getContainer()->get('translator')->setLocale($locale);

    $context = $this->getContainer()->get('router')->getContext();
    $context->setHost($this->getContainer()->getParameter('ocsdc_host'));
    $context->setScheme($this->getContainer()->getParameter('ocsdc_scheme'));

    $repo = $this->em->getRepository('App:Servizio');
    $services = $repo->findBy(['slug' => $this->slugServices]);

    /** @var Servizio $s */
    if (count($services) > 0) {
      try {
        foreach ($services as $s) {
          $s->setPaymentParameters($this->fixedValue);
          $this->em->persist($s);
          $this->io->success('Fixed service '.$s->getName());
        }
        $this->em->flush();
      } catch (\Exception $e) {
        $this->io->error($e->getMessage());
      }
    } else {
      $this->io->note('No services to fix.');
    }

    return 0;

  }

}
