<?php

namespace AppBundle\Form\CambioResidenza;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;

class AttualmenteResidentiType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('persone_residenti', CollectionType::class, [
                'entry_type' => PersonaResidenteType::class,
                'allow_add' => true,
                'label' => false,
            ]);
    }

    public function getBlockPrefix()
    {
        return 'cambio_residenza_persone_attualmente_residenti';
    }
}
