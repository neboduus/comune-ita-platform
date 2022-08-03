<?php

namespace App\Services;

use App\Entity\Allegato;
use App\Entity\DematerializedFormAllegatiContainer;
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
        $allegatiRepo = $this->em->getRepository('App\Entity\Allegato');
        $allegatiIdList = $pratica->getAllegatiIdList();
        foreach ($allegatiIdList as $id) {
            $allegato = $allegatiRepo->find($id);
            if ($allegato instanceof Allegato) {
                $pratica->addAllegato($allegato);
            }
        }
    }
}
