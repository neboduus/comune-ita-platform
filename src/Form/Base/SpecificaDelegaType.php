<?php

namespace App\Form\Base;

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
use App\Form\Base\ChooseAllegatoType;

class SpecificaDelegaType extends AbstractType
{
    const DELEGA_FILE_DESCRIPTION = 'Delega firmata dal delegante';
    const DOCUMENTO_DELEGANTE_FILE_DESCRIPTION = 'Documento identità del delegante';

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var TestiAccompagnatoriProcedura $helper */
        $helper = $options["helper"];
        $helper->setGuideText('steps.common.specifica_delega.guida_alla_compilazione', true);
        $helper->setStepTitle('steps.common.specifica_delega.title', true);
        $helper->setDescriptionText('steps.common.specifica_delega.guida_alla_compilazione', true);

        /** @var Pratica $pratica */
        $pratica = $builder->getData();
        $linkModuloDelega = '/bundles/app/files/DelegaRichiestaCertificatiStatoCivile.pdf';
        $data = is_array($pratica->getDelegaData()) ? $pratica->getDelegaData() : json_decode($pratica->getDelegaData(), true);


        $choices = array();
        foreach ($pratica->getTipiDelega() as $delega) {
            $choices[$helper->translate('steps.common.specifica_delega.tipi.' . $delega)] = $delega;
        }


        $builder
            ->add('delega_type', ChoiceType::class, [
                'choices' => $choices,
                'multiple'  => false,
                'required'  => true,
                'expanded'  => true,
                'data'      => $pratica->getDelegaType() ? $pratica->getDelegaType() : array_values($choices)[0],
                'attr' => array('checked'   => 'checked'),
                'label' => 'pratica.dettaglio.delega.ruolo'
            ])

            ->add('delega_type_text', TextType::class, [
                'mapped'     => false,
                'required'   => false,
                'data'       => isset($data['delega_type_text']) ? (string)$data['delega_type_text'] : '',
                'attr'       => !$pratica->getDelegaType() || $pratica->getDelegaType() == array_values($choices)[0] ? ['style' => 'display:none'] : [],
                'label'      => 'steps.common.specifica_delega.type_text',
                'label_attr' => [
                    'style'  => !$pratica->getDelegaType() || $pratica->getDelegaType() == array_values($choices)[0] ? 'display:none' : '',
                    'id'     => 'pratica_specifica_delega_delega_type_text_label'
                ]
            ])

            ->add('delegante_text', BlockQuoteType::class, array(
                'data'     => 'Specifica i dati del delegante.',
            ))

            ->add('nome_soggetto_certificato', TextType::class, [
                'mapped' => false,
                'required' => true,
                'data'     => isset($data['nome_soggetto_certificato']) ? (string)$data['nome_soggetto_certificato'] : '',
                'label' => 'steps.common.specifica_delega.nome_soggetto_certificato'
            ])

            ->add('related_cfs', TextType::class, [
                'required' => true,
                'label' => 'steps.common.specifica_delega.related_cfs'
            ])

           ->add('data_nascita_soggetto_certificato', DateType::class, [
                'required' => true,
                'mapped'   => false,
                'label'    => 'steps.common.specifica_delega.data_nascita_soggetto_certificato',
                'widget'   => 'single_text',
                'format'   => 'dd-MM-yyyy',
                'data'     => new \DateTime(  (isset($data['data_nascita_soggetto_certificato']) ? (string)$data['data_nascita_soggetto_certificato'] : null) ),
                'attr' => [
                    'class' => 'form-control input-inline datepicker',
                    'data-provide' => 'datepicker',
                    'data-date-format' => 'dd-mm-yyyy'
                ]
            ])

            ->add('file_delega_text', BlockQuoteType::class, array(
                'data'     => 'Puoi scaricare <a href="'.$linkModuloDelega.'" target="_blank">qui</a> un modello di delega, che ti preghiamo di compilare, far firmare dal delegato, quindi scansionare e caricare qui sotto.',
            ))

            ->add('file_delega', UploadAllegatoType::class, [
                'label' => 'Carica il file di delega firmato dal delegante.',
                'fileDescription' => self::DELEGA_FILE_DESCRIPTION,
                'required' => true,
                'pratica' => $builder->getData(),
                'mapped' => false,
            ])

            ->add('documento_delegante', UploadAllegatoType::class, [
                'label' => "Carica il documento d'identità del delegante",
                'fileDescription' => self::DOCUMENTO_DELEGANTE_FILE_DESCRIPTION,
                'required' => true,
                'pratica' => $builder->getData(),
                'mapped' => false,
            ]);

        $builder
            ->add('delega_data', HiddenType::class,
                [
                    'attr'     => ['value' =>  json_encode($pratica->getDelegaData())],
                    'mapped'   => true,
                    'required' => true
                ]
            );

        $builder->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'onPreSubmit'));
    }

    public function getBlockPrefix()
    {
        return 'pratica_specifica_delega';
    }

    public function onPreSubmit(FormEvent $event)
    {
        $data = $event->getData();
        $options = $event->getForm()->getConfig()->getOptions();

        /** @var TestiAccompagnatoriProcedura $helper */
        $helper = $options["helper"];
        $delegaData = [];

        if ( ($data['delega_type'] != 'delegato' && empty($data['delega_type_text'])))
        {
            $event->getForm()->addError(
                new FormError($helper->translate('steps.common.specifica_delega.required_field_error'))
            );
        }

        $delegaData = array(
            'delega_type' => $data['delega_type'],
            'delega_type_text' => $data['delega_type_text'],
            'nome_soggetto_certificato' => $data['nome_soggetto_certificato'],
            'data_nascita_soggetto_certificato' => $data['data_nascita_soggetto_certificato'],
            'cf_soggetto_certificato' => $data['related_cfs'],
        );

        $data['delega_data'] = json_encode( $delegaData );
        $event->setData( $data );
    }
}
