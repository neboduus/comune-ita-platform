<?php
namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * Interface DematerializedFormPratica
 *
 * Used to identify Pratica that has the DematerializedForm field
 * The flow puts Allegato ids inside that field as string and there's need
 * for an additional step to actually attach Allegato to Pratica
 */
interface DematerializedFormPratica
{
    /**
     * @return array
     */
    public function getDematerializedForms();

    /**
     * @param array $dematerializedForms
     */
    public function setDematerializedForms($dematerializedForms);

}
