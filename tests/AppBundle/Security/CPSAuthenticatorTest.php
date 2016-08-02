<?php

namespace Tests\AppBundle\Security;

use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\VarDumper\VarDumper;
use Tests\AppBundle\Base\AppTestCase;

/**
 * Class CPSAuthenticatorTest
 *
 * @package Tests\AppBundle\Security
 */
class CPSAuthenticatorTest extends AppTestCase
{
    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();
    }

    public function testIGetAnExceptionIfUserProviderIsNotACPSUserProvider()
    {
        $this->expectException(\InvalidArgumentException::class);

        $authenticator = $this->container->get('ocsdc.cps.token_authenticator');
        $wrongProvider = $mockLogger = $this->getMockBuilder(UserProviderInterface::class)->getMock();
        VarDumper::dump($wrongProvider);
        $authenticator->getUser([], $wrongProvider);
    }
}
