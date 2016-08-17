<?php

namespace AppBundle\Form\Base;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;


class ComponenteNucleoFamiliareType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder->add('nome')
            ->add('cognome')
            ->add('codiceFiscale')
            ->add('rapportoParentela');
    }
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\ComponenteNucleoFamiliare',
        ));
    }

    public function getBlockPrefix()
    {
        return 'componente_nucleo_familiare';
    }
}
