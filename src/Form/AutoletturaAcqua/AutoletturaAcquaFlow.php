<?php

namespace App\Form\AutoletturaAcqua;

use App\Entity\AutoletturaAcqua;
use App\Entity\CPSUser;
use App\Form\Base\AccettazioneIstruzioniType;
use App\Form\Base\DatiRichiedenteType;
use App\Form\Base\PraticaFlow;
use App\Form\Base\SelezionaEnteType;
use App\Form\Base\SelectPaymentGatewayType;
use App\Form\Base\PaymentGatewayType;
use Craue\FormFlowBundle\Form\FormFlowInterface;


class AutoletturaAcquaFlow extends PraticaFlow
{
    const STEP_SELEZIONA_ENTE = 1;
    const STEP_ACCETTAZIONE_ISTRUZIONI = 2;
    const STEP_DATI_RICHIEDENTE = 3;
    const STEP_DATI_INTESTATARIO = 4;
    const STEP_DATI_CONTATORE = 5;
    const STEP_DATI_LETTURA = 6;
    const STEP_NOTE = 7;
    const STEP_CONFERMA = 8;

    protected $allowDynamicStepNavigation = true;

    protected function loadStepsConfig()
    {
        $steps =  array(
            self::STEP_ACCETTAZIONE_ISTRUZIONI => array(
                'label' => 'steps.common.accettazione_istruzioni.label',
                'form_type' => AccettazioneIstruzioniType::class,
            ),
            self::STEP_DATI_RICHIEDENTE => array(
                'label' => 'steps.common.dati_richiedente.label',
                'form_type' => DatiRichiedenteType::class,
            ),
            self::STEP_DATI_INTESTATARIO => array(
                'label' => 'steps.autolettura_acqua.dati_intestatario.label',
                'form_type' => DatiIntestatarioType::class,
            ),
            self::STEP_DATI_CONTATORE => array(
                'label' => 'steps.autolettura_acqua.dati_contatore.label',
                'form_type' => DatiContatoreType::class,
            ),
            self::STEP_DATI_LETTURA => array(
                'label' => 'steps.autolettura_acqua.dati_lettura.label',
                'form_type' => DatiLetturaType::class,
            ),
            self::STEP_NOTE => array(
                'label' => 'steps.autolettura_acqua.note.label',
                'form_type' => NoteType::class,
            )
        );

        // Mostro lo step del'ente solo se è necesario
        if ($this->getFormData()->getEnte() == null && $this->prefix == null)
        {
            $steps [self::STEP_SELEZIONA_ENTE] = array(
                'label' => 'steps.common.seleziona_ente.label',
                'form_type' => SelezionaEnteType::class,
                'skip' => function ($estimatedCurrentStepNumber, FormFlowInterface $flow) {
                    return ($flow->getFormData()->getEnte() != null && $this->prefix != null);
                }
            );
        }
        ksort($steps);

        // Attivo gli step di pagamento solo se è richiesto nel servizio
        if ($this->isPaymentRequired())
        {

            $steps[]= array(
                'label' => 'steps.common.select_payment_gateway.label',
                'form_type' => SelectPaymentGatewayType::class
            );
            $steps[]= array(
                'label' => 'steps.common.payment_gateway.label',
                'form_type' => PaymentGatewayType::class
            );
        }

        $steps[]= array(
            'label' => 'steps.common.conferma.label'
        );

        return $steps;
    }

    /**
     * @param CPSUser $user
     * @param AutoletturaAcqua $pratica
     */
    public function populatePraticaFieldsWithUserValues(CPSUser $user, $pratica)
    {
        parent::populatePraticaFieldsWithUserValues($user, $pratica);
        if ($pratica->getIntestatarioCodiceUtente() === null) {
            $pratica->setIntestatarioNome($user->getNome());
            $pratica->setIntestatarioCognome($user->getCognome());
            $pratica->setIntestatarioIndirizzo($user->getIndirizzoResidenza());
            $pratica->setIntestatarioCap($user->getCapResidenza());
            $pratica->setIntestatarioCitta($user->getCittaResidenza());
            $pratica->setIntestatarioTelefono($user->getTelefono());
            $pratica->setIntestatarioEmail($user->getEmailCanonical());
        }
    }

    /**
     * @param AutoletturaAcqua $lastPratica
     * @param AutoletturaAcqua $pratica
     */
    public function populatePraticaFieldsWithLastPraticaValues($lastPratica, $pratica)
    {
        parent::populatePraticaFieldsWithLastPraticaValues($lastPratica, $pratica);
        if ($lastPratica->getIntestatarioCodiceUtente()){
            $pratica->setIntestatarioCodiceUtente($lastPratica->getIntestatarioCodiceUtente());
            $pratica->setIntestatarioNome($lastPratica->getIntestatarioNome());
            $pratica->setIntestatarioCognome($lastPratica->getIntestatarioCognome());
            $pratica->setIntestatarioIndirizzo($lastPratica->getIntestatarioIndirizzo());
            $pratica->setIntestatarioCap($lastPratica->getIntestatarioCap());
            $pratica->setIntestatarioCitta($lastPratica->getIntestatarioCitta());
            $pratica->setIntestatarioTelefono($lastPratica->getIntestatarioTelefono());
            $pratica->setIntestatarioEmail($lastPratica->getIntestatarioEmail());

            $pratica->setContatoreNumero($lastPratica->getContatoreNumero());
            $pratica->setContatoreUso($lastPratica->getContatoreUso());
            $pratica->setContatoreUnitaImmobiliari($lastPratica->getContatoreUnitaImmobiliari());

            $pratica->setNote($lastPratica->getNote());
        }
    }

}