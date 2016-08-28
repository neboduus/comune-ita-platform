<?php

namespace AppBundle\Form\Base;

use AppBundle\Form\Extension\TestiAccompagnatoriProcedura;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;

class NucleoFamiliareType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var TestiAccompagnatoriProcedura $helper */
        $helper = $options["helper"];
        $helper->setGuideText('pratica.guida_alla_compilazione.nucleo_familiare', true);

        $builder->add('nucleo_familiare', CollectionType::class, [
            "entry_type" => ComponenteNucleoFamiliareType::class,
            "entry_options" => ["label" => false],
            "allow_add" => true,
            "allow_delete" => true,
            "by_reference" => false,
            "label" => false,
        ]);
    }

    public function getBlockPrefix()
    {
        return 'nucleo_familiare';
    }
}
