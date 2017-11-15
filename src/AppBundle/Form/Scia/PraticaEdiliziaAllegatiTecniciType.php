<?php

namespace AppBundle\Form\Scia;

use AppBundle\Entity\SciaPraticaEdilizia;
use AppBundle\Form\Extension\TestiAccompagnatoriProcedura;
use AppBundle\Mapper\Giscom\SciaPraticaEdilizia\ElencoAllegatiTecnici;
use AppBundle\Mapper\Giscom\FileCollection;
use AppBundle\Mapper\Giscom\SciaPraticaEdilizia as MappedPraticaEdilizia;
use AppBundle\Services\P7MSignatureCheckService;
use AppBundle\Validator\Constraints\AtLeastOneAttachmentConstraint;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Form\FormError;

/**
 * Class AllegatoBType
 */
class PraticaEdiliziaAllegatiTecniciType extends AbstractType
{
    /**
     * @var P7MSignatureCheckService
     */
    private $p7mCheckService;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * ChooseAllegatoType constructor.
     *
     * @param EntityManager $entityManager
     * @param ValidatorInterface $validator
     * @param P7MSignatureCheckService $p7mCheckService
     */
    public function __construct(
        EntityManager $entityManager,
        ValidatorInterface $validator,
        P7MSignatureCheckService $p7mCheckService
    ) {
        $this->p7mCheckService = $p7mCheckService;
        $this->em = $entityManager;
        $this->validator = $validator;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var TestiAccompagnatoriProcedura $helper */
        $helper = $options["helper"];
        $helper->setGuideText('steps.scia.allegati_tecnici.guida_alla_compilazione', true);
        $helper->setStepTitle('steps.scia.allegati_tecnici.title', true);

        /** @var SciaPraticaEdilizia $pratica */
        $pratica = $builder->getData();

        $bundledData = $this->setupHelperData($pratica, $helper);

        $builder
            ->add('dematerialized_forms', HiddenType::class, [
                'attr' => ['value' => json_encode($bundledData)],
                'mapped' => false,
                'required' => false,
            ]);

        $builder->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'onPreSubmit'));
        $builder->addEventListener(FormEvents::POST_SUBMIT, array($this, 'onPostSubmit'));
    }

    public function onPostSubmit(FormEvent $event)
    {
        $helper = $event->getForm()->getConfig()->getOptions()['helper'];
        $pratica = $event->getData();
        $this->setupHelperData($pratica, $helper);
    }

    public function onPreSubmit(FormEvent $event)
    {
        $options = $event->getForm()->getConfig()->getOptions();
        /** @var TestiAccompagnatoriProcedura $helper */
        $helper = $options["helper"];
        $compilazione = json_decode($event->getData()['dematerialized_forms'], true);

        /** @var SciaPraticaEdilizia $pratica */
        $pratica = $event->getForm()->getData();
        $skeleton = new MappedPraticaEdilizia($pratica->getDematerializedForms());

        if (!$pratica->haUnaRichiestaDiIntegrazioneAttiva()) {
            $skeleton->setTipoIntervento($compilazione['tipoIntervento']);
            if (!in_array($skeleton->getTipoIntervento(), $skeleton->getTipiIntervento())) {
                $event->getForm()->addError(
                    new FormError($helper->translate('steps.scia.error.seleziona_tipo_itervento'))
                );
            }
        }

        foreach ($compilazione['elencoAllegatiTecnici'] as $key => $value) {
            $skeleton->setElencoAllegatoTecnici($key, new FileCollection($value));
        }

        $integrazioneAllegati = self::getRequestIntegrations($pratica);
        $allegatiRichiesti = $this->getRequiredFields($skeleton, $integrazioneAllegati);

        $elencoAllegatiTecnici = $skeleton->getElencoAllegatiTecnici();
        foreach ($elencoAllegatiTecnici->getProperties() as $key) {
            if ($elencoAllegatiTecnici->isRequired($key, $allegatiRichiesti[$skeleton->getTipoIntervento()])) {
                $errors = $this->validator->validate(
                    $elencoAllegatiTecnici->{$key}->toIdArray(),
                    new AtLeastOneAttachmentConstraint(), null
                );
                if (count($errors) > 0) {
                    $event->getForm()->addError(
                        new FormError($helper->translate(
                            'steps.scia.error.allegato_richiesto',
                            ['%field%' => $helper->translate('steps.scia.allegati_tecnici.files.' . $key . '.title')]
                        ))
                    );
                }
            }
        }

        $pratica->setDematerializedForms($skeleton->toHash());
        $this->em->persist($pratica);
    }

    public function getBlockPrefix()
    {
        return 'scia_pratica_edilizia_allegati_tecnici';
    }

    /**
     * @param SciaPraticaEdilizia $pratica
     * @param TestiAccompagnatoriProcedura $helper
     *
     * @return array
     */
    private function setupHelperData(SciaPraticaEdilizia $pratica, TestiAccompagnatoriProcedura $helper)
    {
        $skeleton = new MappedPraticaEdilizia($pratica->getDematerializedForms());

        $integrazioneAllegati = self::getRequestIntegrations($pratica);

        $allegati = array();
        $allegatiCorrenti = $skeleton->getElencoAllegatiTecnici()->toHash();

        foreach ($allegatiCorrenti as $key => $value) {
            if (is_array($integrazioneAllegati) && !in_array($key, $integrazioneAllegati)) {
                unset( $allegatiCorrenti[$key] );
                continue;
            }
            $allegati[$key]['title'] = $helper->translate('steps.scia.allegati_tecnici.files.' . $key . '.title');
            $allegati[$key]['description'] = $helper->translate('steps.scia.allegati_tecnici.files.' . $key . '.description');
            $allegati[$key]['type'] = ElencoAllegatiTecnici::TYPE;
            $allegati[$key]['identifier'] = $key;
            $allegati[$key]['checked'] = false;
            $allegati[$key]['files'] = [];
            if (!empty( $value )) {
                $allegati[$key]['checked'] = true;
                $allegati[$key]['files'] = $value;
            }
        }

        $tipiIntervento = [];
        $currentTipoInterventoLabel = null;
        foreach ($skeleton->getTipiIntervento() as $tipo) {
            $label = $helper->translate('steps.scia.allegati_tecnici.tipi_intervento.' . $tipo);
            $tipiIntervento[] = [
                'label' => $label,
                'value' => $tipo
            ];
            if ($skeleton->getTipoIntervento() == $tipo){
                $currentTipoInterventoLabel = $label;
            }
        }

        $allegatiRichiesti = [];
        foreach ($this->getRequiredFields($skeleton, $integrazioneAllegati) as $tipo => $fields) {
            $allegatiRichiesti[$tipo] = array_fill_keys($fields, true);
        }

        $helper->setVueApp(ElencoAllegatiTecnici::TYPE);
        $helper->setVueBundledData(json_encode([
            'tipiIntervento' => $pratica->haUnaRichiestaDiIntegrazioneAttiva() ? false : $tipiIntervento,
            'tipoIntervento' => $skeleton->getTipoIntervento(),
            'currentTipoInterventoLabel' => $currentTipoInterventoLabel,
            'allegatiCorrenti' => $allegatiCorrenti,
            'allegatiRichiesti' => $allegatiRichiesti,
            'idPratica' => $pratica->getId(),
            'allegati' => $allegati,
            'prefix' => $helper->getPrefix(),
        ]));

        return [
            'tipoIntervento' => $skeleton->getTipoIntervento(),
            'elencoAllegatiTecnici' => $allegatiCorrenti
        ];
    }

    public static function getRequestIntegrations(SciaPraticaEdilizia $pratica)
    {
        $integrazioneAllegati = null;
        if ($pratica->haUnaRichiestaDiIntegrazioneAttiva()) {
            $integrationRequest = $pratica->getRichiestaDiIntegrazioneAttiva()->getPayload();
            if (isset( $integrationRequest['elencoAllegatiTecnici'] )) {
                $elencoAllegatiTecnici = (new MappedPraticaEdilizia())->getElencoAllegatiTecnici()->getProperties();
                $integrazioneAllegati = array_intersect(
                    $elencoAllegatiTecnici,
                    (array)$integrationRequest['elencoAllegatiTecnici']
                );
            }
        }

        return $integrazioneAllegati;
    }

    private function getRequiredFields(MappedPraticaEdilizia $skeleton, $integrazioneAllegati)
    {
        $allegatiRichiesti = [];
        foreach ($skeleton->getTipiIntervento() as $tipoIntervento) {
            $requiredFields = $skeleton->getElencoAllegatiTecnici()->getRequiredFields($tipoIntervento);
            if (is_array($integrazioneAllegati)) {
                $requiredIntegrazione = array_intersect(
                    $skeleton->getElencoAllegatiTecnici()->getProperties(),
                    $integrazioneAllegati
                );
                $requiredFields = array_unique(array_merge($requiredFields, $requiredIntegrazione));
            }
            $allegatiRichiesti[$tipoIntervento] = $requiredFields;
        }

        return $allegatiRichiesti;
    }
}
