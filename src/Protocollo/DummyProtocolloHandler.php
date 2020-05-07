<?php

namespace App\Protocollo;

use App\Entity\Allegato;
use App\Entity\AllegatoInterface;
use App\Entity\Pratica;
use App\Entity\RichiestaIntegrazione;

class DummyProtocolloHandler implements ProtocolloHandlerInterface
{
    public function getConfigParameters()
    {
        return false;
    }

    /**
     * @param Pratica $pratica
     *
     */
    public function sendPraticaToProtocollo(Pratica $pratica)
    {
        $pratica->setIdDocumentoProtocollo('pdp-' . $pratica->getId());
        $pratica->setNumeroProtocollo('pnp-' . $pratica->getId());
        $pratica->setNumeroFascicolo('pnf-' . $pratica->getId());
    }

    /**
     * @param Pratica $pratica
     * @param AllegatoInterface $allegato
     */
    public function sendAllegatoToProtocollo(Pratica $pratica, AllegatoInterface $allegato)
    {
        $pratica->addNumeroDiProtocollo([
            'id' => $allegato->getId(),
            'protocollo' => 'a-' . $allegato->getId(),
        ]);
    }

    /**
     * @param Pratica $pratica
     * @param AllegatoInterface $richiesta
     */
    public function sendRichiestaIntegrazioneToProtocollo(Pratica $pratica, AllegatoInterface $richiesta)
    {
        $pratica->addNumeroDiProtocollo([
            'id' => $richiesta->getId(),
            'protocollo' => 'r-' . $richiesta->getId(),
        ]);
    }

    /**
     * @param Pratica $pratica
     * @param RichiestaIntegrazione|AllegatoInterface $allegato
     */
    public function sendIntegrazioneToProtocollo(Pratica $pratica, AllegatoInterface $allegato)
    {
        $pratica->addNumeroDiProtocollo([
            'id' => $allegato->getId(),
            'protocollo' => 'int-' . $allegato->getId(),
        ]);
        $allegato->setNumeroProtocollo('int-' . $allegato->getId());
        $allegato->setIdDocumentoProtocollo('int-' . $allegato->getId());
    }


    public function sendRispostaToProtocollo(Pratica $pratica)
    {
        $risposta = $pratica->getRispostaOperatore();
        $risposta->setNumeroProtocollo('rnp-' . $risposta->getId());
        $risposta->setIdDocumentoProtocollo('rdp-' . $risposta->getId());
    }

    public function sendAllegatoRispostaToProtocollo(Pratica $pratica, AllegatoInterface $allegato)
    {
        $risposta = $pratica->getRispostaOperatore();
        $risposta->addNumeroDiProtocollo([
            'id' => $allegato->getId(),
            'protocollo' => 'a-' . $allegato->getId(),
        ]);
    }
}
