<?php

namespace App\Form\EstrattoMorte;

use App\Entity\Pratica;
use App\Form\Extension\TestiAccompagnatoriProcedura;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\VarDumper\VarDumper;

class DatiAttoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var TestiAccompagnatoriProcedura $helper */
        $helper = $options["helper"];
        $helper->setGuideText('steps.certificato_morte.dati_atto.guida_alla_compilazione', true);
        $helper->setStepTitle('steps.certificato_morte.dati_atto.title', true);
        $helper->setDescriptionText('steps.certificato_morte.dati_atto.guida_alla_compilazione', true);

        $pratica = $builder->getData();
        $data = is_array($pratica->getDematerializedForms()) ? $pratica->getDematerializedForms() : json_decode($pratica->getDematerializedForms(), true);

        $builder

            ->add('nome_soggetto_certificato', TextType::class, [
                'mapped' => false,
                'required' => true,
                'data'     => isset($data['nome_soggetto_certificato']) ? (string)$data['nome_soggetto_certificato'] : '',
                'label' => 'steps.certificato_morte.dati_atto.nome_soggetto_certificato'
            ])

            ->add('cognome_soggetto_certificato', TextType::class, [
                'mapped' => false,
                'required' => true,
                'data'     => isset($data['nome_soggetto_certificato']) ? (string)$data['cognome_soggetto_certificato'] : '',
                'label' => 'steps.certificato_morte.dati_atto.cognome_soggetto_certificato'
            ])

            ->add('data_morte_soggetto_certificato', DateType::class, [
                'required' => true,
                'mapped'   => false,
                'label'    => 'steps.certificato_morte.dati_atto.data_morte_soggetto_certificato',
                'widget'   => 'single_text',
                'format'   => 'dd-MM-yyyy',
                'data'     => new \DateTime(  (isset($data['data_morte_soggetto_certificato']) ? (string)$data['data_morte_soggetto_certificato'] : null) ),
                'attr' => [
                    'class' => 'form-control input-inline datepicker',
                    'data-provide' => 'datepicker',
                    'data-date-format' => 'dd-mm-yyyy'
                ],
                'label_attr' => ['class' => 'active']
            ]);

        $builder
            ->add('dematerialized_forms', HiddenType::class,
                [
                    'attr'     => ['value' =>  json_encode($pratica->getDematerializedForms())],
                    'mapped'   => true,
                    'required' => false
                ]
            );

        $builder->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'onPreSubmit'));
    }

    public function getBlockPrefix()
    {
        return 'pratica_dati_atto';
    }

    public function onPreSubmit(FormEvent $event)
    {
        $data = $event->getData();
        $options = $event->getForm()->getConfig()->getOptions();

        /** @var TestiAccompagnatoriProcedura $helper */
        $helper = $options["helper"];
        $dematerializedData = [];

        $dematerializedData = array(
            'nome_soggetto_certificato' => $data['nome_soggetto_certificato'],
            'cognome_soggetto_certificato' => $data['cognome_soggetto_certificato'],
            'data_morte_soggetto_certificato' => $data['data_morte_soggetto_certificato'],
        );

        $data['dematerialized_forms'] = json_encode( $dematerializedData );
        $event->setData( $data );
    }
}
