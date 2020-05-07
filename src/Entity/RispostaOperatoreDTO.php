<?php

namespace App\Entity;

class RispostaOperatoreDTO
{
    private $payload;

    private $owner;

    private $message;

    /**
     * RichiestaIntegrazioneDTO constructor.
     * @param $payload
     * @param $owner
     * @param $message
     */
    public function __construct($payload, $owner, $message)
    {
        $this->payload = $payload;
        $this->owner = $owner;
        $this->message = $message;
    }

    /**
     * @return mixed
     */
    public function getPayload()
    {
        return $this->payload;
    }

    /**
     * @return mixed
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @return mixed
     */
    public function getMessage()
    {
        return $this->message;
    }
}
