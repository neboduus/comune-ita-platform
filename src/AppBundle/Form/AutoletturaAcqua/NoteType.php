<?php

namespace AppBundle\Form\AutoletturaAcqua;

use AppBundle\Form\Extension\TestiAccompagnatoriProcedura;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;

class NoteType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var TestiAccompagnatoriProcedura $helper */
        $helper = $options["helper"];
        $helper->setGuideText('autolettura_acqua.guida_alla_compilazione.note', true);
        $builder->add(
            'note',
            TextareaType::class,
            [
                "label" => false,
                'required'    => false,
            ]
        );
    }

    public function getBlockPrefix()
    {
        return 'autolettura_acqua_comunicazioni';
    }
}
