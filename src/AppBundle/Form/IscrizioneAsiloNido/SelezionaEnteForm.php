<?php

namespace AppBundle\Form\IscrizioneAsiloNido;

use AppBundle\Entity\Pratica;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;


class SelezionaEnteForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var Pratica $pratica */
        $pratica = $builder->getData();
        $enti = $pratica->getServizio()->getEnti();

        $builder->add('ente', EntityType::class, [
            'class' => 'AppBundle\Entity\Ente',
            'choices' => $enti,
            'expanded' => false,
            'multiple' => false
        ]);
    }

    public function getBlockPrefix()
    {
        return 'iscrizione_asilo_nido_seleziona_ente';
    }
}