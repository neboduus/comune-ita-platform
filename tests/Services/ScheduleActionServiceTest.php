<?php

namespace Tests\Services;

use App\Entity\ScheduledAction;
use App\ScheduledAction\Exception\AlreadyScheduledException;
use App\Services\ScheduleActionService;
use Tests\App\Base\AbstractAppTestCase;

class ScheduleActionServiceTest extends AbstractAppTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->cleanDb(ScheduledAction::class);
    }

    public function testServiceExists()
    {
        $service = $this->container->get('ocsdc.schedule_action_service');
        $this->assertNotNull($service);
        $this->assertEquals(ScheduleActionService::class, get_class($service));
    }

    public function testAppendAction()
    {
        $service = $this->container->get('ocsdc.schedule_action_service');
        $service->appendAction('test', 'test', 'test');

        $items = $this->em->getRepository(ScheduledAction::class)->findAll();
        $this->assertEquals(count($items), 1);
    }

    /**
     * @expectedException \App\ScheduledAction\Exception\AlreadyScheduledException
     */
    public function testCannotReAppendSameAction()
    {
        $service = $this->container->get('ocsdc.schedule_action_service');
        $service->appendAction('test', 'test', 'test');
        $service->appendAction('test', 'test', 'test');
    }

    public function testMarkAsDone()
    {
        $service = $this->container->get('ocsdc.schedule_action_service');
        $service->appendAction('test', 'test', 'test');
        $items = $this->em->getRepository(ScheduledAction::class)->findAll();
        foreach($items as $item){
            $service->markAsDone($item);
        }
        $service->done();

        $items = $this->em->getRepository(ScheduledAction::class)->findAll();
        $this->assertEquals(count($items), 0);
    }

    public function testMarkAsInvalid()
    {
        $this->testMarkAsDone();
    }

    public function testGetActions()
    {
        $service = $this->container->get('ocsdc.schedule_action_service');
        $service->appendAction('test', 'test', 'test');

        $items = $this->em->getRepository(ScheduledAction::class)->findAll();
        $this->assertEquals(count($items), count($service->getActions()));
    }
}
