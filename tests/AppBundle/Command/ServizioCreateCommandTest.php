<?php
namespace Tests\AppBundle\Controller;

use AppBundle\Command\ServizioCreateCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\AppBundle\Base\AbstractAppTestCase;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Helper\HelperSet;

/**
 * Class ServizioCreateCommandTest
 */
class ServizioCreateCommandTest extends AbstractAppTestCase
{
    public function testExecute()
    {

        /*$this->markTestIncomplete("Fix");

        $application = new Application(self::$kernel);
        $application->add(new ServizioCreateCommand());

        $command = $application->find('ocsdc:crea-servizio');

        $commandTester = new CommandTester($command);


        $helper = $command->getHelper('question');
        $helper->setInputStream($this->getInputStream("Test Servizio\n"));
        $helper->setInputStream($this->getInputStream("ocsdc.handlers.test\n"));
        $helper->setInputStream($this->getInputStream("Descrizione\n"));
        $helper->setInputStream($this->getInputStream("Istruzioni\n"));
        $helper->setInputStream($this->getInputStream("\AppBundle\Entity\Test\n"));
        $helper->setInputStream($this->getInputStream("ocsdc.form.flow.test\n"));
        $helper->setInputStream($this->getInputStream(" \n"));

        $commandTester->execute(array('command' => $command->getName()));

        $output = $commandTester->getDisplay();
        $this->assertContains('Servizio creato correttamente', $output);*/
    }

    protected function getInputStream($input)
    {
        $stream = fopen('php://memory', 'r+', false);
        fputs($stream, $input);
        rewind($stream);

        return $stream;
    }
}
