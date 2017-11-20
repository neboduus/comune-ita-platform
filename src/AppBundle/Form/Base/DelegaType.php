<?php

namespace AppBundle\Form\Base;

use AppBundle\Entity\Pratica;
use AppBundle\Form\Extension\TestiAccompagnatoriProcedura;
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

class DelegaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var TestiAccompagnatoriProcedura $helper */
        $helper = $options["helper"];
        $helper->setGuideText('steps.common.delega.guida_alla_compilazione', true);
        $helper->setStepTitle('steps.common.delega.title', true);
        $helper->setDescriptionText('steps.common.delega.guida_alla_compilazione', true);

        /** @var Pratica $pratica */
        $pratica = $builder->getData();
        $data = is_array($pratica->getDelegaData()) ? $pratica->getDelegaData() : json_decode($pratica->getDelegaData(), true);


        $choices = array();
        foreach ($pratica->getTipiDelega() as $delega) {
            $choices[$helper->translate('steps.common.delega.tipi.' . $delega)] = $delega;
        }

        $builder->add('delega_type', ChoiceType::class, [
            'choices' => $choices,
            'multiple' => false,
            'required' => false,
            'label' => 'pratica.dettaglio.delega.ruolo'
        ])
            ->add('delega_type_text', TextType::class, [
                'mapped' => false,
                'required' => false,
                'data'   => isset($data['delega_type_text']) ? (string)$data['delega_type_text'] : '',
                'label' => 'steps.common.delega.type_text'
            ])

            ->add('nome_soggetto_certificato', TextType::class, [
                'mapped' => false,
                'required' => false,
                'data'     => isset($data['nome_soggetto_certificato']) ? (string)$data['nome_soggetto_certificato'] : '',
                'label' => 'steps.common.delega.nome_soggetto_certificato'
            ])

            ->add('related_cfs', TextType::class, [
//                'data' => $cf
                'required' => false,
                'label' => 'steps.common.delega.related_cfs'
            ])

           ->add('data_nascita_soggetto_certificato', DateType::class, [
                'required' => false,
                'mapped'   => false,
                'label'    => 'steps.common.delega.data_nascita_soggetto_certificato',
                'widget'   => 'single_text',
                'format'   => 'dd-MM-yyyy',
                'data'     => new \DateTime(  (isset($data['data_nascita_soggetto_certificato']) ? (string)$data['data_nascita_soggetto_certificato'] : null) ),
                'attr' => [
                    'class' => 'form-control input-inline datepicker',
                    'data-provide' => 'datepicker',
                    'data-date-format' => 'dd-mm-yyyy'
                ]
            ])

            ->add('indirizzo_soggetto_certificato', TextType::class, [
                'mapped'   => false,
                'required' => false,
                'data'     => isset($data['indirizzo_soggetto_certificato']) ? (string)$data['indirizzo_soggetto_certificato'] : '',
                'label'    => 'steps.common.delega.indirizzo_soggetto_certificato'
            ]);


        $builder
            ->add('delega_data', HiddenType::class,
                [
                    'attr'     => ['value' =>  json_encode($pratica->getDelegaData())],
                    'mapped'   => true,
                    'required' => false
                ]
            );

        $builder->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'onPreSubmit'));
    }

    public function getBlockPrefix()
    {
        return 'pratica_delega';
    }

    public function onPreSubmit(FormEvent $event)
    {
        $data = $event->getData();
        $options = $event->getForm()->getConfig()->getOptions();

        /** @var TestiAccompagnatoriProcedura $helper */
        $helper = $options["helper"];
        $delegaData = [];
        if ( empty($data['delega_type']) )
        {
            $data['related_cfs'] = '';
        }
        else
        {
            if ( ($data['delega_type'] != 'semplice' && empty($data['delega_type_text'])) || empty($data['nome_soggetto_certificato'])
                || empty($data['data_nascita_soggetto_certificato']) || empty($data['indirizzo_soggetto_certificato']) || empty($data['related_cfs']))
            {
                $event->getForm()->addError(
                    new FormError($helper->translate('steps.common.delega.required_field_error'))
                );
            }

            $delegaData = array(
                'delega_type' => $data['delega_type'],
                'delega_type_text' => $data['delega_type_text'],
                'nome_soggetto_certificato' => $data['nome_soggetto_certificato'],
                'data_nascita_soggetto_certificato' => $data['data_nascita_soggetto_certificato'],
                'indirizzo_soggetto_certificato' => $data['indirizzo_soggetto_certificato'],
                'cf_soggetto_certificato' => $data['related_cfs'],
            );
        }
        $data['delega_data'] = json_encode( $delegaData );
        $event->setData( $data );
    }
}
