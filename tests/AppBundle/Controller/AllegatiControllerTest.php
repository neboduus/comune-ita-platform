<?php


namespace Tests\AppBundle\Controller;


use AppBundle\Entity\Allegato;
use AppBundle\Entity\ComponenteNucleoFamiliare;
use AppBundle\Entity\Ente;
use AppBundle\Entity\ModuloCompilato;
use AppBundle\Entity\Pratica;
use AppBundle\Entity\Servizio;
use AppBundle\Entity\User;
use AppBundle\Logging\LogConstants;
use AppBundle\Validator\Constraints\ValidMimeType;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
use Tests\AppBundle\Base\AbstractAppTestCase;
use Vich\UploaderBundle\Mapping\PropertyMapping;

/**
 * Class AllegatiControllerTest
 */
class AllegatiControllerTest extends AbstractAppTestCase
{
    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();
        system('rm -rf '.__DIR__."/../../../var/uploads/pratiche/allegati/*");
        $this->cleanDb(Allegato::class);
        $this->cleanDb(ComponenteNucleoFamiliare::class);
        $this->cleanDb(Pratica::class);
        $this->cleanDb(User::class);
    }

    /**
     * @test
     */
    public function testThereIsALinkToCreateAllegati()
    {
        $user = $this->createCPSUser();

        $allegatiListPath = $this->router->generate('allegati_list_cpsuser');

        $crawler = $this->clientRequestAsCPSUser($user, 'GET', $allegatiListPath);
        $newPath = $this->router->generate('allegati_create_cpsuser');
        $linkToNew = $crawler->filterXpath('//*[@href="'.$newPath.'"]');
        $this->assertGreaterThan(0, $linkToNew->count());
    }

    /**
     * @test
     */
    public function testAttachmentCanBeRetrievedIfUserIsOwnerOfThePratica()
    {

        $fakeFileName = 'lenovo-yoga-xp1.pdf';
        $destFileName = md5($fakeFileName).'.pdf';

        $this->setupMockedLogger([
            LogConstants::ALLEGATO_DOWNLOAD_PERMESSO_CPSUSER,
        ]);

        $myUser = $this->createCPSUser();

        $allegato = $this->createAllegato($this->createOperatoreUser('username', 'pass'), $myUser, $destFileName, $fakeFileName);

        $allegatoDownloadUrl = $this->router->generate(
            'allegati_download_cpsuser',
            [
                'allegato' => $allegato->getId(),
            ]
        );
        $this->clientRequestAsCPSUser($myUser, 'GET', $allegatoDownloadUrl);
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertContains('attachment', $response->headers->get('Content-Disposition'));
        $this->assertContains($allegato->getOriginalFilename(), $response->headers->get('Content-Disposition'));
    }

    /**
     * @test
     */
    public function testModuloCompilatoCanBeRetrievedIfUserIsOwnerOfThePratica()
    {

        $fakeFileName = 'lenovo-yoga-xp1.pdf';
        $destFileName = md5($fakeFileName).'.pdf';

        $this->setupMockedLogger([
            LogConstants::ALLEGATO_DOWNLOAD_PERMESSO_CPSUSER,
        ]);

        $myUser = $this->createCPSUser();
        $allegato = $this->createModuloCompilato($this->createOperatoreUser('username', 'pass'), $myUser, $destFileName, $fakeFileName);

        $allegatoDownloadUrl = $this->router->generate(
            'allegati_download_cpsuser',
            [
                'allegato' => $allegato->getId(),
            ]
        );
        $this->clientRequestAsCPSUser($myUser, 'GET', $allegatoDownloadUrl);
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertContains('attachment', $response->headers->get('Content-Disposition'));
        $this->assertContains($allegato->getOriginalFilename(), $response->headers->get('Content-Disposition'));
    }

    /**
     * @test
     */
    public function testAttachmentCanBeRetrievedIfUserIsOperatoreOfThePratica()
    {
        $fakeFileName = 'lenovo-yoga-xp1.pdf';
        $destFileName = md5($fakeFileName).'.pdf';

        $this->setupMockedLogger([
            LogConstants::ALLEGATO_DOWNLOAD_PERMESSO_OPERATORE,
        ]);

        $username = 'pippo';
        $password = 'pippo';
        $myUser = $this->createCPSUser();
        $allegato = $this->createAllegato($this->createOperatoreUser($username, $password), $myUser, $destFileName, $fakeFileName);

        $allegatoDownloadUrl = $this->router->generate(
            'allegati_download_operatore',
            [
                'allegato' => $allegato->getId(),
            ]
        );

        $this->client->request('GET', $allegatoDownloadUrl, array(), array(), array(
            'PHP_AUTH_USER' => $username,
            'PHP_AUTH_PW' => $password,
        ));

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertContains('attachment', $response->headers->get('Content-Disposition'));
        $this->assertContains($allegato->getOriginalFilename(), $response->headers->get('Content-Disposition'));
    }

    /**
     * @test
     */
    public function testAttachmentCannotBeRetrievedByUnauthorizedOperatoreUser()
    {
        $fakeFileName = 'lenovo-yoga-xp1.pdf';
        $destFileName = md5($fakeFileName).'.pdf';

        $this->setupMockedLogger([
            LogConstants::ALLEGATO_DOWNLOAD_NEGATO,
        ]);


        $myUser = $this->createCPSUser();
        $allegato = $this->createAllegato($this->createOperatoreUser('p', 'p'), $myUser, $destFileName, $fakeFileName);

        $allegatoDownloadUrl = $this->router->generate(
            'allegati_download_operatore',
            [
                'allegato' => $allegato->getId(),
            ]
        );

        $username = 'pippo';
        $password = 'pippo';
        $this->createOperatoreUser($username, $password);

        $this->client->request('GET', $allegatoDownloadUrl, array(), array(), array(
            'PHP_AUTH_USER' => $username,
            'PHP_AUTH_PW' => $password,
        ));

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function testAttachmentCannotBeRetrievedByUnauthorizedCPSUser()
    {
        $fakeFileName = 'lenovo-yoga-xp1.pdf';
        $destFileName = md5($fakeFileName).'.pdf';

        $this->setupMockedLogger([
            LogConstants::ALLEGATO_DOWNLOAD_NEGATO,
        ]);

        $otherUser = $this->createCPSUser();
        $allegato = $this->createAllegato($this->createOperatoreUser('p', 'p'), $otherUser, $destFileName, $fakeFileName);

        $allegatoDownloadUrl = $this->router->generate(
            'allegati_download_cpsuser',
            [
                'allegato' => $allegato->getId(),
            ]
        );

        $myUser = $this->createCPSUser();
        $this->clientRequestAsCPSUser($myUser, 'GET', $allegatoDownloadUrl);

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function testCPSUserCanCreateAttachment()
    {
        $user = $this->createCPSUser();
        $repo = $this->em->getRepository('AppBundle:Allegato');
        $this->assertEquals(0, count($repo->findBy(['owner' => $user])));
        $allegatiCreatePath = $this->router->generate('allegati_create_cpsuser');
        $crawler = $this->clientRequestAsCPSUser($user, 'GET', $allegatiCreatePath);
        $form = $crawler->selectButton($this->translator->trans('salva'))->form();
        $values = $form->getValues();
        $values['ocsdc_allegato[description]'] = 'pippo';
        $values['ocsdc_allegato[file][file]'] = new UploadedFile(
            __DIR__.'/../Assets/lenovo-yoga-xp1.pdf',
            'lenovo-yoga-xp1.pdf',
            'application/postscript',
            filesize(__DIR__.'/../Assets/lenovo-yoga-xp1.pdf')
        );

        $form->setValues($values);
        $this->client->submit($form);
        //an allegato is created for this user with the correct file
        $this->assertEquals(1, count($repo->findBy(['owner' => $user])));
    }

    /**
     * @test
     * @dataProvider invalidUploadFilesProvider
     * @param string $invalidFilename
     */
    public function testUserCannotcreateAttachmentOfUnsupportedType($invalidFilename)
    {
        $user = $this->createCPSUser();
        $repo = $this->em->getRepository('AppBundle:Allegato');
        $this->assertEquals(0, count($repo->findBy(['owner' => $user])));
        $allegatiCreatePath = $this->router->generate('allegati_create_cpsuser');
        $crawler = $this->clientRequestAsCPSUser($user, 'GET', $allegatiCreatePath);
        $form = $crawler->selectButton($this->translator->trans('salva'))->form();
        $values = $form->getValues();

        $values['ocsdc_allegato[description]'] = 'pippo';
        $values['ocsdc_allegato[file][file]'] = new UploadedFile(
            __DIR__.'/../Assets/'.$invalidFilename,
            $invalidFilename,
            'application/postscript',
            filesize(__DIR__.'/../Assets/'.$invalidFilename)
        );

        $form->setValues($values);
        $crawler = $this->client->submit($form);
        //an allegato is not created for this user
        $this->assertEquals(0, count($repo->findBy(['owner' => $user])));

        $expectedErrorMessage = $this->translator->trans(ValidMimeType::TRANSLATION_ID);
        $errorMessage = $crawler->filter('.has-error')->html();
        $this->assertContains($expectedErrorMessage, $errorMessage);
    }

    /**
     * @test
     */
    public function testUserCanSeeHisOwnAttachments()
    {
        //create attachment for this user
        $user = $this->createCPSUser();
        $fakeFileName = 'lenovo-yoga-xp1.pdf';
        $destFileName = md5($fakeFileName).'.pdf';
        $operatore = $this->createOperatoreUser('p', 'p');
        $myAllegato = $this->createAllegato($operatore, $user, $destFileName, $fakeFileName);

        $otherUser = $this->createCPSUser();
        $fakeFileName = 'lenovo-yoga-xp1.pdf';
        $destFileName = md5($fakeFileName).'.pdf';
        $otherAllegato = $this->createAllegato($operatore, $otherUser, $destFileName, $fakeFileName);

        $crawler = $this->clientRequestAsCPSUser($user, 'GET', $this->router->generate('allegati_list_cpsuser'));
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertEquals(1, $crawler->filterXPath('//*[@data-allegato="'.$myAllegato->getId().'"]')->count());
        $this->assertEquals(0, $crawler->filterXPath('//*[@data-allegato="'.$otherAllegato->getId().'"]')->count());
    }

    /**
     * @test
     */
    public function testUserCannotSeeHisModuliinbetweenHisOwnAttachments()
    {
        //create attachment for this user
        $user = $this->createCPSUser();
        $operatore = $this->createOperatoreUser('p', 'p');

        $fakeFileName = 'lenovo-yoga-xp1.pdf';
        $destFileName = md5($fakeFileName).'.pdf';

        $myAllegato = $this->createAllegato($operatore, $user, $destFileName, $fakeFileName);

        $myModulo = $this->createModuloCompilato($operatore, $user, 'm_'.$destFileName, $fakeFileName);

        $crawler = $this->clientRequestAsCPSUser($user, 'GET', $this->router->generate('allegati_list_cpsuser'));
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertEquals(1, $crawler->filterXPath('//*[@data-allegato="'.$myAllegato->getId().'"]')->count());
        $this->assertEquals(0, $crawler->filterXPath('//*[@data-allegato="'.$myModulo->getId().'"]')->count());
    }

    /**
     * @test
     */
    public function testUserCannotSeeDeleteAllegatoButtonIfAllegatoIsAttachedToPratica()
    {
        $user = $this->createCPSUser();
        $repo = $this->em->getRepository('AppBundle:Allegato');

        $fakeFileName = 'lenovo-yoga-xp1.pdf';
        $destFileName = md5($fakeFileName).'.pdf';
        $boundAllegato = $this->createAllegato($this->createOperatoreUser('p', 'p'), $user, $destFileName, $fakeFileName);
        $pratica = $this->createPratica($user);
        $pratica->addAllegato($boundAllegato);

        $allegatiDeletePath = $this->router->generate('allegati_list_cpsuser');

        $crawler = $this->clientRequestAsCPSUser($user, 'GET', $allegatiDeletePath);

        $crawler = $crawler->filterXPath('//*[@data-allegato="'.$boundAllegato->getId().'"]')
            ->selectButton($this->translator->trans('elimina'));

        $this->assertEquals(0, count($crawler));

        //an allegato is not created for this user
        $this->assertEquals(1, count($repo->findBy(['owner' => $user])));

    }


    /**
     * @test
     */
    public function testUserCanDeleteAllegatoIfNotAttachedToPratica()
    {
        $this->setupMockedLogger([
            LogConstants::ALLEGATO_CANCELLAZIONE_PERMESSA,
        ]);

        $user = $this->createCPSUser();
        $repo = $this->em->getRepository('AppBundle:Allegato');

        $fakeFileName = 'lenovo-yoga-xp1.pdf';
        $destFileName = md5($fakeFileName).'.pdf';
        $boundAllegato = $this->createAllegatoWithNoPratica($user, $destFileName, $fakeFileName);
        $boundAllegato->getPratiche()->clear();
        $this->em->persist($boundAllegato);
        $this->em->flush();

        $allegatiDeletePath = $this->router->generate('allegati_list_cpsuser');

        $crawler = $this->clientRequestAsCPSUser($user, 'GET', $allegatiDeletePath);

        $form = $crawler->filterXPath('//*[@data-allegato="'.$boundAllegato->getId().'"]')
            ->selectButton($this->translator->trans('elimina'))->form();
        $this->client->followRedirects(true);
        $this->client->submit($form);

        //an allegato is not created for this user
        $this->assertEquals(0, count($repo->findBy(['owner' => $user])));
    }


    /**
     * @return array
     */
    public function invalidUploadFilesProvider()
    {
        $filenames = array_map(function ($e) {
            return [basename($e)];
        }, glob(__DIR__.'/../Assets/invalid_*'));

        return $filenames;
    }



    /**
     * @param $username
     * @param $password
     * @param $myUser
     * @param $destFileName
     * @param $fakeFileName
     * @return Allegato
     */
    private function createAllegato($operatore, $myUser, $destFileName, $fakeFileName)
    {
        $pratica = $this->createPratica($myUser);
        $pratica->setOperatore($operatore);

        $allegato = new Allegato();
        $allegato->addPratica($pratica);
        $allegato->setOwner($myUser);
        $allegato->setFilename($destFileName);
        $allegato->setOriginalFilename($fakeFileName);
        $allegato->setDescription('some description');
        $pratica->addAllegato($allegato);

        $directoryNamer = $this->container->get('ocsdc.allegati.directory_namer');
        /** @var PropertyMapping $mapping */
        $mapping = $this->container->get('vich_uploader.property_mapping_factory')->fromObject($allegato)[0];

        $destDir = $mapping->getUploadDestination().'/'.$directoryNamer->directoryName($allegato, $mapping);
        mkdir($destDir, 0777, true);
        $this->assertTrue(copy(__DIR__.'/../Assets/'.$fakeFileName, $destDir.'/'.$destFileName));
        $this->em->persist($pratica);
        $this->em->persist($allegato);
        $this->em->flush();
        $this->em->refresh($pratica);

        return $allegato;
    }

    /**
     * @param $operatore
     * @param $myUser
     * @param $destFileName
     * @param $fakeFileName
     * @return Allegato
     */
    private function createModuloCompilato($operatore, $myUser, $destFileName, $fakeFileName)
    {
        $pratica = $this->createPratica($myUser);
        $pratica->setOperatore($operatore);

        $allegato = new ModuloCompilato();
        $allegato->addPratica($pratica);
        $allegato->setOwner($myUser);
        $allegato->setFilename($destFileName);
        $allegato->setOriginalFilename($fakeFileName);
        $allegato->setDescription('some description');
        $pratica->addModuloCompilato($allegato);

        $directoryNamer = $this->container->get('ocsdc.allegati.directory_namer');
        /** @var PropertyMapping $mapping */
        $mapping = $this->container->get('vich_uploader.property_mapping_factory')->fromObject($allegato)[0];

        $destDir = $mapping->getUploadDestination().'/'.$directoryNamer->directoryName($allegato, $mapping);
        try {
            mkdir($destDir, 0777, true);
        } catch (\Exception $e) {
            //nothing to see here, move on
        }
        $this->assertTrue(copy(__DIR__.'/../Assets/'.$fakeFileName, $destDir.'/'.$destFileName));
        $this->em->persist($pratica);
        $this->em->persist($allegato);
        $this->em->flush();

        return $allegato;
    }

    /**
     * @param $myUser
     * @param $destFileName
     * @param $fakeFileName
     * @return Allegato
     */
    private function createAllegatoWithNoPratica($myUser, $destFileName, $fakeFileName)
    {

        $allegato = new Allegato();
        $allegato->setOwner($myUser);
        $allegato->setFilename($destFileName);
        $allegato->setOriginalFilename($fakeFileName);
        $allegato->setDescription('some description');

        $directoryNamer = $this->container->get('ocsdc.allegati.directory_namer');
        /** @var PropertyMapping $mapping */
        $mapping = $this->container->get('vich_uploader.property_mapping_factory')->fromObject($allegato)[0];

        $destDir = $mapping->getUploadDestination().'/'.$directoryNamer->directoryName($allegato, $mapping);
        mkdir($destDir, 0777, true);
        $this->assertTrue(copy(__DIR__.'/../Assets/'.$fakeFileName, $destDir.'/'.$destFileName));
        $this->em->persist($allegato);
        $this->em->flush();

        return $allegato;
    }
}
