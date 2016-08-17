<?php

namespace AppBundle\Form\Base;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;

class NucleoFamiliareType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('nucleo_familiare', CollectionType::class, [
            "entry_type" => ComponenteNucleoFamiliareType::class,
            "entry_options" => ["label" => false],
            "allow_add" => true,
            "allow_delete" => true,
        ]);
    }

    public function getBlockPrefix()
    {
        return 'nucleo_familiare';
    }
}
