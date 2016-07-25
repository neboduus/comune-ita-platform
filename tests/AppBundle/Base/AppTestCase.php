<?php

namespace Tests\AppBundle\Base;

use AppBundle\Entity\User;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class AppTestCase extends WebTestCase
{
    /**
     *
     * @var Client
     */
    protected $client;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var Router
     */
    protected $router;


    public function setUp()
    {
        $this->client = static::createClient();
        $this->container = $this->client->getContainer();
        $this->em = $this->container->get('doctrine')->getManager();
        $this->router = $this->container->get('router');
        parent::setUp();
    }

    protected function cleanDb($entityString)
    {
        $this->em->createQuery('DELETE FROM ' . $entityString)->execute();
    }

    protected function createUser($termAccepted = true)
    {
        $userName = md5(time());
        $user = new User();
        $user->setName($userName)
             ->setUsername($userName)
             ->addRole('ROLE_USER')
             ->setTermsAccepted($termAccepted);

        $this->em->persist($user);
        try{
            $this->em->flush();
        }catch(\Exception $e){

        }

        return $user;
    }
}
