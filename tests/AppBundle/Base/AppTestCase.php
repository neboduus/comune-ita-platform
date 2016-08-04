<?php

namespace Tests\AppBundle\Base;

use AppBundle\Entity\User;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Translation\TranslatorInterface;

abstract class AppTestCase extends WebTestCase
{

    /**
     * @var \AppTestKernel
     */
    protected static $kernel;

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

    /**
     * @var TranslatorInterface
     */
    protected $translator;


    public function setUp()
    {
        $this->client = static::createClient();
        $this->container = $this->client->getContainer();
        $this->em = $this->container->get('doctrine')->getManager();
        $this->router = $this->container->get('router');
        $this->translator = $this->container->get('translator');
        parent::setUp();
    }

    protected function cleanDb($entityString)
    {
        $this->em->createQuery('DELETE FROM ' . $entityString)->execute();
    }

    protected function createUser($termAccepted = true)
    {
        $userName = md5(microtime());
        $user = new User();
        $user->setName($userName)
            ->setUsername($userName)
            ->addRole('ROLE_USER')
            ->setTermsAccepted($termAccepted)
            ->setEmail($user->getId().'@'.User::FAKE_EMAIL_DOMAIN)
            ->setPlainPassword('pippo')
            ->setEnabled(true)
        ;

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    /**
     * Attempts to guess the kernel location.
     *
     * When the Kernel is located, the file is required.
     *
     * @return string The Kernel class name
     *
     * @throws \RuntimeException
     */
    protected static function getKernelClass()
    {
        $dir = isset($_SERVER['KERNEL_DIR']) ? $_SERVER['KERNEL_DIR'] : static::getPhpUnitXmlDir();
        $dir = __DIR__.'/../../../'.$dir;
        $finder = new Finder();
        $finder->name('*TestKernel.php')->depth(0)->in($dir);
        $results = iterator_to_array($finder);
        if (!count($results)) {
            throw new \RuntimeException('Either set KERNEL_DIR in your phpunit.xml according to https://symfony.com/doc/current/book/testing.html#your-first-functional-test or override the WebTestCase::createKernel() method.');
        }

        $file = current($results);
        $class = $file->getBasename('.php');

        require_once $file;

        return $class;
    }

}
