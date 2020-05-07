<?php

namespace App\Handlers\Servizio;

class DefaultHandler implements ServizioHandlerInterface
{
    public function execute()
    {
        return null;
    }

    public function getCallToActionText()
    {
        return 'servizio.accedi_al_servizio';
    }

    public function getErrorMessage()
    {
        return "Errore inatteso: contattare il supporto";
    }
}
