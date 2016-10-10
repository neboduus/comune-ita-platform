<?php

namespace AppBundle\Form\Base;

use AppBundle\Form\Extension\TestiAccompagnatoriProcedura;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;


class SelezionaEnteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var TestiAccompagnatoriProcedura $helper */
        $helper = $options["helper"];
        $helper->setGuideText('pratica.guida_alla_compilazione.seleziona_ente', true);

        $builder->add('ente', EntityType::class, [
            'class' => 'AppBundle\Entity\Ente',
            'choices' => $builder->getData()->getServizio()->getEnti(),
            'choice_label' => 'name',
            'expanded' => false,
            'multiple' => false
        ]);
    }

    public function getBlockPrefix()
    {
        return 'pratica_seleziona_ente';
    }
}
