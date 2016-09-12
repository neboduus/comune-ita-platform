<?php


namespace Tests\AppBundle\Entity;
use AppBundle\Entity\Allegato;
use AppBundle\Entity\Pratica;

/**
 * Class PraticaTest
 */
class PraticaTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function testCanSetAndGetData()
    {
        $pratica = new Pratica();
        $this->assertNull($pratica->getData());
        $data = ['a'];
        $this->assertEquals($data,$pratica->setData($data)->getData());
    }

    /**
     * @test
     */
    public function testAddingAllegatoAlsoAddsPraticaToAllegato()
    {
        $pratica = new Pratica();
        $allegato = new Allegato();
        $this->assertEquals(0, $pratica->getAllegati()->count());
        $this->assertEquals(0, $allegato->getPratiche()->count());
        $pratica->addAllegato($allegato);
        $this->assertTrue($pratica->getAllegati()->contains($allegato));
        $this->assertTrue($allegato->getPratiche()->contains($pratica));
    }

    /**
     * @test
     */
    public function testRemovingAllegatoAlsoRemovesPraticaFromAllegato()
    {
        $pratica = new Pratica();
        $allegato = new Allegato();
        $this->assertEquals(0, $pratica->getAllegati()->count());
        $this->assertEquals(0, $allegato->getPratiche()->count());
        $pratica->addAllegato($allegato);

        $pratica->removeAllegato($allegato);
        $this->assertTrue(!$pratica->getAllegati()->contains($allegato));
        $this->assertTrue(!$allegato->getPratiche()->contains($pratica));
    }

}
