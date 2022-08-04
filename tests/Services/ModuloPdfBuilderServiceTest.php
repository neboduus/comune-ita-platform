<?php

namespace Tests\Services;

use Tests\App\Base\AbstractAppTestCase;
use App\Entity\Allegato;
use App\Entity\ComponenteNucleoFamiliare;
use App\Entity\Pratica;
use App\Entity\User;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Translation\TranslatorInterface;
use Knp\Bundle\SnappyBundle\Snappy\LoggableGenerator;
use Symfony\Bundle\TwigBundle\TwigEngine;

class ModuloPdfBuilderServiceTest extends AbstractAppTestCase
{
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
    public function testServiceCanCreateModuloForPratica()
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
        $service->createForPratica($pratica);

    }

    /**
     * @test
     */
    public function testServiceCanCreateResponseForPratica()
    {
        $cpsUser = $this->createCPSUser();
        $pratica = $this->createPratica($cpsUser);

        $translator = $this->setupTranslatorMock();
        $this->container->set('translator', $translator);

        $generator = $this->setupGeneratorMock();
        $this->container->set('knp_snappy.pdf', $generator);

        $service = $this->container->get('ocsdc.modulo_pdf_builder');
        $risposta = $service->createUnsignedResponseForPratica($pratica);
        $this->assertNotNull($risposta);
    }

    protected function setupTranslatorMock()
    {
        $mock = $this->getMockBuilder(TranslatorInterface::class)
                     ->disableOriginalConstructor()
                     ->getMock();

        $mock->expects($this->atLeast(1))
             ->method('trans');

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
             ->method('getOutputFromHtml')
             ->willReturn("La marianna la va in campagna a fare i pdf");

        return $mock;
    }

}
