<?php
namespace Tests\AppBundle\Controller;

use AppBundle\Entity\CPSUser;
use AppBundle\Entity\OperatoreUser;
use AppBundle\Entity\Pratica;
use Symfony\Component\HttpFoundation\Response;
use Tests\AppBundle\Base\AbstractAppTestCase;

/**
 * Class OperatoriControllerTest
 */
class OperatoriControllerTest extends AbstractAppTestCase
{
    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();
        $this->cleanDb(Pratica::class);
        $this->cleanDb(OperatoreUser::class);
        $this->cleanDb(CPSUser::class);
    }

    /**
     * @test
     */
    public function testICannotAccessOperatoriHomePageAsAnonymousUser()
    {
        $operatoriHome = $this->router->generate('operatori_index');
        $this->client->request('GET', $operatoriHome);
        $this->assertEquals(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @test
     */
    public function testICanAccessOperatoriHomePageAsLoggedInOperatore()
    {
        $password = 'pa$$word';
        $username = 'username';

        $this->createOperatoreUser($username, $password);

        $operatoriHome = $this->router->generate('operatori_index');
        $this->client->request('GET', $operatoriHome, array(), array(), array(
            'PHP_AUTH_USER' => $username,
            'PHP_AUTH_PW' => $password,
        ));
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @test
     */
    public function testICanSeeMyPraticheWhenAccessingOperatoriHomePageAsLoggedInOperatore()
    {
        $password = 'pa$$word';
        $username = 'username';

        $operatore = $this->createOperatoreUser($username, $password);
        $altroOperatore = $this->createOperatoreUser($username.'2', $password);
        $user = $this->createCPSUser(true);

        $praticaSubmitted = $this->setupPraticheForUserWithOperatoreAndStatus($user, $operatore, Pratica::STATUS_SUBMITTED);
        $praticaRegistered = $this->setupPraticheForUserWithOperatoreAndStatus($user, $operatore, Pratica::STATUS_REGISTERED);
        $praticaPending = $this->setupPraticheForUserWithOperatoreAndStatus($user, $operatore, Pratica::STATUS_PENDING);
        $praticaSubmittedMaAltroOperatore = $this->setupPraticheForUserWithOperatoreAndStatus($user, $altroOperatore, Pratica::STATUS_SUBMITTED);

        $operatoriHome = $this->router->generate('operatori_index');
        $crawler = $this->client->request('GET', $operatoriHome, array(), array(), array(
            'PHP_AUTH_USER' => $username,
            'PHP_AUTH_PW' => $password,
        ));
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $praticheCount = $crawler->filter('.list')->filter('.pratica')->count();
        $this->assertEquals(3, $praticheCount);

        $expectedPratiche = [
            $praticaSubmitted,
            $praticaRegistered,
            $praticaPending,
        ];

        $unexpectedPratiche = [
            $praticaSubmittedMaAltroOperatore,
        ];

        foreach ($expectedPratiche as $pratica) {
            $this->assertEquals(1, $crawler->filterXPath('//*[@data-pratica="'.$pratica->getId().'"]')->count());
        }

        foreach ($unexpectedPratiche as $pratica) {
            $this->assertEquals(0, $crawler->filterXPath('//*[@data-pratica="'.$pratica->getId().'"]')->count());
        }
    }

    /**
     * @param $username
     * @param $password
     * @return OperatoreUser
     */
    protected function createOperatoreUser($username, $password)
    {
        $um = $this->container->get('fos_user.user_manager');
        $user = new OperatoreUser();
        $user->setUsername($username)
            ->setPlainPassword($password)
            ->setEmail(md5(rand(0, 1000).time()).'some@fake.email')
            ->setNome('a')
            ->setCognome('b')
            ->setEnabled(true);
        $um->updateUser($user);

        return $user;
    }
}
