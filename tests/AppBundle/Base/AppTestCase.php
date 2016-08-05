<?php

namespace Tests\AppBundle\Base;

use AppBundle\Entity\CPSUser as User;
use AppBundle\Services\CPSUserProvider;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Client;
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

    protected function getCPSUserData()
    {
        $random = rand(0,time());
        return [
            "codiceFiscale" => 'ppippi77t05g224f' . $random,
            "capDomicilio" => null,
            "capResidenza" => null,
            "cellulare" => null,
            "cittaDomicilio" => null,
            "cittaResidenza" => null,
            "cognome" => 'Pippucci'.$random,
            "dataNascita" => null,
            "emailAddress" => 'pippo@pippucci.com' . $random,
            "emailAddressPersonale" => null,
            "indirizzoDomicilio" => null,
            "indirizzoResidenza" => null,
            "luogoNascita" => null,
            "nome" => 'Pippo' . $random,
            "provinciaDomicilio" => null,
            "provinciaNascita" => null,
            "provinciaResidenza" => null,
            "sesso" => 'M',
            "statoDomicilio" => null,
            "statoNascita" => null,
            "statoResidenza" => null,
            "telefono" => null,
            "titolo" => null,
            "x509certificate_issuerdn" => null,
            "x509certificate_subjectdn" => null,
            "x509certificate_base64" => null
        ];
    }

    protected function createCPSUser($termAccepted = true)
    {
        $user = $this->userProvider->provideUser($this->getCPSUserData());
        if ($termAccepted){
            $user->setTermsAccepted(true);
            $this->em->persist($user);
            $this->em->flush();
        }
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
        $server += ['HTTP_CODICEFISCALE' => $user->getCodiceFiscale()];
        return $this->client->request($method, $uri, $parameters, $files, $server, $content, $changeHistory);
    }

    public static function setFakeServerRequest()
    {
        $customData = [
            "HTTP_CODICEFISCALE" => 'RLDLCU77T05G224F',
            "HTTP_CAPDOMICILIO" => '35143',
            "HTTP_CAPRESIDENZA" => '35143',
            "HTTP_CELLULARE" => '3386100602',
            "HTTP_CITTADOMICILIO" => 'Padova',
            "HTTP_CITTARESIDENZA" => 'Padova',
            "HTTP_COGNOME" => 'Realdi',
            "HTTP_DATANASCITA" => '05/12/1977',
            "HTTP_EMAILADDRESS" => 'luca@opencontent.it',
            "HTTP_EMAILADDRESSPERSONALE" => 'lr@opencontent.it',
            "HTTP_INDIRIZZODOMICILIO" => 'via Monte Perica 25/B int 4',
            "HTTP_INDIRIZZORESIDENZA" => 'via Monte Perica 25/B int 4',
            "HTTP_LUOGONASCITA" => 'Padova',
            "HTTP_NOME" => 'Luca',
            "HTTP_PROVINCIADOMICILIO" => 'PD',
            "HTTP_PROVINCIANASCITA" => 'PD',
            "HTTP_PROVINCIARESIDENZA" => 'PD',
            "HTTP_SESSO" => 'M',
            "HTTP_STATODOMICILIO" => 'ITALIA',
            "HTTP_STATONASCITA" => 'ITALIA',
            "HTTP_STATORESIDENZA" => 'ITALIA',
            "HTTP_TELEFONO" => '049132456789',
            "HTTP_TITOLO" => 'Sig',
            "HTTP_X509CERTIFICATE_ISSUERDN" => 'test',
            "HTTP_X509CERTIFICATE_SUBJECTDN" => 'test',
            "HTTP_X509CERTIFICATE_BASE64" => 'test'
        ];

        $_SERVER += $customData;

        return $customData;
    }

}
