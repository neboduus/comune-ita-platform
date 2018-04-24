<?php

namespace AppBundle\Protocollo;

use AppBundle\Entity\AllegatoInterface;
use AppBundle\Entity\Pratica;


class DummyProtocolloHandler implements ProtocolloHandlerInterface
{

    /**
     * @param Pratica $pratica
     *
     */
    public function sendPraticaToProtocollo(Pratica $pratica)
    {
        $pratica->setIdDocumentoProtocollo( 'pdp-' . $pratica->getId() );
        $pratica->setNumeroProtocollo( 'pnp-' . $pratica->getId() );
        $pratica->setNumeroFascicolo( 'pnf-' . $pratica->getId() );
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

    public function sendRispostaToProtocollo(Pratica $pratica)
    {
        $risposta = $pratica->getRispostaOperatore();
        $risposta->setNumeroProtocollo( 'rnp-' . $risposta->getId() );
        $risposta->setIdDocumentoProtocollo( 'rdp-' . $risposta->getId() );
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
