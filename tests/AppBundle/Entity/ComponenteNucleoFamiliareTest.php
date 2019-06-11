<?php


namespace Tests\AppBundle\Entity;


use AppBundle\Entity\ComponenteNucleoFamiliare;
use AppBundle\Entity\Pratica;
use PHPUnit\Framework\TestCase;

class ComponenteNucleoFamiliareTest extends TestCase
{
    public function testComponenteCanBeBoundToPratica()
    {
        $componente = new ComponenteNucleoFamiliare();
        $this->assertNotNull($componente->getId());
        $this->assertNull($componente->getPratica());
        $pratica = new Pratica();
        $this->assertEquals($pratica, $componente->setPratica($pratica)->getPratica());
    }
}
