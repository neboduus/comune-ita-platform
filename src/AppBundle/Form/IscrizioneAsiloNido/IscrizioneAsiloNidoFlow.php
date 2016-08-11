<?php

namespace AppBundle\Form\IscrizioneAsiloNido;

use AppBundle\Form\Base\PraticaFlow;

class IscrizioneAsiloNidoFlow extends PraticaFlow
{

    protected $allowDynamicStepNavigation = true;


    protected function loadStepsConfig()
    {
        return array(
            array(
                'label' => 'iscrizione_asilo_nido.accettazioneIstruzioni',
                'form_type' => 'AppBundle\Form\Base\AccettazioneIstruzioniForm',
            ),
            array(
                'label' => 'iscrizione_asilo_nido.selezionaEnte',
                'form_type' => 'AppBundle\Form\IscrizioneAsiloNido\SelezionaEnteForm',
            ),
            array(
                'label' => 'iscrizione_asilo_nido.selezionaNido',
                'form_type' => 'AppBundle\Form\IscrizioneAsiloNido\SelezionaNidoForm',
            ),
            array(
                'label' => 'iscrizione_asilo_nido.accettazioneUtilizzoNidoForm',
                'form_type' => 'AppBundle\Form\IscrizioneAsiloNido\AccettazioneUtilizzoNidoForm',
            ),
            array(
                'label' => 'iscrizione_asilo_nido.datiRichiedente',
                'form_type' => 'AppBundle\Form\IscrizioneAsiloNido\DatiRichiedenteForm',
            ),
            array(
                'label' => 'iscrizione_asilo_nido.nucleoFamiliare',
                'form_type' => 'AppBundle\Form\Base\NucleoFamiliareForm',
            ),
            array(
                'label' => 'iscrizione_asilo_nido.conferma',
            ),
        );
    }

}
