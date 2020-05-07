<?php

namespace App\Form\Base;

use App\Entity\ComponenteNucleoFamiliare;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ComponenteNucleoFamiliareType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('nome', TextType::class, ['required' => true])
            ->add('cognome', TextType::class, ['required' => true])
            ->add('codiceFiscale', TextType::class, ['required' => true])
            ->add('rapportoParentela', TextType::class, ['required' => true]);
    }
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => ComponenteNucleoFamiliare::class,
        ));
    }

    public function getBlockPrefix()
    {
        return 'componente_nucleo_familiare';
    }
}
