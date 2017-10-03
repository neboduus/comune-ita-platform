<?php
/**
 * Created by PhpStorm.
 * User: marco
 * Date: 24/08/17
 * Time: 11.01
 */

namespace Tests\AppBundle\Entity;


use AppBundle\Entity\Pratica;
use Tests\AppBundle\Base\AbstractAppTestCase;

class PraticaRepositoryTest extends AbstractAppTestCase
{
    public function testRepositoryRetrievesRelatedPraticas()
    {
        $repo = $this->em->getRepository('AppBundle:Pratica');
        $user = $this->createCPSUser();

        $this->setupPraticaScia($user->getCodiceFiscale());
        $this->setupPraticaScia($user->getCodiceFiscale());

        $praticas = $repo->findRelatedPraticaForUser($user);

        $this->assertEquals(2, count($praticas));
        foreach($praticas as $p){
            $this->assertInstanceOf(Pratica::class, $p);
        }
    }
}
