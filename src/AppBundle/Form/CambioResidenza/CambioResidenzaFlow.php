<?php

namespace AppBundle\Form\CambioResidenza;

use AppBundle\Entity\CambioResidenza;
use AppBundle\Form\Base\NucleoFamiliareType;
use AppBundle\Form\Base\PraticaFlow;
use AppBundle\Form\Base\AccettazioneIstruzioniType;
use AppBundle\Form\Base\SelezionaEnteType;
use AppBundle\Form\Base\DatiRichiedenteType;
use Craue\FormFlowBundle\Form\FormFlowInterface;


class CambioResidenzaFlow extends PraticaFlow
{
    const STEP_SELEZIONA_ENTE = 1;
    const STEP_ACCETTAZIONE_ISTRUZIONI = 2;
    const STEP_DATI_RICHIEDENTE = 3;
    const STEP_DICHIARAZIONE_PROVENIENZA = 4;
    const STEP_DICHIARAZIONE_PROVENIENZA_DETTAGLIO = 5;
    const STEP_DATI_RESIDENZA = 6;
    const STEP_NUCLEO_FAMILIARE = 7;
    const STEP_ATTUALMENTE_RESIDENTI = 8;
    const STEP_TIPOLOGIA_OCCUPAZIONE = 9;
    const STEP_TIPOLOGIA_OCCUPAZIONE_DETTAGLIO = 10;
    const STEP_INFORMAZIONI_ACCERTAMENTO = 11;
    const STEP_CONFERMA = 12;

    protected $allowDynamicStepNavigation = true;

    protected function loadStepsConfig()
    {
        return array(
            self::STEP_SELEZIONA_ENTE => array(
                'label' => 'pratica.selezionaEnte',
                'form_type' => SelezionaEnteType::class,
            ),
            self::STEP_ACCETTAZIONE_ISTRUZIONI => array(
                'label' => 'pratica.accettazioneIstruzioni',
                'form_type' => AccettazioneIstruzioniType::class,
            ),
            self::STEP_DATI_RICHIEDENTE => array(
                'label' => 'pratica.datiRichiedente',
                'form_type' => DatiRichiedenteType::class,
            ),
            self::STEP_DICHIARAZIONE_PROVENIENZA => array(
                'label' => 'cambio_residenza.dichiarazioneProvenienza',
                'form_type' => DichiarazioneProvenienzaType::class,
            ),
            self::STEP_DICHIARAZIONE_PROVENIENZA_DETTAGLIO => array(
                'label' => 'cambio_residenza.dichiarazioneProvenienzaDettaglio',
                'form_type' => DichiarazioneProvenienzaDettaglioType::class,
                'skip' => function($estimatedCurrentStepNumber, FormFlowInterface $flow) {
                    return $flow->getFormData()->getProvenienza() == CambioResidenza::PROVENIENZA_COMUNE;
                },
            ),
            self::STEP_DATI_RESIDENZA => array(
                'label' => 'cambio_residenza.datiResidenza',
                'form_type' => DatiResidenzaType::class,
            ),
            self::STEP_NUCLEO_FAMILIARE => array(
                'label' => 'pratica.nucleoFamiliare',
                'form_type' => NucleoFamiliareType::class,
            ),
            self::STEP_ATTUALMENTE_RESIDENTI => array(
                'label' => 'cambio_residenza.attualmenteResidenti',
                'form_type' => AttualmenteResidentiType::class,
            ),
            self::STEP_TIPOLOGIA_OCCUPAZIONE => array(
                'label' => 'cambio_residenza.tipologiaOccupazione',
                'form_type' => TipologiaOccupazioneType::class,
            ),
            self::STEP_TIPOLOGIA_OCCUPAZIONE_DETTAGLIO => array(
                'label' => 'cambio_residenza.tipologiaOccupazioneDettaglio',
                'form_type' => TipologiaOccupazioneDettaglioType::class,
            ),
            self::STEP_INFORMAZIONI_ACCERTAMENTO => array(
                'label' => 'cambio_residenza.informazioniAccertamento',
                'form_type' => InformazioneAccertamentoType::class,
            ),
            self::STEP_CONFERMA => array(
                'label' => 'pratica.conferma',
            ),
        );
    }
}
