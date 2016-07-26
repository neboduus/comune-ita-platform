<?php

namespace AppBundle\Tests\Controller;

use AppBundle\Entity\User;
use AppBundle\Entity\Servizio;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ServizioControllerTest extends WebTestCase
{
    /**
     *
     * @var Client
     */
    private $client;

    /**
     * @var EntityManager
     */
    private $em;


    public function setup(){
        $this->client = static::createClient();
        $this->em = $this->client->getContainer()->get('doctrine')->getEntityManager();

        $this->cleanDb( $this->em );
    }


    public function testIndex()
    {
        $user = new User();
        $userName = md5(time());
        $user->setName($userName);

        $repo = $this->em->getRepository("AppBundle:Servizio");
        $servizio = new Servizio();
        $servizio->setName('Primo servizio');

        $this->em->persist($servizio);
        $this->em->flush();

        $serviceCountAfterInsert = count($repo->findAll());

        $crawler = $this->client->request('GET', '/servizi', [], [], ['HTTP_REMOTE_USER' => $user->getName()]);
        $renderedServicesCount = $crawler->filter('.servizio')->count();
        $this->assertEquals( $serviceCountAfterInsert, $renderedServicesCount );

    }

    private function cleanDb( EntityManager $em )
    {
        $em->createQuery('DELETE FROM AppBundle\Entity\Servizio')->execute();
    }

}
