<?php

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Entity\AsiloNido;
use AppBundle\Entity\Ente;
use AppBundle\Entity\Servizio;
use AppBundle\Entity\TerminiUtilizzo;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Google\Spreadsheet\DefaultServiceRequest;
use Google\Spreadsheet\ServiceRequestFactory;
use Google\Spreadsheet\SpreadsheetService;

class LoadData implements FixtureInterface
{
    const PUBLIC_SPREADSHEETS_URL = 'https://docs.google.com/spreadsheets/d/1mbGZN9OIjfsrrjVbs2QB1DjzzMoCPT6MD5cPTJS4308/edit#gid=0';

    const PUBLIC_SPREADSHEETS_ID = '1mbGZN9OIjfsrrjVbs2QB1DjzzMoCPT6MD5cPTJS4308';

    private $counters = [
        'servizi' => [
            'new' => 0,
            'updated' => 0,
        ],
        'enti' => [
            'new' => 0,
            'updated' => 0,
        ]
        ];

    public function load(ObjectManager $manager)
    {
        $this->loadAsili($manager);
        $this->loadEnti($manager);
        $this->loadServizi($manager);
        $this->loadTerminiUtilizzo($manager);
    }

    private function loadAsili(ObjectManager $manager)
    {
        $data = $this->getData('Asili');
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

    private function getData($worksheetTitle)
    {
        $serviceRequest = new DefaultServiceRequest("");
        ServiceRequestFactory::setInstance($serviceRequest);

        $spreadsheetService = new SpreadsheetService();
        $worksheetFeed = $spreadsheetService->getPublicSpreadsheet(self::PUBLIC_SPREADSHEETS_ID);
        $worksheet = $worksheetFeed->getByTitle($worksheetTitle);

        $data = $worksheet->getCsv();

        $dataArray = str_getcsv($data, "\r\n");
        foreach ($dataArray as &$row) {
            $row = str_getcsv($row, ",");
        }

        array_walk($dataArray, function (&$a) use ($dataArray) {
            $a = array_map('trim', $a);
            $a = array_combine($dataArray[0], $a);
        });
        array_shift($dataArray); # remove column header

        return $dataArray;
    }

    public function loadEnti(ObjectManager $manager)
    {
        $data = $this->getData('Enti');
        $entiRepo = $manager->getRepository('AppBundle:Ente');
        foreach ($data as $item) {
            $ente = $entiRepo->findOneByCodiceMeccanografico($item['codice']);
            if (!$ente) {
                $this->counters['enti']['new']++;
                $ente = (new Ente())
                    ->setName($item['name'])
                    ->setCodiceMeccanografico($item['codice']);
                $manager->persist($ente);
            } else {
                $this->counters['enti']['updated']++;
            }

            $asiliNames = explode('##', $item['asili']);
            $asiliNames = array_map('trim', $asiliNames);
            $asili = $manager->getRepository('AppBundle:AsiloNido')->findBy(['name' => $asiliNames]);
            foreach ($asili as $asilo) {
                $ente->addAsilo($asilo);
            }

            $manager->flush();
        }
    }

    /**
     * @param ObjectManager $manager
     */
    public function loadServizi(ObjectManager $manager)
    {
        $data = $this->getData('Servizi');
        $serviziRepo = $manager->getRepository('AppBundle:Servizio');
        foreach ($data as $item) {
            $servizio = $serviziRepo->findOneByName($item['name']);
            if (!$servizio) {
                $this->counters['servizi']['new']++;
                $servizio = new Servizio();
                $servizio
                    ->setName($item['name'])
                    ->setDescription($item['description'])
                    ->setTestoIstruzioni($item['testoIstruzioni'])
                    ->setStatus($item['status'])
                    ->setArea($item['area'])
                    ->setPraticaFCQN($item['fcqn'])
                    ->setPraticaFlowServiceName($item['flow']);
                $manager->persist($servizio);
            } else {
                $this->counters['servizi']['updated']++;
            }

            $codiciMeccanograficiEnti = explode('##', $item['codici_enti']);
            $enti = $manager->getRepository('AppBundle:Ente')->findBy(['codiceMeccanografico' => $codiciMeccanograficiEnti]);
            foreach ($enti as $ente) {
                $servizio->activateForEnte($ente);
            }

            $manager->flush();
        }
    }

    private function loadTerminiUtilizzo(ObjectManager $manager)
    {
        $data = $this->getData('TerminiUtilizzo');
        foreach ($data as $item) {
            $terminiUtilizzo = (new TerminiUtilizzo())
                ->setName($item['name'])
                ->setText($item['text']);
            $manager->persist($terminiUtilizzo);
            $manager->flush();
        }
    }

    public function getCounters()
    {
        return $this->counters;
    }
}
