<?php
namespace App\Tests\Entity;


use App\Entity\Allegato;
use App\Entity\Pratica;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\File;

class AllegatoTest extends TestCase {

    public function testDateFieldsGetUpdatedMagically()
    {
        $allegato = new Allegato();
        $originalDate = new \DateTime('last year');
        $allegato->setUpdatedAt($originalDate);
        $mockedFile = $this->getMockBuilder(File::class)
            ->disableOriginalConstructor()
            ->getMock();
        $allegato->setFile($mockedFile);
        $newUpdatedAt = $allegato->getUpdatedAt();
        $this->assertGreaterThan($originalDate, $newUpdatedAt);
    }

    public function testCanAddAndRemovePratica()
    {
        $allegato = new Allegato();
        $pratica = new Pratica();
        $this->assertEquals(0, $allegato->getPratiche()->count());
        $allegato->addPratica($pratica);
        $this->assertEquals(1, $allegato->getPratiche()->count());
        $allegato->addPratica($pratica);
        $this->assertEquals(1, $allegato->getPratiche()->count());
        $allegato->removePratica($pratica);
        $this->assertEquals(0, $allegato->getPratiche()->count());

    }
}
