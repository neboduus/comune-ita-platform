<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class ModuloCompilato
 */

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class ModuloCompilato extends Allegato
{
  const TYPE_DEFAULT = 'modulo_compilato';

  /**
   * @ORM\ManyToMany(targetEntity="AppBundle\Entity\Pratica", mappedBy="moduliCompilati")
   * @var ArrayCollection $pratiche
   */
  private $pratiche2;

  /**
   * ModuloCompilato constructor.
   */
  public function __construct()
  {
    parent::__construct();
    $this->type = 'modulo_compilato';
    $this->pratiche2 = new ArrayCollection();
  }

  /**
   * @return ArrayCollection
   */
  public function getPratiche(): Collection
  {
    return $this->pratiche2;
  }

  /**
   * @param Pratica $pratica
   * @return $this
   */
  public function addPratica(Pratica $pratica)
  {
    if (!$this->pratiche2->contains($pratica)) {
      $this->pratiche2->add($pratica);
    }

    return $this;
  }

  /**
   * @param Pratica $pratica
   * @return $this
   */
  public function removePratica(Pratica $pratica)
  {
    if ($this->pratiche2->contains($pratica)) {
      $this->pratiche2->removeElement($pratica);
    }

    return $this;
  }

  public function getType(): string
  {
    return self::TYPE_DEFAULT;
  }

}
