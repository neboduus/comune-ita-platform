<?php

namespace AppBundle\Form\IscrizioneAsiloNido;

use AppBundle\Entity\Pratica;
use AppBundle\Logging\LogConstants;
use AppBundle\Services\SdcDataProvider;
use Craue\FormFlowBundle\Form\FormFlow;
use Psr\Log\LoggerInterface;

class IscrizioneAsiloNidoFlow extends FormFlow
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    protected $allowDynamicStepNavigation = true;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    protected function loadStepsConfig()
    {
        $sdcDataProvider = new SdcDataProvider([
            'testo_istruzioni'=> "<strong>Tutto</strong> quello che volevi sapere sugli asili nido e non hai <em>mai</em> osato chiedere!"
        ]);
        return array(
            array(
                'label' => 'iscrizione_asilo_nido.istruzioni',
                'form_type' => 'AppBundle\Form\IscrizioneAsiloNido\IstruzioniForm',
                'form_options' => array('sdc_data' => $sdcDataProvider ),
            ),
            array(
                'label' => 'iscrizione_asilo_nido.selezionaEnte',
                'form_type' => 'AppBundle\Form\IscrizioneAsiloNido\SelezionaEnteForm',
                'form_options' => array('sdc_data' => $sdcDataProvider ),
            ),
            array(
                'label' => 'iscrizione_asilo_nido.selezionaNido',
                'form_type' => 'AppBundle\Form\IscrizioneAsiloNido\SelezionaNidoForm',
                'form_options' => array('sdc_data' => $sdcDataProvider ),
            ),
            array(
                'label' => 'iscrizione_asilo_nido.conferma',
                'form_options' => array('sdc_data' => $sdcDataProvider ),
            ),
        );
    }

    public function getFormOptions($step, array $options = array()) {
        $options = parent::getFormOptions($step, $options);

        /** @var Pratica $pratica */
        $pratica = $this->getFormData();

        $this->logger->info(
            LogConstants::PRATICA_COMPILING_STEP, ['step' => $step, 'pratica' => $pratica->getId(), 'user' => $pratica->getUser()->getId()]
        );


        if ($step === 3 && $pratica instanceof Pratica) {
            $ente = $pratica->getEnte();

            /** @var SdcDataProvider $sdcDataProvider */
            $sdcDataProvider = $options['sdc_data'];
            $sdcDataProvider->set('asili', [
                'aaa' => 'aaa',
                'bbb' => 'bbb',
                'ccc' => 'ccc',
                $ente->getName() => 'test',
            ]);
        }

        return $options;
    }
}
