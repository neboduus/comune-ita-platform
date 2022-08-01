<?php

namespace App\Form\Base;

use App\Entity\Pratica;
use App\Form\Extension\TestiAccompagnatoriProcedura;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\VarDumper\VarDumper;

class CertificatoAnagraficoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var TestiAccompagnatoriProcedura $helper */
        $helper = $options["helper"];

        /** @var Pratica $pratica */
        $pratica = $builder->getData();

        $class = new \ReflectionClass($pratica);

        $helper->setStepTitle('steps.common.certificato_anagrafico.'.$class->getShortName().'.title', true);
        $helper->setDescriptionText('steps.common.certificato_anagrafico.'.$class->getShortName().'.description', true);
        $helper->setGuideText('steps.common.certificato_anagrafico.'.$class->getShortName().'.guida_alla_compilazione', true);


        $choices = array();
        if (method_exists($pratica,'getTipologieCertificatoAnagrafico'))
        {
            foreach ($pratica->getTipologieCertificatoAnagrafico() as $certificatoAnagrafico) {
                $choices[$helper->translate('steps.common.certificato_anagrafico.tipologie.' . $certificatoAnagrafico)] = $certificatoAnagrafico;
            }
        }

        if (method_exists($pratica,'getTipologieCertificatoAnagrafico')) {
            $builder->add('tipologia_certificato_anagrafico', ChoiceType::class, [
                'choices' => $choices,
                'expanded' => true,
                'multiple' => false,
                'required' => true,
                'data'      => $pratica->getTipologiaCertificatoAnagrafico() ? $pratica->getTipologiaCertificatoAnagrafico() : array_values($choices)[0],
                'attr' => array('checked'   => 'checked'),
                'label' => 'Specificare tipologia di certificato'
            ])

            ->add('stato_estero_certificato_anagrafico', TextType::class, [
                'mapped'     => true,
                'required'   => false,
                'data'       => $pratica->getStatoEsteroCertificatoAnagrafico() ? $pratica->getStatoEsteroCertificatoAnagrafico() : '',
                'attr'       => $pratica->getTipologiaCertificatoAnagrafico() != 'internazionale' ? ['style' => 'display:none'] : [],
                'label'      => 'Specifica stato estero',
                'label_attr' => [
                    'style'  => !$pratica->getDelegaType() || $pratica->getDelegaType() == array_values($choices)[0] ? 'display:none' : '',
                    'id'     => 'pratica_certificato_anagrafico_stato_estero_certificato_anagrafico_label'
                ]
            ]);


        }
        $builder->add('uso_certificato_anagrafico', TextType::class, [
                'required' => true,
                'label' => 'steps.common.certificato_anagrafico.'.$class->getShortName().'.uso'
            ]);
        ;
    }

    public function getBlockPrefix()
    {
        return 'pratica_certificato_anagrafico';
    }
}
