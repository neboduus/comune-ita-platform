<?php

namespace AppBundle\Form\Scia;

use AppBundle\Entity\SciaPraticaEdilizia;
use AppBundle\Form\Extension\TestiAccompagnatoriProcedura;
use AppBundle\Mapper\Giscom\SciaPraticaEdilizia\ElencoUlterioriAllegatiTecnici;
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


class PraticaEdiliziaUlterioriAllegatiTecniciType extends AbstractType
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
    public function __construct(EntityManager $entityManager, ValidatorInterface $validator, P7MSignatureCheckService $p7mCheckService)
    {
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
        $helper->setStepTitle('steps.scia.ulteriori_allegati_tecnici.title', true);
        $helper->setGuideText('steps.scia.ulteriori_allegati_tecnici.guida_alla_compilazione', true);

        $elencoUlterioriAllegatiTecnici = $this->setupHelperData($pratica, $helper);
        $builder
            ->add('dematerialized_forms', HiddenType::class,
                [
                    'attr' => ['value'=> json_encode(['elencoUlterioriAllegatiTecnici' => $elencoUlterioriAllegatiTecnici])],
                    'mapped' => false,
                    'required' => false,
                ]
            );
        $builder->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'onPreSubmit'));
        $builder->addEventListener(FormEvents::POST_SUBMIT, array($this, 'onPostSubmit'));
    }

    public function onPostSubmit(FormEvent $event)
    {
        $helper = $event->getForm()->getConfig()->getOptions()['helper'];
        $pratica = $event->getData();
        $this->setupHelperData($pratica, $helper);
    }

    public function getBlockPrefix()
    {
        return 'scia_pratica_edilizia_ulteriori_allegati_tecnici';
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

        foreach($compilazione['elencoUlterioriAllegatiTecnici'] as $key => $value){
            $skeleton->setElencoUlterioriAllegatiTecnici($key, new FileCollection($value));
        }

        $integrazioneAllegati = self::getRequestIntegrations($pratica);
        $allegatiRichiesti = $this->getRequiredFields($skeleton, $integrazioneAllegati);

        $elencoUlterioriAllegatiTecnici = $skeleton->getElencoUlterioriAllegatiTecnici();
        foreach ($elencoUlterioriAllegatiTecnici->getProperties() as $key) {
            if ($elencoUlterioriAllegatiTecnici->isRequired($key, $allegatiRichiesti)) {
                $errors = $this->validator->validate(
                    $elencoUlterioriAllegatiTecnici->{$key}->toIdArray(),
                    new AtLeastOneAttachmentConstraint(), null
                );
                if (count($errors) > 0) {
                    $event->getForm()->addError(
                        new FormError($helper->translate(
                            'steps.scia.error.allegato_richiesto',
                            ['%field%' => $helper->translate('steps.scia.ulteriori_allegati_tecnici.files.' . $key . '.title')]
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
        $allegatiCorrenti = $skeleton->getElencoUlterioriAllegatiTecnici()->toHash();
        $allegatiRichiesti = $this->getRequiredFields($skeleton, $integrazioneAllegati);

        foreach ($allegatiCorrenti as $key => $value) {
            if (is_array($integrazioneAllegati) && !in_array($key, $integrazioneAllegati)){
                unset($allegatiCorrenti[$key]);
                continue;
            }
            $allegati[$key]['title'] = $helper->translate('steps.scia.ulteriori_allegati_tecnici.files.' . $key . '.title');
            $allegati[$key]['description'] = $helper->translate('steps.scia.ulteriori_allegati_tecnici.files.' . $key . '.description');
            $allegati[$key]['type'] = ElencoUlterioriAllegatiTecnici::TYPE;
            $allegati[$key]['identifier'] = $key;
            $allegati[$key]['checked'] = false;
            $allegati[$key]['files'] = [];
            if (!empty($value)) {
                $allegati[$key]['checked'] = true;
                $allegati[$key]['files'] = $value;
            }
        }

        $idPratica = $pratica->getId();

        $helper->setVueApp(ElencoUlterioriAllegatiTecnici::TYPE);
        $helper->setVueBundledData(json_encode([
            'allegatiCorrenti' => $allegatiCorrenti,
            'allegati' => $allegati,
            'allegatiRichiesti' => array_fill_keys($allegatiRichiesti, true),
            'idPratica' => $idPratica,
        ]));

        return $allegatiCorrenti;
    }

    public static function getRequestIntegrations(SciaPraticaEdilizia $pratica)
    {
        $integrazioneAllegati = null;
        if ($pratica->haUnaRichiestaDiIntegrazioneAttiva()) {
            $integrationRequest = $pratica->getRichiestaDiIntegrazioneAttiva()->getPayload();
            if (isset( $integrationRequest['elencoAllegatiTecnici'] )) {
                $elencoUlterioriAllegatiTecnici = (new MappedPraticaEdilizia())->getElencoUlterioriAllegatiTecnici()->getProperties();
                $integrazioneAllegati = array_intersect(
                    $elencoUlterioriAllegatiTecnici,
                    (array)$integrationRequest['elencoAllegatiTecnici']
                );
            }
        }

        return $integrazioneAllegati;
    }

    private function getRequiredFields(MappedPraticaEdilizia $skeleton, $integrazioneAllegati)
    {
        return is_array($integrazioneAllegati) ?
            $integrazioneAllegati :
            $skeleton->getElencoUlterioriAllegatiTecnici()->getRequiredFields($skeleton->getTipoIntervento());
    }
}