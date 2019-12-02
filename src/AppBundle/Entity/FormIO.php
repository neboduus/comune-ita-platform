<?php
namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class FormIO
 * @ORM\Entity
 */
class FormIO extends Pratica implements DematerializedFormPratica
{

    /**
     * @ORM\Column(type="json_array", options={"jsonb":true})
     * @var $dematerializedForms array
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

}
