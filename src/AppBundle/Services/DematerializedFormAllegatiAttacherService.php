<?php

namespace AppBundle\Services;

use AppBundle\Entity\Allegato;
use AppBundle\Entity\DematerializedFormAllegatiContainer;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Class DematerializedFormAllegatiAttacherService
 */
class DematerializedFormAllegatiAttacherService
{
    /**
     * @var EntityManagerInterface $em
     */
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @param DematerializedFormAllegatiContainer $pratica
     */
    public function attachAllegati(DematerializedFormAllegatiContainer $pratica)
    {
        $allegatiRepo = $this->em->getRepository('AppBundle:Allegato');
        $allegatiIdList = $pratica->getAllegatiIdList();
        foreach ($allegatiIdList as $id) {
            $allegato = $allegatiRepo->find($id);
            if ($allegato instanceof Allegato) {
                $pratica->addAllegato($allegato);
            }
        }
    }
}
