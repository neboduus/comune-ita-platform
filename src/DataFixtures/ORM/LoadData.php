<?php

namespace App\DataFixtures\ORM;

use App\Entity\Categoria;
use App\Entity\Ente;
use App\Entity\Erogatore;
use App\Entity\Servizio;
use App\Entity\TerminiUtilizzo;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use App\Utils\Csv;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;


class LoadData extends AbstractFixture implements FixtureInterface
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

  private $data = [];

  public function load(ObjectManager $manager)
  {
    $this->loadCategories($manager);
    $this->loadTerminiUtilizzo($manager);
  }

  private function getData($key)
  {

    if (empty($this->data)) {
      // Fixme
      $dataDir = dirname(__FILE__) . '/../../../data';

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


  public function loadCategories(ObjectManager $manager)
  {
    $data = $this->getData('categories');
    $categoryRepo = $manager->getRepository('App\Entity\Categoria');
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
   */
  public function loadTerminiUtilizzo(ObjectManager $manager)
  {
    $data = $this->getData('privacy');
    $terminiUtilizzoRepo = $manager->getRepository('App\Entity\TerminiUtilizzo');
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
