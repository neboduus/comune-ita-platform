<?php
namespace Tests\AppBundle\Entity;

use AppBundle\Entity\Ente;
use AppBundle\Entity\Erogatore;
use AppBundle\Entity\Pratica;
use Tests\AppBundle\Base\AbstractAppTestCase;

/**
 * Class EnteTest
 */
class EnteTest extends AbstractAppTestCase
{

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();
        $this->em->getConnection()->executeQuery('DELETE FROM servizio_erogatori')->execute();
        $this->em->getConnection()->executeQuery('DELETE FROM erogatore_ente')->execute();
        $this->em->getConnection()->executeQuery('DELETE FROM ente_asili')->execute();
        $this->cleanDb(Pratica::class);
        $this->cleanDb(Ente::class);
        $this->cleanDb(Erogatore::class);
    }

    public function testEnteReturnsAllTheErogatoriThatIncludeIt()
    {
        $enti = $this->createEnti();
        $erogatore1 = $this->createErogatoreWithEnti($enti);
        $erogatore2 = $this->createErogatoreWithEnti($enti);
        $erogatore3 = $this->createErogatoreWithEnti([$enti[0]]);

        $ente = $this->em->getRepository(Ente::class)->find($enti[0]->getId());
        $this->em->refresh($ente);
        $erogatori = $ente->getErogatori();
        $this->assertEquals(3, $erogatori->count());
    }
}
