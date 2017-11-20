<?php

namespace AppBundle\Form\Base;

use AppBundle\Entity\Pratica;
use AppBundle\Form\Extension\TestiAccompagnatoriProcedura;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class CertificatoAnagraficoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var TestiAccompagnatoriProcedura $helper */
        $helper = $options["helper"];
        $helper->setGuideText('steps.common.certificato_anagrafico.guida_alla_compilazione', true);
        $helper->setStepTitle('steps.common.certificato_anagrafico.title', true);

        /** @var Pratica $pratica */
        $pratica = $builder->getData();

        $choices = array();
        foreach ($pratica->getTipologieCertificatoAnagrafico() as $certificatoAnagrafico) {
            $choices[$helper->translate('steps.common.certificato_anagrafico.tipologie.' . $certificatoAnagrafico)] = $certificatoAnagrafico;
        }

        $builder->add('tipologia_certificato_anagrafico', ChoiceType::class, [
            'choices' => $choices,
            'expanded' => true,
            'multiple' => false,
            'required' => true,
            'label' => 'Specificare tipologia di certificato'
        ])
            ->add('uso_certificato_anagrafico', TextType::class, [
                'required' => true,
                'label' => 'Specifica uso:'
            ]);
        ;
    }

    public function getBlockPrefix()
    {
        return 'pratica_certificato_anagrafico';
    }
}
