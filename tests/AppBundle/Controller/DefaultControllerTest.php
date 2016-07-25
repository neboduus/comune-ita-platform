<?php

namespace AppBundle\Tests\Controller;

use AppBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DefaultControllerTest extends WebTestCase
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/');

        $this->assertContains('Hello World', $client->getResponse()->getContent());
    }

    public function testISeeMyName()
    {
        $user = new User();
        $userName = md5(time());
        $user->setName($userName);

        $client = static::createClient();
        $route = $client->getContainer()->get('router')->generate('app_default_servizi');
        $crawler = $client->request('GET', $route, [], [], ['HTTP_REMOTE_USER' => $user->getName()]);

        $this->assertContains($user->getName(), $client->getResponse()->getContent());
    }

    /**
     * @dataProvider protectedRoutesProvider
     */
    public function testIGetAccessDeniedErrorWhenAccessProtectedResourcesAsAnonymouUser($route)
    {
        $client = static::createClient();
        $client->request('GET', $route);
        $this->assertEquals(401, $client->getResponse()->getStatusCode());
    }

    public function protectedRoutesProvider()
    {
        return array(
            array('/servizi')
        );
    }
}
