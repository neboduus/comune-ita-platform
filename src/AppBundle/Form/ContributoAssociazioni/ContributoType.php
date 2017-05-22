<?php

namespace AppBundle\Form\ContributoAssociazioni;

use AppBundle\Entity\ContributoAssociazioni;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class ContributoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var TestiAccompagnatoriProcedura $helper */
        $helper = $options["helper"];
        $helper->setGuideText('steps.contributo_associazioni.contributo.guida_alla_compilazione', true);
        $helper->setStepTitle('steps.contributo_associazioni.contributo.title', true);

        /** @var ContributoAssociazioni $pratica */
        $pratica = $builder->getData();
        $choices = array();
        foreach ($pratica->getTipologieUsoContributo() as $type) {
            $choices[$helper->translate('steps.contributo_associazioni.contributo.' . $type)] = $type;
        }

        $builder
            ->add('usoContributo', ChoiceType::class, [
                'choices' => $choices,
                'expanded' => true,
                'multiple' => false,
                'label' => false,
            ])
            ->add('descrizioneContributo', TextareaType::class, ["label" => 'steps.contributo_associazioni.contributo.descrizione'])
            ->add('annoAttivita', TextType::class, ["label" => 'steps.contributo_associazioni.contributo.anno']);
    }

    public function getBlockPrefix()
    {
        return 'contributo_associazioni_contributo';
    }
}
