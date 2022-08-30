<?php

namespace App\Entity;


interface IntegrabileInterface
{
    public function getRichiesteIntegrazione();

    public function getRichiestaDiIntegrazioneAttiva();

    public function setRichiesteIntegrazione($richiesteIntegrazione);

    public function haUnaRichiestaDiIntegrazioneAttiva();

    public function addRichiestaIntegrazione(RichiestaIntegrazione $integration);
}
