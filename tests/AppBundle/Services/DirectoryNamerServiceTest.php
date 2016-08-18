<?php
namespace Tests\AppBundle\Services;

use AppBundle\Entity\Allegato;
use AppBundle\Entity\Pratica;
use AppBundle\Services\DirectoryNamerService;
use Tests\AppBundle\Base\AbstractAppTestCase;
use Vich\UploaderBundle\Mapping\PropertyMapping;
use Vich\UploaderBundle\Naming\DirectoryNamerInterface;

/**
 * Class AllegatiDirectoryNamerTest
 */
class DirectoryNamerServiceTest extends AbstractAppTestCase
{
    public function setUp()
    {
        parent::setUp();
    }

    public function testClassExists()
    {
        $this->assertNotNull(new DirectoryNamerService());
    }

    public function testServiceExists()
    {
        $directoryNamer = $this->container->get('ocsdc.allegati.directory_namer');
        $this->assertTrue($directoryNamer instanceof DirectoryNamerService);
        $this->assertTrue($directoryNamer instanceof DirectoryNamerInterface);
    }

    public function testDirectoryNamerReturnsPraticaIdIfObjectIsAllegatoClass()
    {
        $pratica = new Pratica();
        $allegato = new Allegato();
        $allegato->setPratica($pratica);

        $mockedMappings = $this->getMockBuilder(PropertyMapping::class)->disableOriginalConstructor()->getMock();

        $directoryNamer = $this->container->get('ocsdc.allegati.directory_namer');
        $directoryName = $directoryNamer->directoryName($allegato,$mockedMappings);
        $this->assertEquals($pratica->getId(), $directoryName);
    }
}
