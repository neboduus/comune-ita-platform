<?php

namespace AppBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SdcDataTypeExtension extends AbstractTypeExtension
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->setAttribute('sdc_data', $options['sdc_data']);
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['sdc_data'] = $form->getConfig()->getAttribute('sdc_data');
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefined('sdc_data')->setDefault('sdc_data', null);
    }

    public function getExtendedType()
    {
        return 'Symfony\Component\Form\Extension\Core\Type\FormType';
    }
}
