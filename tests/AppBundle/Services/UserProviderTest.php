<?php

namespace AppBundle\Services\Test;

use Tests\AppBundle\Base\AppTestCase;

/**
 * Class UserProviderTest
 * @package AppBundle\Services\Test
 */
class UserProviderTest extends AppTestCase
{

    /**
     * @test
     */
    public function testUserProviderAssignsFakeEmailToCPSUserWithNoRegisteredMail()
    {
        $username = "pippo";
        $provider = $this->container->get('sdc_userprovider');
        $user = $provider->loadUserByUsername($username);
        $this->assertContains($user->getId().'', $user->getEmail());
    }
}
