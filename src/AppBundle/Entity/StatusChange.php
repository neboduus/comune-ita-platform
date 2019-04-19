<?php


namespace AppBundle\Entity;

/**
 * Class StatusChange
 */
class StatusChange
{
    private $timestamp;
    private $evento;
    private $operatore;
    private $responsabile;
    private $struttura;
    private $message;

    /**
     * StatusChange constructor.
     * @param array $data
     * @throws \Exception
     */
    public function __construct($data)
    {
        /**
         * Since GISCOM uses different codes we have to map them here
         * We look for the giscom mapping and fallback to the raw value if none is found
         */
        $this->evento = $data['evento'];
        $this->operatore = $data['operatore'];
        $this->responsabile = $data['responsabile'];
        $this->struttura = $data['struttura'];
        $this->timestamp = $data['timestamp'] ?? $data['time'];
        /**
         * Giscom is asking for SDC to relay the error message to the user
         */
        $this->message = $data['message'] ?? null;

        if(!is_int($this->timestamp)){
            $date = new \DateTime($this->timestamp, new \DateTimeZone('Europe/Rome'));
            $this->timestamp = $date->getTimestamp();
        }
    }

    /**
     * @return int
     */
    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    /**
     * @return string
     */
    public function getEvento(): string
    {
        return $this->evento;
    }

    /**
     * @return string
     */
    public function getOperatore(): string
    {
        return $this->operatore;
    }

    /**
     * @return string
     */
    public function getResponsabile(): string
    {
        return $this->responsabile;
    }

    /**
     * @return string
     */
    public function getStruttura(): string
    {
        return $this->struttura;
    }

    /**
     * @return string
     */
    public function toJson()
    {
        return json_encode(
            $this->toArray()
        );
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'evento' => $this->evento,
            'operatore' => $this->operatore,
            'responsabile' => $this->responsabile,
            'struttura' => $this->struttura,
            'timestamp' => $this->timestamp,
            'message' => $this->message
        ];
    }
}