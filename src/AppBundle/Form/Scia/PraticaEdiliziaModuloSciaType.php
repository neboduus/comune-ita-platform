<?php

namespace AppBundle\Form\Scia;

use AppBundle\Entity\SciaPraticaEdilizia;
use AppBundle\Mapper\Giscom\SciaPraticaEdilizia\ModuloDomanda;
use AppBundle\Mapper\Giscom\SciaPraticaEdilizia as MappedPraticaEdilizia;
use AppBundle\Services\P7MSignatureCheckService;
use AppBundle\Validator\Constraints\AtLeastOneAttachmentConstraint;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormError;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use AppBundle\Form\Extension\TestiAccompagnatoriProcedura;

class PraticaEdiliziaModuloSciaType extends AbstractType
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
        $helper->setGuideText('steps.scia.modulo_default.guida_alla_compilazione', true);
        $helper->setStepTitle('steps.scia.modulo_default.title', true);

        $skeleton = new MappedPraticaEdilizia($pratica->getDematerializedForms());
        $allegati = $skeleton->getModuloDomanda()->hasContent() ? [$skeleton->getModuloDomanda()->toHash()] : [];

        $idPratica = $pratica->getId();

        $helper->setVueApp('scia_ediliza_modulo_scia');
        $helper->setVueBundledData(json_encode([
            'type' => 'scia_ediliza_modulo_scia',
            'files' => $allegati,
            'idPratica' => $idPratica,
            'prefix' => $helper->getPrefix(),
        ]));

        $builder
            ->add('oggetto', TextareaType::class, [
                'required' => true,
                'label' => 'Oggetto dei lavori',
            ])
            ->add('dematerialized_forms', HiddenType::class,
                [
                    'attr' => ['value' => json_encode($allegati)],
                    'mapped' => false,
                    'required' => false,
                ]
            );
        $builder->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'onPreSubmit'));
    }


    public function getBlockPrefix()
    {
        return 'scia_pratica_edilizia_modulo_scia';
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
        if (empty( $compilazione )) {
            $event->getForm()->addError(
                new FormError($helper->translate(
                    'steps.scia.error.allegato_richiesto',
                    ['%field%' => $helper->translate('steps.scia.modulo_default.title')]
                ))
            );
        } else {
            foreach ($compilazione as $item) {
                $skeleton->setModuloDomanda(new ModuloDomanda($item));
                break;
            }

            $errors = $this->validator->validate(
                [$skeleton->getModuloDomanda()->getId()],
                new AtLeastOneAttachmentConstraint(), null
            );
            if (count($errors) > 0 || empty( $compilazione )) {
                $event->getForm()->addError(
                    new FormError($helper->translate(
                        'steps.scia.error.allegato_richiesto',
                        ['%field%' => $helper->translate('steps.scia.modulo_default.title')]
                    ))
                );
            }

            $pratica->setDematerializedForms($skeleton->toHash());
            $this->em->persist($pratica);
        }
    }

    public static function getRequestIntegrations(SciaPraticaEdilizia $pratica)
    {
        $integrazioneAllegati = null;
        if ($pratica->haUnaRichiestaDiIntegrazioneAttiva()){
            $integrationRequest = $pratica->getRichiestaDiIntegrazioneAttiva()->getPayload();
            if (isset($integrationRequest['moduloDomanda'])){
                $integrazioneAllegati = true;
            }
        }

        return $integrazioneAllegati;
    }
}
