<?php

namespace App\Handlers\Servizio;

interface ServizioHandlerInterface
{
    public function execute();

    public function getCallToActionText();

    public function getErrorMessage();
}
