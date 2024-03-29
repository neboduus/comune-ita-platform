<?php

namespace Tests\Controller;

use App\Entity\Allegato;
use App\Entity\ComponenteNucleoFamiliare;
use App\Entity\CPSUser;
use App\Entity\Ente;
use App\Entity\Pratica;
use App\Entity\Servizio;
use App\Logging\LogConstants;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Tests\App\Base\AbstractAppTestCase;

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
    /*$this->em->getConnection()->executeQuery('DELETE FROM servizio_erogatori')->execute();
    $this->cleanDb(ComponenteNucleoFamiliare::class);
    $this->cleanDb(Allegato::class);
    $this->cleanDb(Pratica::class);
    $this->cleanDb(CPSUser::class);
    $this->cleanDb(Servizio::class);
    $this->cleanDb(Ente::class);*/
  }

  /**
   * @test
   */
  public function testICannotAccessUserDashboardAsAnonymousUser()
  {
    $this->client->followRedirects();
    $this->client->request('GET', $this->router->generate('user_dashboard'));
    $this->assertEquals(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
  }

  /**
   * @test
   */
  public function testICanAccessUserDashboardAsLoggedUser()
  {
    $user = $this->createCPSUser();
    $this->client->followRedirects();
    $this->clientRequestAsCPSUser(
      $user,
      'GET',
      $this->router->generate(
        'login_pat'
      )
    );
    $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
  }

  /**
   * @test
   */
  public function testICannotAccessUserProfileAsAnonymousUser()
  {
    $this->client->followRedirects();
    $this->client->request('GET', $this->router->generate('user_profile'));
    $this->assertEquals(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
  }

  /**
   * @test
   */
  public function testICanAccessUserProfileAsLoggedUser()
  {
    $user = $this->createCPSUser();
    $this->client->followRedirects();
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
    $user = $this->createCPSUser();
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

    $user = $this->createCPSUser();

    $testEmail = rand(1, 10) . '@example.com';
    $testCellulare = rand(1, 10);

    $crawler = $this->clientRequestAsCPSUser($user, 'GET', $this->router->generate('user_profile'));
    $form = $crawler->selectButton('form[save]')->form([
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
