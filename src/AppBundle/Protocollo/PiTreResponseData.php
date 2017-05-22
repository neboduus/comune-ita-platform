<?php

namespace AppBundle\Protocollo;


class PiTreResponseData
{
    private $rawData;
    private $data;

    public function __construct(array $rawData)
    {
        $this->rawData = $rawData;
        $this->data = $this->rawData['data'] ?? array();
    }

    public function getStatus()
    {
        return $this->rawData['status'] ?? null;
    }

    public function getMessage()
    {
        return $this->rawData['message'] ?? null;
    }

    public function getIdDoc()
    {
        return $this->data['id_doc'] ?? null;
    }

    public function getNProt()
    {
        return $this->data['n_prot'] ?? null;
    }

    public function getIdProj()
    {
        return $this->data['id_proj'] ?? null;
    }

    public function __toString()
    {
        return json_encode($this->rawData);
    }
}
