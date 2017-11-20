<?php

namespace AppBundle\Form\Base;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class BlockQuoteType extends AbstractType
{

    public function getBlockPrefix()
    {
        return 'blockquote';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'required' => false,
                'mapped'   => false,
                'label'    => false
            )
        );
    }
}