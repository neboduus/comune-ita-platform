<?php

namespace Tests\AppBundle\Base;

use AppBundle\Entity\CPSUser as User;
use AppBundle\Entity\CPSUser;
use AppBundle\Entity\Ente;
use AppBundle\Entity\OperatoreUser;
use AppBundle\Entity\IscrizioneAsiloNido as Pratica;
use AppBundle\Entity\Servizio;
use AppBundle\Services\CPSUserProvider;
use Doctrine\ORM\EntityManager;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class AbstractAppTestCase
 * @package Tests\AppBundle\Base
 */
abstract class AbstractAppTestCase extends WebTestCase
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

    /**
     * @inheritdoc
     */
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
        $this->em->createQuery('DELETE FROM '.$entityString)->execute();
    }

    protected function getCPSUserData()
    {
        $random = rand(0, time());

        return [
            "codiceFiscale" => 'ppippi77t05g224f'.$random,
            "capDomicilio" => '371378',
            "capResidenza" => '38127',
            "cellulare" => null,
            "cittaDomicilio" => 'Verona',
            "cittaResidenza" => 'Trento',
            "cognome" => 'Pippucci'.$random,
            "dataNascita" => '04/01/1973',
            "emailAddress" => 'pippo@pippucci.com'.$random,
            "emailAddressPersonale" => null,
            "indirizzoDomicilio" => 'via Leonardo da vinci 17',
            "indirizzoResidenza" => 'via Marsala 13',
            "luogoNascita" => 'Verona',
            "nome" => 'Pippo'.$random,
            "provinciaDomicilio" => 'VR',
            "provinciaNascita" => 'VR',
            "provinciaResidenza" => 'TN',
            "sesso" => 'M',
            "statoDomicilio" => 'IT',
            "statoNascita" => 'IT',
            "statoResidenza" => 'IT',
            "telefono" => null,
            "titolo" => null,
            "x509certificate_issuerdn" => null,
            "x509certificate_subjectdn" => null,
            "x509certificate_base64" => null,
        ];
    }

    protected function createCPSUserWithTelefonoAndEmail($telefono, $email)
    {
        return $this->createCPSUser(true, $telefono, $email);
    }

    protected function createCPSUser($termAccepted = true, $telefono = null, $email = null)
    {
        $user = $this->container->get('ocsdc.cps.userprovider')->provideUser($this->getCPSUserData());
        if ($termAccepted) {
            $user->setTermsAccepted(true);
        }

        if ($telefono != null) {
            $user->setTelefono($telefono);
        }
        if ($email != null) {
            $user->setEmail($email);
        }
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

    protected function clientRequestAsCPSUser(User $user, $method, $uri, array $parameters = array(), array $files = array(), array $server = array(), $content = null, $changeHistory = true)
    {
        $server += ['REDIRECT_shibb_pat_attribute_codicefiscale' => $user->getCodiceFiscale()];

        return $this->client->request($method, $uri, $parameters, $files, $server, $content, $changeHistory);
    }

    protected function getMockLogger()
    {
        return $this->getMockBuilder(Logger::class)->disableOriginalConstructor()->getMock();
    }

    /**
     * @param User $user
     * @param bool $howMany
     * @return array
     */
    protected function createPratiche(User $user, $howMany = false)
    {
        $pratiche = array();
        if ( !$howMany )
        {
            $howMany = rand(1, 10);
        }

        for ($i = 0; $i < $howMany; $i++)
        {
            $pratiche []= $this->createPratica( $user );
        }
        return $pratiche;
    }


    /**
     * @param CPSUser $user
     * @param OperatoreUser|null $operatore
     * @param null $status
     * @param Ente|null $ente
     * @param Servizio|null $servizio
     * @return Pratica
     */
    protected function createPratica(CPSUser $user, OperatoreUser $operatore = null, $status = null, Ente $ente = null, Servizio $servizio = null)
    {
        if ( $servizio == null) {
            $servizio = $this->createServizioWithAssociatedEnti($this->createEnti());
        }

        $pratica = new Pratica();
        $pratica->setUser($user);
        $pratica->setServizio($servizio);
        if ($operatore) {
            $pratica->setOperatore($operatore);
        }
        if ($ente) {
            $pratica->setEnte($ente);
        }

        if ($status !== null) {
            $pratica->setStatus($status);
        }

        $this->em->persist($pratica);
        $this->em->flush();

        return $pratica;
    }

    /**
     * @param $user
     */
    protected function setupPraticheForUser(CPSUser $user)
    {
        $expectedStatuses = $this->getExpectedPraticaStatuses();


        foreach ($expectedStatuses as $status) {
            $this->createPratica($user, null, $status);
        }
    }

    /**
     * @param CPSUser $user
     * @param OperatoreUser $operatore
     * @param $status
     * @return Pratica|null
     */
    protected function setupPraticheForUserWithOperatoreAndStatus(CPSUser $user, OperatoreUser $operatore = null, $status = null)
    {
        return $this->createPratica($user, $operatore, $status);
    }

    /**
     * @param CPSUser $user
     * @param Ente $ente
     * @param $status
     * @return Pratica|null
     */
    protected function setupPraticheForUserWithEnteAndStatus(CPSUser $user, Ente $ente = null, $status = null)
    {
        return $this->createPratica($user, null, $status, $ente);
    }

    /**
     * @return array
     */
    protected function getExpectedPraticaStatuses()
    {
        $expectedStatuses = [
            Pratica::STATUS_PENDING,
            Pratica::STATUS_REGISTERED,
            Pratica::STATUS_COMPLETE,
            Pratica::STATUS_SUBMITTED,
            Pratica::STATUS_DRAFT,
            Pratica::STATUS_CANCELLED,
        ];

        shuffle($expectedStatuses);

        return $expectedStatuses;
    }

    /**
     * @param Ente[] $enti
     * @return Servizio
     */
    protected function createServizioWithAssociatedEnti($enti)
    {
        $servizio = new Servizio();
        $servizio->setName('Servizio test pratiche')->setEnti($enti);
        $servizio->setDescription('Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer ultricies eros eu dignissim bibendum. Praesent tortor nibh, sodales vel ante quis, ultrices consequat ipsum. Praesent vestibulum vel eros nec consectetur. Phasellus et eros vestibulum, ultrices nisl nec, pharetra velit. Donec in ex fermentum, accumsan eros ac, convallis nulla. Donec ut suscipit purus, eget dignissim odio. Duis a congue felis.');
        $servizio->setArea('Test area');
        $this->em->persist($servizio);
        $this->em->flush();

        return $servizio;
    }

    /**
     * @return array
     */
    protected function createEnti()
    {
        $ente1 = new Ente();
        $ente1->setName('Ente di prova');
        $this->em->persist($ente1);
        $this->em->flush();

        $ente2 = new Ente();
        $ente2->setName('Ente di prova 2');
        $this->em->persist($ente2);
        $this->em->flush();

        return array($ente1, $ente2);
    }

    /**
     * @param $username
     * @param $password
     * @return OperatoreUser
     */
    protected function createOperatoreUser($username, $password, Ente $ente = null)
    {
        $um = $this->container->get('fos_user.user_manager');
        $user = new OperatoreUser();
        $user->setUsername($username)
            ->setPlainPassword($password)
            ->setEmail(md5(rand(0, 1000).time()).'some@fake.email')
            ->setNome('a')
            ->setCognome('b')
            ->setEnabled(true);

        if ($ente) {
            $user->setEnte($ente);
        }

        $um->updateUser($user);

        return $user;
    }
}
