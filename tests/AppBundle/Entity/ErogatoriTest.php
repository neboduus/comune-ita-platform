<?php
namespace Tests\AppBundle\Entity;

use AppBundle\Entity\Ente;
use AppBundle\Entity\Erogatore;
use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\TestCase;

/**
 * Class ErogatoriTest
 */
class ErogatoriTest extends TestCase
{
    /**
     * @test
     */
    public function testErogatoreHasManyEnte()
    {
        $erogatore = new Erogatore();
        $this->assertInstanceOf(Collection::class, $erogatore->getEnti());
    }

    /**
     * @test
     */
    public function testErogatoreHasManyServizio()
    {
        $erogatore = new Erogatore();
        $this->assertInstanceOf(Collection::class, $erogatore->getServizi());
    }

    /**
     * @test
     */
    public function testEnteCanBeAddedToErogatore()
    {
        $erogatore = new Erogatore();
        $ente = new Ente();
        $this->assertEquals(0, $erogatore->getEnti()->count());
        $erogatore->addEnte($ente);
        $this->assertEquals(1, $erogatore->getEnti()->count());
    }

    /**
     * @test
     */
    public function testEnteCanBeRemovedFromErogatore()
    {
        $erogatore = new Erogatore();
        $ente = new Ente();
        $this->assertEquals(0, $erogatore->getEnti()->count());
        $erogatore->addEnte($ente);
        $this->assertEquals(1, $erogatore->getEnti()->count());
        $erogatore->removeEnte($ente);
        $this->assertEquals(0, $erogatore->getEnti()->count());
    }

    /**
     * @test
     */
    public function testErogatoreCanHaveAName()
    {
        $erogatore = new Erogatore();
        $name = "Consorzio della tristezza rodigina";
        $erogatore->setName($name);
        $this->assertEquals($name, $erogatore->getName());
    }
}
