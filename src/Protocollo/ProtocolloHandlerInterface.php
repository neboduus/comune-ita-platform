<?php

namespace App\Protocollo;

use App\Entity\AllegatoInterface;
use App\Entity\Pratica;
use App\Entity\RichiestaIntegrazione;
use App\Protocollo\Exception\ResponseErrorException;

interface ProtocolloHandlerInterface
{
    public function getConfigParameters();

    /**
     * @param Pratica $pratica
     * @throws ResponseErrorException
     */
    public function sendPraticaToProtocollo(Pratica $pratica);

    /**
     * @param Pratica $pratica
     * @param AllegatoInterface $allegato
     * @throws ResponseErrorException
     */
    public function sendAllegatoToProtocollo(Pratica $pratica, AllegatoInterface $allegato);

    /**
     * @param Pratica $pratica
     * @param AllegatoInterface $allegato
     * @throws ResponseErrorException
     */
    public function sendIntegrazioneToProtocollo(Pratica $pratica, AllegatoInterface $allegato);

    /**
     * @param Pratica $pratica
     */
    public function sendRispostaToProtocollo(Pratica $pratica);

    /**
     * @param Pratica $pratica
     * @param AllegatoInterface $allegato
     * @throws ResponseErrorException
     */
    public function sendAllegatoRispostaToProtocollo(Pratica $pratica, AllegatoInterface $allegato);

    /**
     * @param Pratica $pratica
     * @param AllegatoInterface $richiesta
     * @throws ResponseErrorException
     */
    public function sendRichiestaIntegrazioneToProtocollo(Pratica $pratica, AllegatoInterface $richiesta);
}
