<?php

namespace App\DataFixtures\ORM;

use App\DataFixtures\GoogleSpreadsheetTrait;
use App\Entity\AsiloNido;
use App\Entity\Categoria;
use App\Entity\Ente;
use App\Entity\Erogatore;
use App\Entity\PaymentGateway;
use App\Entity\Servizio;
use App\Entity\TerminiUtilizzo;
use App\Services\InstanceService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class LoadData extends Fixture
{
  use GoogleSpreadsheetTrait;

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
  ];

  /** @var InstanceService */
  private $instanceService;

  public function __construct(InstanceService $instanceService)
  {
    $this->instanceService = $instanceService;
  }

  public function load(ObjectManager $manager)
  {
    $this->loadAsili($manager);
    $this->loadEnti($manager);
    $this->loadCategories($manager);
    $this->loadServizi($manager);
    $this->loadTerminiUtilizzo($manager);
    $this->loadPaymentGateways($manager);
  }

  public function loadAsili(ObjectManager $manager)
  {
    $data = $this->getGoogleSpreadsheetData('Asili');
    foreach ($data as $item) {
      $orari = explode('##', $item['orari']);
      $orari = array_map('trim', $orari);

      $asiloNido = (new AsiloNido())
        ->setName($item['name'])
        ->setSchedaInformativa($item['schedaInformativa'])
        ->setOrari($orari);

      $manager->persist($asiloNido);
      $manager->flush();
    }
  }

  public function loadEnti(ObjectManager $manager)
  {
    $data = $this->getGoogleSpreadsheetData('Enti');
    $entiRepo = $manager->getRepository('App:Ente');

    foreach ($data as $item) {
      if ($this->instanceService->getCurrentInstance() &&
        $item['codice'] != $this->instanceService->getCurrentInstance()->getCodiceMeccanografico()) {
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

      $asiliNames = explode('##', $item['asili']);
      $asiliNames = array_map('trim', $asiliNames);
      $asili = $manager->getRepository('App:AsiloNido')->findBy(['name' => $asiliNames]);
      foreach ($asili as $asilo) {
        $ente->addAsilo($asilo);
      }

      $manager->flush();
    }
  }

  public function loadCategories(ObjectManager $manager)
  {
    $data = $this->getGoogleSpreadsheetData('Categorie');
    $categoryRepo = $manager->getRepository('App:Categoria');
    foreach ($data as $item) {
      $category = $categoryRepo->findOneByTreeId($item['tree_id']);
      $parent = $categoryRepo->findOneByTreeId($item['tree_parent_id']);
      if (!$category) {
        $this->counters['categorie']['new']++;
        $category = new Categoria();
        $category
          ->setName($item['name'])
          ->setDescription($item['description'])
          ->setTreeId($item['tree_id'])
          ->setTreeParentId($item['tree_parent_id']);

        $category->setParentId(($parent ? $parent->getId() : null));
        $manager->persist($category);
      } else {
        $this->counters['categorie']['updated']++;
      }

      $manager->flush();
    }
  }

  public function loadServizi(ObjectManager $manager)
  {
    $ente = $this->instanceService->getCurrentInstance();
    if (!$ente instanceof Ente) {
      return;
    }

    $data = $this->getGoogleSpreadsheetData('Servizi');
    $serviziRepo = $manager->getRepository('App:Servizio');
    $categoryRepo = $manager->getRepository('App:Categoria');
    foreach ($data as $item) {
      $codiciMeccanograficiEnti = explode('##', $item['codici_enti']);

      if ($this->instanceService->getCurrentInstance() &&
        !in_array($this->instanceService->getCurrentInstance()->getCodiceMeccanografico(), $codiciMeccanograficiEnti)) {
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

      $enti = $manager->getRepository('App:Ente')->findBy(['codiceMeccanografico' => $codiciMeccanograficiEnti]);
      foreach ($enti as $ente) {
        $erogatore = new Erogatore();
        $erogatore->setName('Erogatore di ' . $servizio->getName() . ' per ' . $ente->getName());
        $erogatore->addEnte($ente);
        $manager->persist($erogatore);
        $servizio->activateForErogatore($erogatore);
      }

      $manager->flush();
    }
  }

  public function loadTerminiUtilizzo(ObjectManager $manager)
  {
    $data = $this->getGoogleSpreadsheetData('TerminiUtilizzo');
    foreach ($data as $item) {
      $terminiUtilizzo = (new TerminiUtilizzo())
        ->setName($item['name'])
        ->setText($item['text'])
        ->setMandatory(true);
      $manager->persist($terminiUtilizzo);
      $manager->flush();
    }
  }

  public function loadPaymentGateways(ObjectManager $manager)
  {
    $data = $this->getGoogleSpreadsheetData('PaymentGateways');
    $gatewayRepo = $manager->getRepository('App:PaymentGateway');
    foreach ($data as $item) {
      $gateway = $gatewayRepo->findOneByName($item['name']);
      if (!$gateway) {
        $this->counters['payment_gateways']['new']++;
        $gateway = new PaymentGateway();
        $gateway
          ->setName($item['name'])
          ->setIdentifier($item['identifier'])
          ->setDescription($item['description'])
          ->setDisclaimer($item['disclaimer'])
          ->setFcqn($item['fcqn'])
          ->setEnabled($item['enabled']);
        $manager->persist($gateway);
      } else {
        $this->counters['payment_gateways']['updated']++;
      }
      $manager->flush();
    }
  }

  public function getCounters()
  {
    return $this->counters;
  }
}
