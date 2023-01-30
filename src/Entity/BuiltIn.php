<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class BuiltIn
 * @ORM\Entity
 */
class BuiltIn extends FormIO
{

  /**
   * BuiltIn constructor.
   */
  public function __construct()
  {
    parent::__construct();
    $this->type = self::TYPE_BUILTIN;
  }

  public function getType(): string
  {
    return Pratica::TYPE_BUILTIN;
  }

}
