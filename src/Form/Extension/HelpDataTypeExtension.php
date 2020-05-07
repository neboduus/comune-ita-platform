<?php

namespace App\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class HelpDataTypeExtension extends AbstractTypeExtension
{
    private $testiAccompagnatoriProcedura;

    public function __construct(TestiAccompagnatoriProcedura $testiAccompagnatoriProcedura)
    {
        $this->testiAccompagnatoriProcedura = $testiAccompagnatoriProcedura;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->setAttribute('helper', $options['helper']);
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['helper'] = $options['helper'];
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(['helper' => $this->testiAccompagnatoriProcedura]);
    }

    public static function getExtendedTypes()
    {
        return [FormType::class];
    }
}
