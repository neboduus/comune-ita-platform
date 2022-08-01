<?php

namespace Tests\Services;

use App\Services\KafkaService;
use Tests\App\Base\AbstractAppTestCase;
use Tests\App\Helpers\EntitiesHelper;

class KafkaServiceTest extends AbstractAppTestCase
{
  public function testServiceExists()
  {
    $kafkaService = $this->container->get('ocsdc.kafka_service');
    $this->assertNotNull($kafkaService);
    $this->assertEquals(KafkaService::class, get_class($kafkaService));
  }

  public function testGenerateServiceMessage()
  {
    $kafkaService = $this->container->get('ocsdc.kafka_service');
    $topics = $this->container->getParameter('kafka_topics');

    $ente = EntitiesHelper::createEnte();
    $erogatore = EntitiesHelper::createErogatore($ente);
    $category = EntitiesHelper::createCategoria();
    $servizio = EntitiesHelper::createFormIOService($ente, $erogatore, $category);

    $message = $kafkaService->generateMessage($servizio);

    $this->assertEquals($topics['services'], $message['topic']);
    $this->assertTrue(isset($message['data']) && !empty($message['data']));

    $data = $message['data'];

    $this->assertEquals($servizio->getId(), $data['id']);

  }
}
