<?php

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Entity\Categoria;
use AppBundle\Entity\Ente;
use AppBundle\Entity\Erogatore;
use AppBundle\Entity\Servizio;
use AppBundle\Entity\TerminiUtilizzo;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use AppBundle\Utils\Csv;


class LoadData extends AbstractFixture implements FixtureInterface, ContainerAwareInterface
{

  private $counters = [
    'servizi' => [
      'new' => 0,
      'updated' => 0,
    ],
    'enti' => [
      'new' => 0,
      'updated' => 0,
    ],
    'categorie' => [
      'new' => 0,
      'updated' => 0,
    ],
    'payment_gateways' => [
      'new' => 0,
      'updated' => 0,
    ],
    'privacy' => [
      'new' => 0,
      'updated' => 0,
    ],
  ];

  /** @var  ContainerInterface */
  private $container;

  private $data = [];

  public function setContainer(ContainerInterface $container = null)
  {
    $this->container = $container;
  }

  public function getContainer()
  {
    return $this->container;
  }

  public function load(ObjectManager $manager)
  {
    $this->loadEnti($manager);
    $this->loadCategories($manager);
    $this->loadServizi($manager);
    $this->loadTerminiUtilizzo($manager);
  }

  private function getData($key)
  {
    if (empty($this->data)) {
      $rootDir =  $this->getContainer()->get('kernel')->getRootDir();
      $dataDir = $rootDir . '/../data';

      $categories = Csv::csvToArray($dataDir . '/categories.csv');
      $paymentGateways = Csv::csvToArray($dataDir . '/payment-gateways.csv');
      $privacy = Csv::csvToArray($dataDir . '/privacy.csv');

      $this->data = [
        'categories'       => $categories,
        'payment_gateways' => $paymentGateways,
        'privacy' => $privacy,
      ];
    }

    if (isset($this->data[$key])) {
      return $this->data[$key];
    }

    return [];
  }

  /**
   * @param ObjectManager $manager
   * @deprecated
   */
  public function loadEnti(ObjectManager $manager)
  {
    $data = $this->getData('Enti');
    $entiRepo = $manager->getRepository('AppBundle:Ente');

    foreach ($data as $item) {

      if ($this->container->hasParameter('codice_meccanografico') &&
        $item['codice'] != $this->container->getParameter('codice_meccanografico')) {
        continue;
      }

      $ente = $entiRepo->findOneByCodiceMeccanografico($item['codice']);
      if (!$ente) {
        $this->counters['enti']['new']++;
        $ente = (new Ente())
          ->setName($item['name'])
          ->setCodiceMeccanografico($item['codice'])
          ->setCodiceAmministrativo($item['istat'])
          ->setSiteUrl($item['url'])
          ->setContatti($item['contatti'])
          ->setEmail($item['email'])
          ->setEmailCertificata($item['email_certificata']);
        $manager->persist($ente);
      } else {
        $this->counters['enti']['updated']++;
      }
      $manager->flush();
    }
  }

  public function loadCategories(ObjectManager $manager)
  {
    $data = $this->getData('categories');
    $categoryRepo = $manager->getRepository('AppBundle:Categoria');
    foreach ($data as $item) {
      $category = $categoryRepo->findOneBySlug($item['slug']);
      if (!$category) {
        $this->counters['categorie']['new']++;
        $category = new Categoria();
        $category
          ->setName($item['name'])
          ->setDescription($item['description']);

        $manager->persist($category);

      } else {
        // Update name
        if ($item['name'] != $category->getName()) {
          $category->setName($item['name']);
        }
        // Update Description
        if ($item['description'] != $category->getDescription()) {
          $category->setDescription($item['description']);
        }
        $manager->persist($category);
        $this->counters['categorie']['updated']++;
      }

      $manager->flush();
    }
  }

  /**
   * @param ObjectManager $manager
   * @deprecated
   */
  public function loadServizi(ObjectManager $manager)
  {

    $ente = $this->getContainer()->get('ocsdc.instance_service')->getCurrentInstance();
    if (!$ente instanceof Ente) {
      return;
    }

    $data = $this->getData('Servizi');
    $serviziRepo = $manager->getRepository('AppBundle:Servizio');
    $categoryRepo = $manager->getRepository('AppBundle:Categoria');
    foreach ($data as $item) {
      $codiciMeccanograficiEnti = explode('##', $item['codici_enti']);

      if ($this->container->hasParameter('codice_meccanografico') &&
        !in_array($this->container->getParameter('codice_meccanografico'), $codiciMeccanograficiEnti)) {
        continue;
      }

      $servizio = $serviziRepo->findOneByName($item['name']);
      if (!$servizio) {
        $this->counters['servizi']['new']++;
        $servizio = new Servizio();
        $servizio
          ->setName($item['name'])
          ->setHandler($item['handler'])
          ->setDescription($item['description'])
          ->setHowto($item['testoIstruzioni'])
          ->setStatus($item['status'])
          ->setPraticaFCQN($item['fcqn'])
          ->setPraticaFlowServiceName($item['flow'])
          ->setPraticaFlowOperatoreServiceName($item['flow_operatore'])
          ->setEnte($ente);

        $area = $categoryRepo->findOneByTreeId($item['area']);
        if ($area instanceof Categoria) {
          $servizio->setTopics($area);
        }

        $manager->persist($servizio);
      } else {
        $this->counters['servizi']['updated']++;
      }

      $enti = $manager->getRepository('AppBundle:Ente')->findBy(['codiceMeccanografico' => $codiciMeccanograficiEnti]);
      foreach ($enti as $ente) {
        $erogatore = new Erogatore();
        $erogatore->setName('Erogatore di '.$servizio->getName().' per '.$ente->getName());
        $erogatore->addEnte($ente);
        $manager->persist($erogatore);
        $servizio->activateForErogatore($erogatore);
      }

      $manager->flush();
    }
  }

  /**
   * @param ObjectManager $manager
   */
  public function loadTerminiUtilizzo(ObjectManager $manager)
  {
    $data = $this->getData('privacy');
    $terminiUtilizzoRepo = $manager->getRepository('AppBundle:TerminiUtilizzo');
    foreach ($data as $item) {
      $terminiUtilizzo = $terminiUtilizzoRepo->findOneByName($item['name']);
      if (!$terminiUtilizzo) {
        $this->counters['privacy']['new']++;
        $terminiUtilizzo = (new TerminiUtilizzo())
          ->setName($item['name'])
          ->setText($item['text'])
          ->setMandatory(true);
        $manager->persist($terminiUtilizzo);
      } else {
        // Update name
        if ($item['name'] != $terminiUtilizzo->getName()) {
          $terminiUtilizzo->setName($item['name']);
        }
        // Update Description
        if ($item['text'] != $terminiUtilizzo->getText()) {
          $terminiUtilizzo->setText($item['text']);
        }
        $manager->persist($terminiUtilizzo);
      }
      $manager->flush();
    }
  }

  public function getCounters()
  {
    return $this->counters;
  }
}
