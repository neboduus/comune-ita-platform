<?php

namespace Tests\AppBundle\Controller;

use AppBundle\Entity\Pratica;
use AppBundle\Entity\Servizio;
use AppBundle\Entity\User;
use Tests\AppBundle\Base\AppTestCase;
use AppBundle\Services\CPSUserProvider;

class PraticaControllerTest extends AppTestCase
{
    /**
     * @var CPSUserProvider
     */
    protected $userProvider;

    public function setUp()
    {
        parent::setUp();
        $this->userProvider = $this->container->get('ocsdc.cps.userprovider');
        $this->cleanDb(Pratica::class);
        $this->cleanDb(Servizio::class);
        $this->cleanDb(User::class);
    }

    public function testAsLoggedUserISeeAllMyPratiche()
    {
        $myUser = $this->createCPSUser(true);
        $this->createPratiche($myUser);

        $otherUser = $this->createCPSUser(true);
        $this->createPratiche($otherUser);

        $repo = $this->em->getRepository("AppBundle:Pratica");
        $myUserPraticheCountAfterInsert = count($repo->findByUser($myUser));

        $otherUserPraticheCountAfterInsert = count($repo->findByUser($otherUser));
        $this->assertGreaterThan(0, $otherUserPraticheCountAfterInsert);

        $crawler = $this->clientRequestAsCPSUser( $myUser, 'GET', '/pratiche/');

        $renderedPraticheCount = $crawler->filterXPath('//*[@data-user="'.$myUser->getId().'"]')->count();
        $this->assertEquals( $myUserPraticheCountAfterInsert, $renderedPraticheCount );

        $renderedOtherUserPraticheCount = $crawler->filterXPath('//*[@data-user="'.$otherUser->getId().'"]')->count();
        $this->assertEquals( 0, $renderedOtherUserPraticheCount );
    }

    public function testAsLoggedUserISeeAllMyPraticheInCorrectOrder()
    {
        $user = $this->createCPSUser(true);

        $expectedStatuses = [
            Pratica::STATUS_PENDING,
            Pratica::STATUS_REGISTERED,
            Pratica::STATUS_COMPLETE,
            Pratica::STATUS_SUBMITTED,
            Pratica::STATUS_DRAFT,
            Pratica::STATUS_CANCELLED
        ];

        shuffle($expectedStatuses);
        foreach($expectedStatuses as $status){
            $this->createPratica( $user, $status );
        }

        $crawler = $this->clientRequestAsCPSUser( $user, 'GET', '/pratiche/');
        $renderedPraticheCount = $crawler->filterXPath('//*[@data-user="'.$user->getId().'"]')->count();
        $this->assertEquals( count($expectedStatuses), $renderedPraticheCount );

        //For now this logic is enough since sorting is based on actual constants values
        //it's quite brittle though
        rsort($expectedStatuses);
        for($i = 0; $i < count($expectedStatuses); $i ++){
            $statusPratica = $crawler->filterXPath('//*[@data-user="'.$user->getId().'"]')->getNode($i)->getAttribute('data-status');
            $this->assertEquals( $statusPratica, $expectedStatuses[$i] );
        }
    }

    /**
     * @param User $user
     * @param bool $howMany
     * @return array
     */
    protected function createPratiche(User $user, $howMany = false)
    {
        $pratiche = array();
        if ( !$howMany )
        {
            $howMany = rand(1, 10);
        }

        for ($i = 0; $i < $howMany; $i++)
        {
            $pratiche []= $this->createPratica( $user );
        }
        return $pratiche;
    }


    /**
     * @param User $user
     * @param bool $status
     * @return Pratica
     */
    protected function createPratica(User $user, $status = false)
    {
        $servizio = new Servizio();
        $servizio->setName('Servizio test pratiche');
        $this->em->persist($servizio);
        $pratica = new Pratica();
        $pratica->setUser($user);
        $pratica->setServizio($servizio);
        $pratica->setName('Pratica per servizio: ' . $servizio->getName() );

        if ($status !== false)
        {
            $pratica->setStatus($status);
        }

        $this->em->persist($pratica);
        $this->em->flush();

        return $pratica;
    }

}
