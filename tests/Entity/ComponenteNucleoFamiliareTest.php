<?php


namespace App\Tests\Entity;


use App\Entity\ComponenteNucleoFamiliare;
use App\Entity\Pratica;
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
