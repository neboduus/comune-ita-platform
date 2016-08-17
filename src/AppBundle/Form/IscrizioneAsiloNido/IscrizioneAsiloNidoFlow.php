<?php

namespace AppBundle\Form\IscrizioneAsiloNido;

use AppBundle\Form\Base\AccettazioneIstruzioniType;
use AppBundle\Form\Base\NucleoFamiliareType;
use AppBundle\Form\Base\PraticaFlow;

class IscrizioneAsiloNidoFlow extends PraticaFlow
{

    protected $allowDynamicStepNavigation = true;
    protected $handleFileUploads = false;


    protected function loadStepsConfig()
    {
        return array(
            array(
                'label' => 'iscrizione_asilo_nido.accettazioneIstruzioni',
                'form_type' => AccettazioneIstruzioniType::class,
            ),
            array(
                'label' => 'iscrizione_asilo_nido.selezionaEnte',
                'form_type' => SelezionaEnteType::class,
            ),
            array(
                'label' => 'iscrizione_asilo_nido.selezionaNido',
                'form_type' => SelezionaNidoType::class,
            ),
            array(
                'label' => 'iscrizione_asilo_nido.accettazioneUtilizzoNidoForm',
                'form_type' => AccettazioneUtilizzoNidoType::class,
            ),
            array(
                'label' => 'iscrizione_asilo_nido.datiRichiedente',
                'form_type' => DatiRichiedenteType::class,
            ),
            array(
                'label' => 'iscrizione_asilo_nido.nucleoFamiliare',
                'form_type' => NucleoFamiliareType::class,
            ),
            array(
                'label' => 'iscrizione_asilo_nido.allegati',
                'form_type' => AllegatiType::class,
            ),
            array(
                'label' => 'iscrizione_asilo_nido.conferma',
            ),
        );
    }

}
