<?php
namespace Tests\AppBundle\Controller;

use AppBundle\Command\ServizioCreateCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\AppBundle\Base\AbstractAppTestCase;

/**
 * Class ServizioCreateCommandTest
 */
class ServizioCreateCommandTest extends AbstractAppTestCase
{
    public function testExecute()
    {
        $application = new Application(self::$kernel);

        $application->add(new ServizioCreateCommand());

        $command = $application->find('ocsdc:crea-servizio');
        $commandTester = new CommandTester($command);

        $slug = 'iscrizione_nido';
        $name = 'Iscrizione Asili Nido';
        $commandTester->execute(array(
            'command' => $command->getName(),
            'slug' => $slug,
            'name' => $name,
        ));

        $output = $commandTester->getDisplay();
        $this->assertContains('Servizio: '.$slug.' creato, manca il flusso', $output);
    }
}
