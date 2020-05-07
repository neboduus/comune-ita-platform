<?php

namespace App\Mapper\Giscom;

use App\Entity\Pratica;
use App\Entity\StatusChange;
use App\Mapper\StatusMapperInterface;
use Symfony\Component\HttpFoundation\Request;

class GiscomStatusMapper implements StatusMapperInterface
{
    const GISCOM_STATUS_PREISTRUTTORIA = "preistruttoria";
    const GISCOM_STATUS_ISTRUTTORIA = "istruttoria";
    const GISCOM_STATUS_ACCETTATA = "accettata";
    const GISCOM_STATUS_PROTOCOLLATA = "protocollata";
    const GISCOM_STATUS_RICHIESTA_INTEGRAZIONI = "richiesta_integrazioni";
    const GISCOM_STATUS_INVIO_INTEGRAZIONI = "invio_integrazioni";
    const GISCOM_STATUS_PROTOCOLLATA_INTEGRAZIONI = "protocollata_integrazioni";
    const GISCOM_STATUS_ACQUISITA = "acquisita";
    const GISCOM_STATUS_RIFIUTATA = "rifiutata";
    const GISCOM_STATUS_ESITATA = "accettata";

    private static $mapping = [
        self::GISCOM_STATUS_PREISTRUTTORIA => Pratica::STATUS_PENDING,
        self::GISCOM_STATUS_ISTRUTTORIA => Pratica::STATUS_PENDING,
        self::GISCOM_STATUS_RICHIESTA_INTEGRAZIONI => Pratica::STATUS_REQUEST_INTEGRATION, //implicito
        self::GISCOM_STATUS_ACCETTATA => Pratica::STATUS_PENDING,
        self::GISCOM_STATUS_PROTOCOLLATA => Pratica::STATUS_PENDING,
        //self::GISCOM_STATUS_INVIO_INTEGRAZIONI => Pratica::STATUS_PROCESSING,
        self::GISCOM_STATUS_PROTOCOLLATA_INTEGRAZIONI => Pratica::STATUS_PENDING_AFTER_INTEGRATION,
        //self::GISCOM_STATUS_ACQUISITA => Pratica::STATUS_PROCESSING,
        #self::GISCOM_STATUS_RIFIUTATA => Pratica::STATUS_CANCELLED, //@todo
        #self::GISCOM_STATUS_ESITATA => Pratica::STATUS_COMPLETE, //@todo
        self::GISCOM_STATUS_RIFIUTATA => Pratica::STATUS_CANCELLED_WAITALLEGATIOPERATORE,
        self::GISCOM_STATUS_ESITATA => Pratica::STATUS_COMPLETE_WAITALLEGATIOPERATORE
    ];

    public function map($giscomStatus)
    {
        $giscomStatus = strtolower($giscomStatus);
        if (isset(self::$mapping[$giscomStatus])) {
            return self::$mapping[$giscomStatus];
        }

        throw new \InvalidArgumentException("Status $giscomStatus is not a Giscom status");
    }

    /**
     * @param Request $request
     * @return StatusChange
     * @throws \Exception
     */
    public function getStatusChangeFromRequest(Request $request)
    {
        $content = $request->getContent();
        $data = $this->cleanData(json_decode($content, true));
        if (isset($data['evento'])) {
            $data['evento'] = $this->map($data['evento']);
        }
        return new StatusChange($data);
    }

    private function cleanData(array $payload)
    {
        $data = array();
        foreach ($payload as $key => $value) {
            if (strtolower($key) == 'evento') {
                $value = strtolower($value);
            }
            $data[strtolower($key)] = $value;
        }

        return $data;
    }
}
