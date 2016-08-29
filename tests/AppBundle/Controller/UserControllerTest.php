<?php
namespace Tests\AppBundle\Controller;

use AppBundle\Entity\Allegato;
use AppBundle\Entity\ComponenteNucleoFamiliare;
use AppBundle\Entity\CPSUser;
use AppBundle\Entity\Pratica;
use AppBundle\Logging\LogConstants;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Tests\AppBundle\Base\AbstractAppTestCase;

/**
 * Class UserControllerTest
 */
class UserControllerTest extends AbstractAppTestCase
{
    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();
        $this->userProvider = $this->container->get('ocsdc.cps.userprovider');
        $this->cleanDb(ComponenteNucleoFamiliare::class);
        $this->cleanDb(Allegato::class);
        $this->cleanDb(Pratica::class);
        $this->cleanDb(CPSUser::class);
    }

    /**
     * @test
     */
    public function testICannotAccessUserDashboardAsAnonymousUser()
    {
        $this->client->request('GET', $this->router->generate('user_dashboard'));
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @test
     */
    public function testICanAccessUserDashboardAsLoggedUser()
    {
        $user = $this->createCPSUser(true);
        $this->clientRequestAsCPSUser(
            $user,
            'GET',
            $this->router->generate(
                'user_dashboard'
            )
        );
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @test
     */
    public function testICanSeeMyPraticheWhenAccessingUserDashboardAsLoggedUser()
    {
        $user = $this->createCPSUser(true);
        $this->setupPraticheForUser($user);

        $crawler = $this->clientRequestAsCPSUser($user, 'GET', $this->router->generate('user_dashboard'));

        $repo = $this->em->getRepository("AppBundle:Pratica");
        $praticheDraft = $repo->findBy(
            [
                'user' => $user,
                'status' => Pratica::STATUS_DRAFT
            ]
        );

        $pratichePending = $repo->findBy(
            [
                'user' => $user,
                'status' => [
                    Pratica::STATUS_PENDING,
                    Pratica::STATUS_SUBMITTED,
                    Pratica::STATUS_REGISTERED,
                ]
            ]
        );

        $praticheCompleted = $repo->findBy(
            [
                'user' => $user,
                'status' => Pratica::STATUS_COMPLETE
            ]
        );

        $praticheCancelled = $repo->findBy(
            [
                'user' => $user,
                'status' => Pratica::STATUS_CANCELLED
            ]
        );

        $praticheCount = $crawler->filter('.list.draft')->filter('.pratica')->count();
        $this->assertEquals(count($praticheDraft), $praticheCount);

        $praticheCount = $crawler->filter('.list.pending')->filter('.pratica')->count();
        $this->assertEquals(count($pratichePending), $praticheCount);

        $praticheCount = $crawler->filter('.list.completed')->filter('.pratica')->count();
        $this->assertEquals(count($praticheCompleted), $praticheCount);

        $praticheCount = $crawler->filter('.list.cancelled')->filter('.pratica')->count();
        $this->assertEquals(count($praticheCancelled), $praticheCount);

    }

    /**
     * @test
     */
    public function testICannotAccessUserProfileAsAnonymousUser()
    {
        $this->client->request('GET', $this->router->generate('user_profile'));
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @test
     */
    public function testICanAccessUserProfileAsLoggedUser()
    {
        $user = $this->createCPSUser(true);
        $this->client->request('GET', $this->router->generate('user_profile'));
        $this->clientRequestAsCPSUser(
            $user,
            'GET',
            $this->router->generate(
                'user_dashboard'
            )
        );
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testUseCPSValuesAsDefaultContactInfoWhenCreatingUser()
    {
        $user = $this->createCPSUser(true);
        $this->assertEquals($user->getCellulare(), $user->getCellulareContatto());
        $this->assertEquals($user->getEmail(), $user->getEmailContatto());
    }

    public function testICanChangeMyContactInfoAsLoggedUser()
    {
        $mockLogger = $this->getMockLogger();
        $mockLogger->expects($this->exactly(1))
                   ->method('info')
                   ->with(LogConstants::USER_HAS_CHANGED_CONTACTS_INFO);
        static::$kernel->setKernelModifier(function (KernelInterface $kernel) use ($mockLogger) {
            $kernel->getContainer()->set('logger', $mockLogger);
        });

        $user = $this->createCPSUser(true);

        $testEmail = rand(1, 10).'@example.com';
        $testCellulare = rand(1, 10);

        $crawler = $this->clientRequestAsCPSUser($user, 'GET', $this->router->generate('user_profile'));
        $form = $crawler->selectButton($this->translator->trans('user.profile.salva_informazioni_contatto'))->form([
            'form[email_contatto]' => $testEmail,
            'form[cellulare_contatto]' => $testCellulare,
        ]);
        $this->client->submit($form);
        $this->assertEquals(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode(), "Unexpected HTTP status code");

        $this->em->refresh($user);
        $this->assertEquals($testCellulare, $user->getCellulareContatto());
        $this->assertEquals($testEmail, $user->getEmailContatto());
    }
}
