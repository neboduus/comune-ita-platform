<?php

namespace App\Tests\Entity;

use App\Entity\Ente;
use App\Entity\Erogatore;
use App\Entity\Pratica;
use App\Tests\Base\AbstractAppTestCase;

/**
 * Class EnteTest
 */
class EnteTest extends AbstractAppTestCase
{
  /**
   * @test
   */
  public function testEnteCanHaveAName()
  {
    $ente = new Ente();
    $name = "Consorzio della tristezza rodigina";
    $ente->setName($name);
    $this->assertEquals($name, $ente->getName());
  }
}
