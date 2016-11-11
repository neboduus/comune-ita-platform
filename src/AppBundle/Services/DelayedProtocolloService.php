<?php

namespace AppBundle\Services;

use AppBundle\Entity\AllegatoInterface;
use AppBundle\Entity\Pratica;
use AppBundle\Entity\ScheduledAction;
use AppBundle\Protocollo\Exception\AlreadyScheduledException;
use AppBundle\ScheduledAction\ScheduledActionHandlerInterface;

class DelayedProtocolloService extends ProtocolloService implements ProtocolloServiceInterface, ScheduledActionHandlerInterface
{
    const SCHEDULED_ITEM_TYPE_SEND = 'protocollo.sendPratica';

    const SCHEDULED_ITEM_TYPE_UPLOAD = 'protocollo.uploadFile';

    public function protocollaPratica(Pratica $pratica)
    {
        $this->validatePratica($pratica);

        $params = serialize([
            'pratica' => $pratica->getId(),
        ]);

        $repository = $this->entityManager->getRepository('AppBundle:ScheduledAction');
        if ($repository->findBy([
            'type' => self::SCHEDULED_ITEM_TYPE_SEND,
            'params' => $params,
        ])
        ) {
            throw new AlreadyScheduledException();
        }

        $scheduled = (new ScheduledAction())
            ->setService('ocsdc.protocollo')
            ->setType(self::SCHEDULED_ITEM_TYPE_SEND)
            ->setParams($params);
        $this->entityManager->persist($scheduled);
        $this->entityManager->flush();
    }

    public function protocollaAllegato(Pratica $pratica, AllegatoInterface $allegato)
    {
        $this->validateUploadFile($pratica, $allegato);

        $params = serialize([
            'pratica' => $pratica->getId(),
            'allegato' => $allegato->getId()
        ]);

        $repository = $this->entityManager->getRepository('AppBundle:ScheduledAction');
        if ($repository->findBy([
            'type' => self::SCHEDULED_ITEM_TYPE_UPLOAD,
            'params' => $params,
        ])
        ) {
            throw new AlreadyScheduledException();
        }

        $scheduled = (new ScheduledAction())
            ->setService('ocsdc.protocollo')
            ->setType(self::SCHEDULED_ITEM_TYPE_UPLOAD)
            ->setParams($params);

        $this->entityManager->persist($scheduled);
        $this->entityManager->flush();
    }

    /**
     * @param ScheduledAction $action
     *
     * @see ScheduledActionCommand
     */
    public function executeScheduledAction(ScheduledAction $action)
    {
        $params = unserialize($action->getParams());

        if ($action->getType() == self::SCHEDULED_ITEM_TYPE_SEND) {

            $pratica = $this->entityManager->getRepository('AppBundle:Pratica')->find($params['pratica']);

            if ($pratica instanceof Pratica) {
                parent::protocollaPratica($pratica);
            }

        } elseif ($action->getType() == self::SCHEDULED_ITEM_TYPE_UPLOAD) {

            $allegato = $this->entityManager->getRepository('AppBundle:Allegato')->find($params['allegato']);
            $pratica = $this->entityManager->getRepository('AppBundle:Pratica')->find($params['pratica']);

            if ($pratica instanceof Pratica && $allegato instanceof AllegatoInterface) {
                parent::protocollaAllegato($pratica, $allegato);
            }

        }
    }
}
