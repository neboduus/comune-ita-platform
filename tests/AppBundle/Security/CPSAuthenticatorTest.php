<?php

namespace Tests\AppBundle\Security;

use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\VarDumper\VarDumper;
use Tests\AppBundle\Base\AbstractAppTestCase;

/**
 * Class CPSAuthenticatorTestAbstract
 *
 * @package Tests\AppBundle\Security
 */
class CPSAuthenticatorTest extends AbstractAppTestCase
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
        $authenticator->getUser([], $wrongProvider);
    }
}
