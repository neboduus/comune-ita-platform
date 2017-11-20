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


        $choices = array(
            'Si' => true,
            'No' => false
        );

        $builder->add('has_delega', ChoiceType::class, [
            'choices'  => $choices,
            'multiple' => false,
            'required' => true,
            'mapped'   => false,
            'expanded' => true,
            'data'     => isset($data['has_delega']) ? $data['has_delega'] : false,
            'label'    => 'steps.common.delega.has_delega'
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
        if ( !$data['has_delega'] )
        {
            $delegaData = array(
                'has_delega' => $data['has_delega']
            );
            //Todo: eliminare via entity
            //$data['related_cfs'] = '';
        }
        else
        {
            $delegaData = array(
                'has_delega' => $data['has_delega'],
                'delega_type' => isset($data['delega_type']) ? $data['delega_type'] : '',
                'delega_type_text' => isset($data['delega_type_text']) ? $data['delega_type_text'] : '',
                'nome_soggetto_certificato' => isset($data['nome_soggetto_certificato']) ? $data['nome_soggetto_certificato'] : '',
                'data_nascita_soggetto_certificato' => isset($data['data_nascita_soggetto_certificato']) ? $data['data_nascita_soggetto_certificato'] : '',
                'cf_soggetto_certificato' => isset($data['related_cfs']) ? $data['related_cfs'] : '',
            );
        }
        $data['delega_data'] = json_encode( $delegaData );
        $event->setData( $data );
    }
}
