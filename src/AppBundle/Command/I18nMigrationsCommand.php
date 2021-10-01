<?php

namespace AppBundle\Command;

use AppBundle\DataFixtures\ORM\LoadData;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PropertyAccess\PropertyAccess;

class I18nMigrationsCommand extends ContainerAwareCommand
{
  private $defaultLocale = 'it';
  private $servicesI18nFields = [
    'name', 'description', 'who', 'howto', 'specialCases', 'moreInfo', 'compilationInfo', 'finalIndications', 'feedbackMessages'
  ];

  protected function configure()
  {
    $this
      ->setName('ocsdc:i18n:migrate')
      ->setDescription('Migra i contenuti traducibili nella tabella di traduzioni se non già presenti.')
      ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Dry run');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {

    $this->symfonyStyle = new SymfonyStyle($input, $output);
    try {

      $dryRun = $input->getOption('dry-run');

      /** @var EntityManager $entityManager */
      $entityManager = $this->getContainer()->get('doctrine')->getManager();
      $translationsRepo = $entityManager->getRepository('Gedmo\Translatable\Entity\Translation');

      $services = $entityManager->getRepository('AppBundle:Servizio')->findAll();
      $accessor = PropertyAccess::createPropertyAccessor();

      $servicesToUpdate = [];

      foreach ($services as $service) {
        $translations = $translationsRepo->findTranslations($service);
        foreach ($this->servicesI18nFields as $field) {
          $value = $accessor->getValue($service, $field);
          if ( (!isset($translations[$this->defaultLocale][$field]) || empty($translations[$this->defaultLocale][$field])) && !empty($value) ) {
              $servicesToUpdate[$service->getName()][]=$field;
              if (!$dryRun) {
                $translationsRepo->translate($service, $field, $this->defaultLocale, $value);
              }
          }
        }
        $entityManager->persist($service);
      }
      $entityManager->flush();

      if (!empty($servicesToUpdate)) {
        if (!$dryRun) {
          $this->symfonyStyle->success('Sono stati tradotti in ' . $this->defaultLocale . ' i seguenti servizi:');
        } else {
          $this->symfonyStyle->note('Verranno tradotti in ' . $this->defaultLocale . ' i seguenti servizi:');
        }
        foreach ($servicesToUpdate as $k => $v) {
          $this->symfonyStyle->writeln( $k );
          $this->symfonyStyle->listing( $v );
        }
      } else {
        $this->symfonyStyle->note('Non sono presenti servizi da tradurre.');
      }

    } catch (\Exception $e) {
      $this->symfonyStyle->error('Error: ' . $e->getMessage());
      return 1;
    }
  }
}