<?php

namespace AppBundle\Form\Base;

use AppBundle\Entity\ComponenteNucleoFamiliare;
use AppBundle\Form\Base\ComponenteNucleoFamiliareForm;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\VarDumper\VarDumper;

class NucleoFamiliareForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('nucleo_familiare', CollectionType::class, [
            "entry_type" => ComponenteNucleoFamiliareForm::class,
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