<?php

namespace AppBundle\Services\Test;

use AppBundle\Entity\User;
use AppBundle\Logging\LogConstants;
use Symfony\Bridge\Monolog\Logger;
use Tests\AppBundle\Base\AppTestCase;

/**
 * Class UserProviderTest
 * @package AppBundle\Services\Test
 */
class UserProviderTest extends AppTestCase
{
    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();
        $this->cleanDb(User::class);
    }

    /**
     * @test
     */
    public function testUserProviderAssignsFakeEmailToCPSUserWithNoRegisteredMail()
    {
        $mockLogger = $this->getMockBuilder(Logger::class)->disableOriginalConstructor()->getMock();
        $mockLogger->expects($this->exactly(1))->method('info')->with(LogConstants::CPS_USER_CREATED_WITH_BOGUS_DATA);
        $this->container->set('logger', $mockLogger);

        $username = "pippo";
        $provider = $this->container->get('sdc_userprovider');
        $user = $provider->loadUserByUsername($username);
        $this->assertContains($user->getId().'', $user->getEmail());
    }
}
