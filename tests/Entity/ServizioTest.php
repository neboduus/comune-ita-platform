<?php

namespace Tests\Entity;

use App\Entity\Servizio;
use Doctrine\Common\Collections\Collection;
use Ramsey\Uuid\Uuid;
use Tests\Helpers\EntitiesHelper;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;

/** @covers \App\Entity\Servizio */
class ServizioTest extends TestCase
{
  public function testFormIOServiceCreate()
  {
    /*$mockedEntityManager = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
      ->setMethods(array('persist', 'flush'))
      ->disableOriginalConstructor()
      ->getMock();*/

    $ente = EntitiesHelper::createEnte();
    $erogatore = EntitiesHelper::createErogatore($ente);
    $category = EntitiesHelper::createCategoria();
    $servizio = EntitiesHelper::createFormIOService($ente, $erogatore, $category);

    $this->assertTrue(Uuid::isValid($servizio->getId()));
    $this->assertEquals('Servizio di test', $servizio->getName());
    $this->assertEquals($ente->getId(), $servizio->getEnte()->getId());
    $this->assertEquals($category->getId(), $servizio->getTopics()->getId());
    $this->assertEquals(1, $servizio->getErogatori()->count());
    $this->assertEquals($erogatore->getId(), $servizio->getErogatori()->first()->getId());
    $this->assertEquals('\App\Entity\FormIO', $servizio->getPraticaFCQN());
    $this->assertEquals('ocsdc.form.flow.formio', $servizio->getPraticaFlowServiceName());

    // Defaults of service
    $this->assertIsArray($servizio->getFlowSteps());
    $this->assertInstanceOf(Collection::class, $servizio->getFeedbackMessages());
    $this->assertInstanceOf(Collection::class, $servizio->getRecipients());
    $this->assertInstanceOf(Collection::class, $servizio->getGeographicAreas());

    $this->assertIsArray($servizio->getCoverage());

    $this->assertEquals(Servizio::STATUS_AVAILABLE, $servizio->getStatus());
    $this->assertEquals(Servizio::ACCESS_LEVEL_SPID_L2, $servizio->getAccessLevel());
    $this->assertFalse($servizio->isLoginSuggested());
    $this->assertFalse($servizio->isProtocolRequired());
    $this->assertFalse($servizio->isAllowReopening());
    $this->assertTrue($servizio->isAllowWithdraw());
    $this->assertTrue($servizio->isAllowIntegrationRequest());


    $this->assertEquals('Service lorem ipsum', $servizio->getDescription());
    $this->assertEquals('Service lorem ipsum', $servizio->getHowto());
    $this->assertEquals('Service lorem ipsum', $servizio->getWho());
    $this->assertEquals('Service lorem ipsum', $servizio->getSpecialCases());
    $this->assertEquals('Service lorem ipsum', $servizio->getMoreInfo());
    $this->assertEquals('Service lorem ipsum', $servizio->getCompilationInfo());
    $this->assertEquals('Service lorem ipsum', $servizio->getFinalIndications());

  }

  public function testServiceHasSameContentsOfServiceGroupIfShared()
  {
    $ente = EntitiesHelper::createEnte();
    $erogatore = EntitiesHelper::createErogatore($ente);
    $category = EntitiesHelper::createCategoria();
    $servizio = EntitiesHelper::createFormIOService($ente, $erogatore, $category);

    $serviceGroup = EntitiesHelper::createServiceGroup();
    $servizio->setServiceGroup($serviceGroup);
    $servizio->setSharedWithGroup(true);

    $this->assertEquals($serviceGroup->getDescription(), $servizio->getDescription());
    $this->assertEquals($serviceGroup->getHowto(), $servizio->getHowto());
    $this->assertEquals($serviceGroup->getWho(), $servizio->getWho());
    $this->assertEquals($serviceGroup->getSpecialCases(), $servizio->getSpecialCases());
    $this->assertEquals($serviceGroup->getMoreInfo(), $servizio->getMoreInfo());
    $this->assertEquals($serviceGroup->getTopics(), $servizio->getTopics());

  }

}
