<?php
namespace Tests\AppBundle\Services;

use AppBundle\Entity\Allegato;
use AppBundle\Entity\AsiloNido;
use AppBundle\Entity\ComponenteNucleoFamiliare;
use AppBundle\Entity\Ente;
use AppBundle\Entity\OperatoreUser;
use AppBundle\Entity\Pratica;
use AppBundle\Entity\Servizio;
use AppBundle\Entity\User;
use AppBundle\Services\MessagesAdapterService;
use Doctrine\Common\Collections\ArrayCollection;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use Tests\AppBundle\Base\AbstractAppTestCase;

/**
 * Class MessagesAdapterServiceTest
 */
class MessagesAdapterServiceTest extends AbstractAppTestCase
{
    /**
     * @test
     */
    public function setUp()
    {
        parent::setUp();
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
    public function itExists()
    {
        $this->assertNotNull($this->container->get('ocsdc.messages_adapter'));
    }

    /**
     * @test
     */
    public function testServiceGetsThreadsForUser()
    {
        $user = $this->createCPSUser(true, true);
        $mockedGuzzle = $this->getMockGuzzleClient([$this->getMockedMessagesBackendThreadResponseForUser($user)]);
        $mockedLogger = $this->getMockLogger();

        $service = new MessagesAdapterService($mockedGuzzle, $mockedLogger);
        $threads = $service->getThreadsForUser($user);
        foreach ($threads as $thread) {
            $this->assertTrue($this->checkThreadObjectIsCorrect($thread));
        }
    }
    /**
     * @test
     */
    public function testServiceGetsThreadForUserEnteAndServizio()
    {
        $user = $this->createCPSUser(true, true);
        $ente = $this->createEnti()[0];
        $servizio = $this->createServizioWithEnte($ente, 'servizio_a', 'aa', 'bb');
        $operatore = $this->createOperatoreUser('pippo', 'pippi', $ente, new ArrayCollection([$servizio->getId()]));
        $mockedGuzzle = $this->getMockGuzzleClient([$this->getMockedMessagesBackendThreadResponseForUserEnteAndService($user, $operatore)]);
        $mockedLogger = $this->getMockLogger();

        $enteMock = $this->getMockBuilder(Ente::class)->getMock();
        $enteMock->expects($this->once())->method('getOperatori')
            ->willReturn(new ArrayCollection([$operatore]));

        $service = new MessagesAdapterService($mockedGuzzle, $mockedLogger);
        $thread = $service->getThreadsForUserEnteAndService($user, $enteMock, $servizio);

        $this->assertTrue($this->checkThreadObjectIsCorrect($thread[0]));
    }

    /**
     * @test
     */
    public function testServiceCreatesThreadForUserEnteAndServizio()
    {
        $user = $this->createCPSUser(true, true);
        $ente = $this->createEnti()[0];
        $servizio = $this->createServizioWithEnte($ente, 'servizio_a', 'aa', 'bb');
        $operatore = $this->createOperatoreUser('pippo', 'pippi', $ente, new ArrayCollection([$servizio->getId()]));
        $mockedGuzzle = $this->getMockGuzzleClient([$this->getMockedMessagesBackendThreadResponseForUserEnteAndService($user, $operatore)]);
        $mockedLogger = $this->getMockLogger();

        $enteMock = $this->getMockBuilder(Ente::class)->getMock();
        $enteMock->expects($this->once())->method('getOperatori')
            ->willReturn(new ArrayCollection([$operatore]));

        $service = new MessagesAdapterService($mockedGuzzle, $mockedLogger);
        $thread = $service->createThreadsForUserEnteAndService($user, $enteMock, $servizio);

        $this->assertTrue($this->checkThreadObjectIsCorrect($thread[0]));
    }

    /**
     * @test
     */
    public function testServiceReturnsNullAndLogsIfRemoteServiceIsDown()
    {
        $mockedGuzzle = $this->getMockGuzzleClient([new RequestException("Service down", new Request('GET', 'test'))]);
        $mockedLogger = $this->getMockLogger();
        $mockedLogger->expects($this->once())->method('error')
            ->with(MessagesAdapterService::REMOTE_ENDPOINT_UNAVAILABLE_EXCEPTION_MESSAGE);
        $user = $this->createCPSUser(true, true);
        $ente = $this->createEnti()[0];
        $servizio = $this->createServizioWithEnte($ente, 'servizio_a', 'aa', 'bb');
        $operatore = $this->createOperatoreUser('pippo', 'pippi', $ente, new ArrayCollection([$servizio->getId()]));
        $enteMock = $this->getMockBuilder(Ente::class)->getMock();

        $enteMock->expects($this->once())->method('getOperatori')
            ->willReturn(new ArrayCollection([$operatore]));

        $service = new MessagesAdapterService($mockedGuzzle, $mockedLogger);
        $threads = $service->getThreadsForUserEnteAndService($user, $enteMock, $servizio);
        $this->assertNull($threads);
    }

    private function checkThreadObjectIsCorrect($t)
    {
        $splitted = preg_split('/~/', $t->threadId);
        $this->assertEquals(2, count($splitted));
        foreach ($splitted as $id) {
            $this->assertRegExp('/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i', $id);
        }

        return true;
    }
}
