<?php

namespace AppBundle\Form\Scia;

use AppBundle\Entity\SciaPraticaEdilizia;
use AppBundle\Mapper\Giscom\SciaPraticaEdilizia\ElencoAllegatiAllaDomanda;
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
use Symfony\Component\Form\FormError;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use AppBundle\Form\Extension\TestiAccompagnatoriProcedura;

class PraticaEdiliziaAllegatiModuloSciaType extends AbstractType
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
        /** @var SciaPraticaEdilizia $pratica */
        $pratica = $builder->getData();

        /** @var TestiAccompagnatoriProcedura $helper */
        $helper = $options["helper"];
        $helper->setStepTitle('steps.scia.allegati_modulo_scia.title', true);
        $helper->setGuideText('steps.scia.allegati_modulo_scia.guida_alla_compilazione', true);

        $elencoAllegatiAllaDomanda = $this->setupHelperData($pratica, $helper);

        $builder
            ->add('dematerialized_forms', HiddenType::class, [
                'attr' => ['value' => json_encode(['elencoAllegatiAllaDomanda' => $elencoAllegatiAllaDomanda])],
                'mapped' => false,
                'required' => false,
            ]);
        $builder->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'onPreSubmit'));
        $builder->addEventListener(FormEvents::POST_SUBMIT, array($this, 'onPostSubmit'));
    }


    public function getBlockPrefix()
    {
        return 'scia_pratica_edilizia_allegati_modulo_scia';
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

        /** @var SciaPraticaEdilizia $pratica */
        $pratica = $event->getForm()->getData();
        $skeleton = new MappedPraticaEdilizia($pratica->getDematerializedForms());

        $compilazione = json_decode($event->getData()['dematerialized_forms'], true);

        foreach ($compilazione['elencoAllegatiAllaDomanda'] as $key => $value) {
            $skeleton->setElencoAllegatiAllaDomanda($key, new FileCollection($value));
        }

        $integrazioneAllegati = self::getRequestIntegrations($pratica);
        $allegatiRichiesti = $this->getRequiredFields($skeleton, $integrazioneAllegati);

        $elencoAllegatiAllaDomanda = $skeleton->getElencoAllegatiAllaDomanda();
        foreach ($elencoAllegatiAllaDomanda->getProperties() as $key) {
            if ($elencoAllegatiAllaDomanda->isRequired($key, $allegatiRichiesti)) {
                $errors = $this->validator->validate(
                    $elencoAllegatiAllaDomanda->{$key}->toIdArray(),
                    new AtLeastOneAttachmentConstraint(), null
                );
                if (count($errors) > 0) {
                    $event->getForm()->addError(
                        new FormError($helper->translate(
                            'steps.scia.error.allegato_richiesto',
                            ['%field%' => $helper->translate('steps.scia.allegati_modulo_scia.files.' . $key . '.title')]
                        ))
                    );
                }
            }
        }

        $pratica->setDematerializedForms($skeleton->toHash());
        $this->em->persist($pratica);
    }

    /**
     * @param SciaPraticaEdilizia $pratica
     * @param TestiAccompagnatoriProcedura $helper
     * @return array
     */
    private function setupHelperData(SciaPraticaEdilizia $pratica, TestiAccompagnatoriProcedura $helper)
    {
        $skeleton = new MappedPraticaEdilizia($pratica->getDematerializedForms());

        $integrazioneAllegati = self::getRequestIntegrations($pratica);

        $allegati = array();
        $allegatiCorrenti = $skeleton->getElencoAllegatiAllaDomanda()->toHash();
        $allegatiRichiesti = $this->getRequiredFields($skeleton, $integrazioneAllegati);

        foreach ($allegatiCorrenti as $key => $value) {
            if (is_array($integrazioneAllegati) && !in_array($key, $integrazioneAllegati)){
                unset($allegatiCorrenti[$key]);
                continue;
            }
            $allegati[$key]['title'] = $helper->translate('steps.scia.allegati_modulo_scia.files.' . $key . '.title');
            $allegati[$key]['description'] = $helper->translate('steps.scia.allegati_modulo_scia.files.' . $key . '.description');
            $allegati[$key]['type'] = ElencoAllegatiAllaDomanda::TYPE;
            $allegati[$key]['identifier'] = $key;
            $allegati[$key]['checked'] = false;
            $allegati[$key]['files'] = [];
            if (!empty($value)) {
                $allegati[$key]['checked'] = true;
                $allegati[$key]['files'] = $value;
            }
        }

        $idPratica = $pratica->getId();

        $helper->setVueApp(ElencoAllegatiAllaDomanda::TYPE);
        $helper->setVueBundledData(json_encode([
            'allegatiCorrenti' => $allegatiCorrenti,
            'allegati' => $allegati,
            'allegatiRichiesti' => array_fill_keys($allegatiRichiesti, true),
            'idPratica' => $idPratica,
            'prefix' => $helper->getPrefix(),
        ]));

        return $allegatiCorrenti;
    }

    public static function getRequestIntegrations(SciaPraticaEdilizia $pratica)
    {
        $integrazioneAllegati = null;
        if ($pratica->haUnaRichiestaDiIntegrazioneAttiva()){
            $integrationRequest = $pratica->getRichiestaDiIntegrazioneAttiva()->getPayload();
            if (isset($integrationRequest['elencoAllegatiAllaDomanda'])){
                $integrazioneAllegati = (array)$integrationRequest['elencoAllegatiAllaDomanda'];
            }
        }

        return $integrazioneAllegati;
    }

    private function getRequiredFields(MappedPraticaEdilizia $skeleton, $integrazioneAllegati)
    {
        return is_array($integrazioneAllegati) ?
            $integrazioneAllegati :
            $skeleton->getElencoAllegatiAllaDomanda()->getRequiredFields($skeleton->getTipoIntervento() ?? $skeleton->getTipo());
    }

}
