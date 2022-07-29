<?php

namespace Tests\AppBundle\Entity;

use AppBundle\Entity\Allegato;
use AppBundle\Entity\OperatoreUser;
use AppBundle\Entity\Pratica;
use AppBundle\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Ramsey\Uuid\Uuid;
use PHPUnit\Framework\TestCase;

/**
 * Class OperatoreUserTest
 */
class OperatoreUserTest extends TestCase
{


  /**
   * @test
   */
  public function testCanStoreServicesIds()
  {
    $operatore = new OperatoreUser();
    $operatore->setServiziAbilitati(new ArrayCollection([
      Uuid::uuid4() . '',
      Uuid::uuid4() . '',
    ]));

    $operatore->setUsername('pippo')
      ->setPlainPassword('pippo')
      ->setEmail(md5(rand(0, 1000) . microtime()) . 'some@fake.email')
      ->setNome('a')
      ->setCognome('b')
      ->setEnabled(true);

    $this->assertInstanceOf(Collection::class, $operatore->getServiziAbilitati());
  }
}
