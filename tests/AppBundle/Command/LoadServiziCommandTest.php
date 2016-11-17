<?php
namespace Tests\AppBundle\Controller;

use AppBundle\Command\LoadServiziCommand;
use AppBundle\Entity\ComponenteNucleoFamiliare;
use AppBundle\Entity\Ente;
use AppBundle\Entity\Pratica;
use AppBundle\Entity\Servizio;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\AppBundle\Base\AbstractAppTestCase;

/**
 * Class ServizioCreateCommandTest
 */
class LoadServiziCommandTest extends AbstractAppTestCase
{
    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();
        $this->em->getConnection()->executeQuery('DELETE FROM servizio_enti')->execute();
        $this->em->getConnection()->executeQuery('DELETE FROM ente_asili')->execute();
        $this->cleanDb(ComponenteNucleoFamiliare::class);
        $this->cleanDb(Pratica::class);
        $this->cleanDb(Ente::class);
        $this->cleanDb(Servizio::class);
    }

    /**
     * @test
     */
    public function testExecute()
    {
        //FIXME: questo pesca dal foglio "live" quindi si rompe ad ogni modifica
        $expectedServicesCount = 13;
        $serviziRepo = $this->em->getRepository(Servizio::class);
        $this->assertEquals(0, count($serviziRepo->findAll()));

        $application = new Application(self::$kernel);
        $application->add(new LoadServiziCommand());

        $command = $application->find('ocsdc:carica-servizi');
        $commandTester = new CommandTester($command);

        $commandTester->execute(array(
            'command' => $command->getName(),
        ));

        $this->assertEquals($expectedServicesCount, count($serviziRepo->findAll()));

        $output = $commandTester->getDisplay();
        $this->assertContains('Servizi caricati: '.$expectedServicesCount, $output);
        $this->assertContains('Servizi aggiornati: 0', $output);

        //idempotence
        $commandTester->execute(array(
            'command' => $command->getName(),
        ));
        $this->assertEquals($expectedServicesCount, count($serviziRepo->findAll()));
        $output = $commandTester->getDisplay();
        $this->assertContains('Servizi caricati: 0', $output);
        $this->assertContains('Servizi aggiornati: '.$expectedServicesCount, $output);
    }
}
