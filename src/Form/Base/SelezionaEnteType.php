<?php

namespace App\Form\Base;

use App\Form\Extension\TestiAccompagnatoriProcedura;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;


class SelezionaEnteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var TestiAccompagnatoriProcedura $helper */
        $helper = $options["helper"];
        $helper->setGuideText('steps.common.seleziona_ente.guida_alla_compilazione', true);
        $helper->setStepTitle('steps.common.seleziona_ente.title', true);

        $builder->add('ente', EntityType::class, [
            'class' => 'App\Entity\Ente',
            'choices' => $builder->getData()->getServizio()->getEnti(),
            'choice_label' => 'name',
            'expanded' => false,
            'multiple' => false,
            'label' => false,
        ]);
    }

    public function getBlockPrefix()
    {
        return 'pratica_seleziona_ente';
    }
}
