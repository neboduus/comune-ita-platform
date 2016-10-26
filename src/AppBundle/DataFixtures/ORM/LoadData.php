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
            $a= array_map('trim', $a);
            $a = array_combine($dataArray[0], $a);
        });
        array_shift($dataArray); # remove column header

        return $dataArray;
    }

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

    private function loadEnti(ObjectManager $manager)
    {
        $data = $this->getData('Enti');

        foreach ($data as $item) {

            $asiliNames = explode('##', $item['asili']);
            $asiliNames = array_map('trim', $asiliNames);
            $asili = $manager->getRepository('AppBundle:AsiloNido')->findBy(['name' => $asiliNames]);

            $ente = (new Ente())
                ->setName($item['name'])
                ->setCodiceMeccanografico($item['codice'])
                ->setAsili($asili);

            $manager->persist($ente);
            $manager->flush();
        }
    }

    private function loadServizi(ObjectManager $manager)
    {
        $data = $this->getData('Servizi');

        foreach ($data as $item) {

            $entiNames = explode('##', $item['enti']);
            $entiNames = array_map('trim', $entiNames);
            $enti = $manager->getRepository('AppBundle:Ente')->findBy(['name' => $entiNames]);

            $servizio = (new Servizio())
                ->setName($item['name'])
                ->setDescription($item['description'])
                ->setTestoIstruzioni($item['testoIstruzioni'])
                ->setStatus($item['status'])
                ->setArea($item['area'])
                ->setPraticaFCQN($item['fcqn'])
                ->setPraticaFlowServiceName($item['flow'])
                ->setEnti($enti);

            $manager->persist($servizio);
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
}
