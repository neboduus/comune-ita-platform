<?php

namespace AppBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\ResultSetMapping;

/**
 * PraticaRepository
 *
 * This class was generated by the PhpStorm "Php Annotations" Plugin. Add your own custom
 * repository methods below.
 */
class PraticaRepository extends EntityRepository
{
    public function findRelatedPraticaForUser(CPSUser $user)
    {
        $sql = 'SELECT id from pratica where (related_cfs)::jsonb @> \'"' . $user->getCodiceFiscale() . '"\'';


        $stmt = $this->getEntityManager()->getConnection()->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll();

        $ids = [];

        foreach ($result as $id) {
            $ids[] = $id['id'];
        }

        return $this->findById($ids);
    }

    public function findDraftPraticaForUser(CPSUser $user)
    {
        return $this->findBy(
            [
                'user' => $user,
                'status' => Pratica::STATUS_DRAFT,
            ],
            [
                'creationTime' => 'DESC',
            ]
        );
    }

    public function findPendingPraticaForUser(CPSUser $user)
    {
        return $this->findBy(
            [
                'user' => $user,
                'status' => [
                    Pratica::STATUS_SUBMITTED,
                    Pratica::STATUS_REGISTERED,
                    Pratica::STATUS_PENDING,
                    Pratica::STATUS_PENDING_AFTER_INTEGRATION,
                    Pratica::STATUS_COMPLETE_WAITALLEGATIOPERATORE,
                    Pratica::STATUS_REQUEST_INTEGRATION,
                    Pratica::STATUS_REGISTERED_AFTER_INTEGRATION,
                    Pratica::STATUS_CANCELLED_WAITALLEGATIOPERATORE,
                ],
            ],
            [
                'creationTime' => 'DESC',
            ]
        );
    }

    public function findProcessingPraticaForUser(CPSUser $user)
    {
        return $this->findBy(
            [
                'user' => $user,
                'status' => Pratica::STATUS_PROCESSING,
            ],
            [
                'creationTime' => 'DESC',
            ]
        );
    }

    public function findCompletePraticaForUser(CPSUser $user)
    {
        return $this->findBy(
            [
                'user' => $user,
                'status' => Pratica::STATUS_COMPLETE,
            ],
            [
                'creationTime' => 'DESC',
            ]
        );
    }

    public function findCancelledPraticaForUser(CPSUser $user)
    {
        return $this->findBy(
            [
                'user' => $user,
                'status' => [
                    Pratica::STATUS_CANCELLED,
                ],
            ],
            [
                'creationTime' => 'DESC',
            ]
        );
    }

    public function findDraftForIntegrationPraticaForUser(CPSUser $user)
    {
        return $this->findBy(
            [
                'user' => $user,
                'status' => [
                    Pratica::STATUS_DRAFT_FOR_INTEGRATION,
                    Pratica::STATUS_SUBMITTED_AFTER_INTEGRATION,
                ],
            ],
            [
                'creationTime' => 'DESC',
            ]
        );
    }

    public function findPraticheAssignedToOperatore(OperatoreUser $user)
    {
        $ente = $user->getEnte();
        return $this->findBy(
            [
                'operatore' => $user,
                'erogatore' => $ente->getErogatori()->toArray(),
                'status' => [
                    Pratica::STATUS_PENDING,
                    Pratica::STATUS_PENDING_AFTER_INTEGRATION,
                    Pratica::STATUS_PROCESSING,
                    Pratica::STATUS_SUBMITTED,
                    Pratica::STATUS_REGISTERED,
                ],
            ]
        );
    }

    public function findPraticheByEnte(Ente $ente)
    {
        return $this->findBy(
            [
                'erogatore' => $ente->getErogatori()->toArray(),
            ]
        );
    }

    public function findPraticheUnAssignedByEnte(Ente $ente)
    {
        return $this->findBy(
            [
                'operatore' => null,
                'erogatore' => $ente->getErogatori()->toArray(),
                'status' => [
                    Pratica::STATUS_PENDING,
                    Pratica::STATUS_SUBMITTED,
                    Pratica::STATUS_REGISTERED,
                    Pratica::STATUS_PROCESSING,
                ],
            ]
        );
    }

    public function findPraticheCompletedByOperatore(OperatoreUser $user)
    {
        $ente = $user->getEnte();
        return $this->findBy(
            [
                'operatore' => $user,
                'erogatore' => $ente->getErogatori()->toArray(),
                'status' => [
                    Pratica::STATUS_COMPLETE,
                    Pratica::STATUS_COMPLETE_WAITALLEGATIOPERATORE,
                    Pratica::STATUS_CANCELLED_WAITALLEGATIOPERATORE,
                    Pratica::STATUS_CANCELLED,
                ]
            ]
        );
    }
}
