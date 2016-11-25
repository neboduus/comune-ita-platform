<?php

namespace Tests\AppBundle\Services;

use Tests\AppBundle\Base\AbstractAppTestCase;
use AppBundle\Entity\Allegato;
use AppBundle\Entity\ComponenteNucleoFamiliare;
use AppBundle\Entity\Pratica;
use AppBundle\Entity\User;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Translation\TranslatorInterface;
use Knp\Bundle\SnappyBundle\Snappy\LoggableGenerator;
use Symfony\Bundle\TwigBundle\TwigEngine;

class ModuloPdfBuilderServiceTest extends AbstractAppTestCase
{
    /**
     * @test
     */
    public function setUp()
    {
        parent::setUp();
        $this->cleanDb(ComponenteNucleoFamiliare::class);
        $this->cleanDb(Allegato::class);
        $this->cleanDb(Pratica::class);
        $this->cleanDb(User::class);

    }

    /**
     * @test
     */
    public function testModuloPdfBuilderService()
    {
        $cpsUser = $this->createCPSUser();
        $pratica = $this->createPratica($cpsUser);

        $translator = $this->setupTranslatorMock();
        $this->container->set('translator', $translator);

        $templating = $this->setupTemplatingMock();
        $this->container->set('templating', $templating);

        $generator = $this->setupGeneratorMock();
        $this->container->set('knp_snappy.pdf', $generator);

        $service = $this->container->get('ocsdc.modulo_pdf_builder');
        $service->createForPratica($pratica, $cpsUser);

    }

    protected function setupTranslatorMock()
    {
        $mock = $this->getMockBuilder(TranslatorInterface::class)
                     ->disableOriginalConstructor()
                     ->getMock();

        $mock->expects($this->once())
             ->method('trans')->with('pratica.modulo.descrizione');

        return $mock;
    }

    protected function setupTemplatingMock()
    {
        $mock = $this->getMockBuilder(TwigEngine::class)
                     ->disableOriginalConstructor()
                     ->getMock();

        $mock->expects($this->atLeast(1))
             ->method('render');

        return $mock;
    }

    protected function setupGeneratorMock()
    {
        $mock = $this->getMockBuilder(LoggableGenerator::class)
                     ->disableOriginalConstructor()
                     ->getMock();

        $mock->expects($this->once())
             ->method('getOutputFromHtml');

        return $mock;
    }

}
