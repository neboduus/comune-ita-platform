<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Class FormIO
 * @ORM\Entity
 * @Gedmo\Loggable
 */
class FormIO extends Pratica implements DematerializedFormPratica
{

  /**
   * @ORM\Column(type="json_array", options={"jsonb":true})
   * @var $dematerializedForms array
   * @Gedmo\Versioned
   */
  protected $dematerializedForms;

  /**
   * SciaPraticaEdilizia constructor.
   */
  public function __construct()
  {
    parent::__construct();
    $this->type = self::TYPE_FORMIO;
    $this->dematerializedForms = [];
  }

  /**
   * @return array
   */
  public function getDematerializedForms()
  {
    return $this->dematerializedForms;
  }

  /**
   * @param [] $dematerializedForms
   * @return $this
   */
  public function setDematerializedForms($dematerializedForms)
  {
    $this->dematerializedForms = $dematerializedForms;

    return $this;
  }

  public function getType(): string
  {
    return Pratica::TYPE_FORMIO;
  }

}
