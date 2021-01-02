<?php

namespace App\Tests\Services;

use App\Entity\Allegato;
use App\Entity\Pratica;
use App\Services\DirectoryNamerService;
use App\Tests\Base\AbstractAppTestCase;
use Vich\UploaderBundle\Mapping\PropertyMapping;
use Vich\UploaderBundle\Naming\DirectoryNamerInterface;

/**
 * Class AllegatiDirectoryNamerTest
 */
class DirectoryNamerServiceTest extends AbstractAppTestCase
{

  public function testClassExists()
  {
    $this->assertNotNull(new DirectoryNamerService());
  }

  public function testServiceExists()
  {
    $directoryNamer = static::$container->get('ocsdc.allegati.directory_namer');
    $this->assertTrue($directoryNamer instanceof DirectoryNamerService);
    $this->assertTrue($directoryNamer instanceof DirectoryNamerInterface);
  }

  public function testDirectoryNamerReturnsCPSUserIdIfObjectIsAllegatoClass()
  {

    $this->markTestIncomplete('Check if user is logged in');

    $user = $this->createCPSUser();
    $allegato = new Allegato();
    $allegato->setOwner($user);

    $mockedMappings = $this->getMockBuilder(PropertyMapping::class)->disableOriginalConstructor()->getMock();

    $directoryNamer = static::$container->get('ocsdc.allegati.directory_namer');
    $directoryName = $directoryNamer->directoryName($allegato, $mockedMappings);
    $this->assertEquals($user->getId(), $directoryName);
  }
}
