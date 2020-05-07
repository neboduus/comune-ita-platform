<?php

namespace App\Form\Base;

use App\Entity\Pratica;
use App\Form\Extension\TestiAccompagnatoriProcedura;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class UsoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var TestiAccompagnatoriProcedura $helper */
        $helper = $options["helper"];

        $helper->setStepTitle('steps.common.uso.title', true);
        $helper->setDescriptionText('steps.common.uso.description', true);
        $helper->setGuideText('steps.common.uso.guida_alla_compilazione', true);

        /** @var Pratica $pratica */
        $pratica = $builder->getData();

        // FIXME: trovare un modo di farsi restituire il type --> https://stackoverflow.com/questions/33360211/how-to-select-discriminator-column-in-doctrine-2
        $class = new \ReflectionClass($pratica);


        $builder
            ->add('uso_certificato_anagrafico', TextType::class, [
                'required' => true,
                'label' => 'steps.common.uso.uso'
            ]);
        ;
    }

    public function getBlockPrefix()
    {
        return 'pratica_uso';
    }
}
