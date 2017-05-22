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
        $helper->setGuideText('steps.autolettura_acqua.note.guida_alla_compilazione', true);
        $helper->setStepTitle('steps.autolettura_acqua.note.title', true);
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
