<?php

namespace Tests\Flows\Operatore;

use AppBundle\Entity\Allegato;
use AppBundle\Entity\AsiloNido;
use AppBundle\Entity\CertificatoNascita;
use AppBundle\Entity\ComponenteNucleoFamiliare;
use AppBundle\Entity\Ente;
use AppBundle\Entity\OperatoreUser;
use AppBundle\Entity\Pratica;
use AppBundle\Entity\Servizio;
use AppBundle\Entity\User;
use AppBundle\Services\CPSUserProvider;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\VarDumper\VarDumper;
use Tests\AppBundle\Base\AbstractAppTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Class CertificatoNascitaOperatoreTest
 */
class CertificatoNascitaOperatoreTest extends AbstractAppTestCase
{
    /**
     * @var CPSUserProvider
     */
    protected $userProvider;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        system('rm -rf '.__DIR__."/../../../var/uploads/pratiche/allegati/*");

        $this->userProvider = $this->container->get('ocsdc.cps.userprovider');
        $this->em->getConnection()->executeQuery('DELETE FROM servizio_enti')->execute();
        $this->em->getConnection()->executeQuery('DELETE FROM ente_asili')->execute();
        $this->cleanDb(ComponenteNucleoFamiliare::class);
        $this->cleanDb(Allegato::class);
        $this->cleanDb(Pratica::class);
        $this->cleanDb(Servizio::class);
        $this->cleanDb(AsiloNido::class);
        $this->cleanDb(OperatoreUser::class);
        $this->cleanDb(Ente::class);
        $this->cleanDb(User::class);
    }

    /**
     * @test
     */
    public function testICanApproveCertificatoNascitaRequestWithoutUploadAFileAsOperatore()
    {
        $password = 'pa$$word';
        $username = 'username';
        $operatore = $this->createOperatoreUser($username, $password);
        //create an ente
        $ente = $this->createEnti()[0];
        //create the autolettura service bound to that ente
        $fqcn = CertificatoNascita ::class;
        $flow = 'ocsdc.form.flow.certificatonascita';
        $flowOperatore = 'ocsdc.form.flow.certificatonascitaoperatore';
        $servizio = $this->createServizioWithAssociatedEnti(array($ente), 'Certificato Nascita', $fqcn, $flow, $flowOperatore);
        $user = $this->createCPSUser();

        $pratica = $this->createPratica($user, $operatore, Pratica::STATUS_PENDING, $ente, $servizio);

        $detailPraticaUrl = $this->router->generate('operatori_show_pratica', ['pratica' => $pratica->getId()]);
        $approvePraticaUrl = $this->router->generate('operatori_approva_pratica', ['pratica' => $pratica->getId()]);

        $this->client->request('GET', $detailPraticaUrl, array(), array(), array(
            'PHP_AUTH_USER' => $username,
            'PHP_AUTH_PW' => $password,
        ));

        $this->assertContains($approvePraticaUrl, $this->client->getResponse()->getContent());

        $this->client->request('GET', $approvePraticaUrl, array(), array(), array(
            'PHP_AUTH_USER' => $username,
            'PHP_AUTH_PW' => $password,
        ));
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode(), "Unexpected HTTP status code");

        // testare il file
        $crawler = $this->client->getCrawler();
        $nextButton = $this->translator->trans('button.next', [], 'CraueFormFlowBundle');
        $finishButton = $this->translator->trans('button.finish', [], 'CraueFormFlowBundle');

        //$this->addAllegatoOperatore($crawler, $nextButton, $form);

        $form = $crawler->selectButton($nextButton)->form();

        //upload_certificato_nascita[allegati_operatore][add]
        /*
         *
         * <div class="alert alert-danger">
         * <ul class="list-unstyled">
         * <li><span class="glyphicon glyphicon-exclamation-sign"></span> Il campo file è richiesto
         * </li>
         * </ul>
         * </div>
         *
         */
        $crawler = $this->client->submit($form);
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode(), "Unexpected HTTP status code");

        //print_r($crawler->filter('.alert-danger'));
        $msg = trim($crawler->filter('.alert-danger ul')->first()->text());
        $this->assertEquals($msg, "Il campo file è richiesto", "File obbligatorio");
    }


    /**
     * @test
     */
    public function testICanApproveCertificatoNascitaRequestAsOperatore()
    {
        $password = 'pa$$word';
        $username = 'username';
        $operatore = $this->createOperatoreUser($username, $password);
        //create an ente
        $ente = $this->createEnti()[0];
        //create the autolettura service bound to that ente
        $fqcn = CertificatoNascita ::class;
        $flow = 'ocsdc.form.flow.certificatonascita';
        $flowOperatore = 'ocsdc.form.flow.certificatonascitaoperatore';
        $servizio = $this->createServizioWithAssociatedEnti(array($ente), 'Certificato Nascita', $fqcn, $flow, $flowOperatore);
        $user = $this->createCPSUser();

        $pratica = $this->createPratica($user, $operatore, Pratica::STATUS_PENDING, $ente, $servizio);

        $mockMailer = $this->setupSwiftmailerMock([$user, $operatore]);
        static::$kernel->setKernelModifier(function (KernelInterface $kernel) use ($mockMailer) {
            $kernel->getContainer()->set('swiftmailer.mailer.default', $mockMailer);
        });

        $detailPraticaUrl = $this->router->generate('operatori_show_pratica', ['pratica' => $pratica->getId()]);
        $approvePraticaUrl = $this->router->generate('operatori_approva_pratica', ['pratica' => $pratica->getId()]);

        $this->client->request('GET', $detailPraticaUrl, array(), array(), array(
            'PHP_AUTH_USER' => $username,
            'PHP_AUTH_PW' => $password,
        ));

        $this->assertContains($approvePraticaUrl, $this->client->getResponse()->getContent());

        $this->client->request('GET', $approvePraticaUrl, array(), array(), array(
            'PHP_AUTH_USER' => $username,
            'PHP_AUTH_PW' => $password,
        ));
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode(), "Unexpected HTTP status code");
//        $crawler = $this->client->followRedirect();

        // testare il file
        $crawler = $this->client->getCrawler();
        $nextButton = $this->translator->trans('button.next', [], 'CraueFormFlowBundle');
        $finishButton = $this->translator->trans('button.finish', [], 'CraueFormFlowBundle');

        //$this->addAllegatoOperatore($crawler, $nextButton, $form);

        $form = $crawler->selectButton($nextButton)->form();
        copy(__DIR__.'/test.pdf', __DIR__.'/run_test.pdf');
        $file = new UploadedFile(__DIR__.'/run_test.pdf', 'test.pdf', null, null, null, true);

        //upload_certificato_nascita[allegati_operatore][add]
        /*
         *
         * <div class="alert alert-danger">
         * <ul class="list-unstyled">
         * <li><span class="glyphicon glyphicon-exclamation-sign"></span> Il campo file è richiesto
         * </li>
         * </ul>
         * </div>
         *
         */
        $form['upload_certificato_nascita[allegati_operatore][add]']->upload($file);
        $crawler = $this->client->submit($form);
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode(), "Unexpected HTTP status code");

        //print_r($crawler->filter('.alert-danger'));
        $msg = trim($crawler->filter('.alert-danger ul')->first()->text());
        $this->assertEquals($msg, "Il file è stato caricato correttamente", "File non caricato");

        //$radio = $crawler->filter("input[type=radio]");

        $form = $crawler->selectButton($nextButton)->form();
        $crawler = $this->client->submit($form);
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode(), "Unexpected HTTP status code");

        $form = $crawler->selectButton($finishButton)->form();
        $this->client->submit($form);
        $this->assertEquals(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode(), "Unexpected HTTP status code");
        $this->client->followRedirect();

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode(), "Unexpected HTTP status code");
    }
}
