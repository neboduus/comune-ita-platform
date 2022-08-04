<?php
namespace Tests\Services;

use App\Entity\Allegato;
use App\Entity\Pratica;
use App\Services\DirectoryNamerService;
use Tests\App\Base\AbstractAppTestCase;
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

    public function testDirectoryNamerReturnsCPSUserIdIfObjectIsAllegatoClass()
    {
        $user = $this->createCPSUser();
        $allegato = new Allegato();
        $allegato->setOwner($user);

        $mockedMappings = $this->getMockBuilder(PropertyMapping::class)->disableOriginalConstructor()->getMock();

        $directoryNamer = $this->container->get('ocsdc.allegati.directory_namer');
        $directoryName = $directoryNamer->directoryName($allegato,$mockedMappings);
        $this->assertEquals($user->getId(), $directoryName);
    }
}
