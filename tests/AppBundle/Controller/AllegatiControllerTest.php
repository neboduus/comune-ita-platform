<?php


namespace Tests\AppBundle\Controller;


use AppBundle\Entity\Allegato;
use AppBundle\Entity\ComponenteNucleoFamiliare;
use AppBundle\Entity\Pratica;
use AppBundle\Entity\User;
use AppBundle\Logging\LogConstants;
use AppBundle\Validator\Constraints\ValidMimeType;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
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
    public function testAttachmentCanBeRetrievedIfUserIsOwnerOfThePratica()
    {

        $fakeFileName = 'lenovo-yoga-xp1.pdf';
        $destFileName = md5($fakeFileName).'.pdf';

        $this->setupMockedLogger([
            LogConstants::ALLEGATO_DOWNLOAD_PERMESSO_CPSUSER,
        ]);

        $myUser = $this->createCPSUser(true);
        $allegato = $this->createAllegato('username', 'pass', $myUser, $destFileName, $fakeFileName);

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
        $myUser = $this->createCPSUser(true);
        $allegato = $this->createAllegato($username, $password, $myUser, $destFileName, $fakeFileName);

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


        $myUser = $this->createCPSUser(true);
        $allegato = $this->createAllegato('p', 'p', $myUser, $destFileName, $fakeFileName);

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

        $otherUser = $this->createCPSUser(true);
        $allegato = $this->createAllegato('p', 'p', $otherUser, $destFileName, $fakeFileName);

        $allegatoDownloadUrl = $this->router->generate(
            'allegati_download_cpsuser',
            [
                'allegato' => $allegato->getId(),
            ]
        );

        $myUser = $this->createCPSUser(true);
        $this->clientRequestAsCPSUser($myUser, 'GET', $allegatoDownloadUrl);

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function testCPSUserCanCreateAttachment()
    {
        $user = $this->createCPSUser(true);
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
        $crawler = $this->client->submit($form);
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
        $user = $this->createCPSUser(true);
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
     * @return array
     */
    public function invalidUploadFilesProvider()
    {
        $filenames = array_map(function ($e) {
            return [basename($e)];
        }, glob(__DIR__.'/../Assets/invalid_*'));

        return $filenames;
    }

    private function setupMockedLogger($expectedArgs)
    {
        $mockLogger = $this->getMockLogger();
        $mockLogger->expects($this->exactly(1))
            ->method('info')
            ->with($this->callback(function ($subject) use ($expectedArgs) {
                return in_array($subject, $expectedArgs);
            }));

        static::$kernel->setKernelModifier(function (KernelInterface $kernel) use ($mockLogger) {
            $kernel->getContainer()->set('logger', $mockLogger);
        });
    }

    /**
     * @param $username
     * @param $password
     * @param $myUser
     * @param $destFileName
     * @param $fakeFileName
     * @return Allegato
     */
    private function createAllegato($username, $password, $myUser, $destFileName, $fakeFileName)
    {
        $operatore = $this->createOperatoreUser($username, $password);
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

        return $allegato;
    }
}
