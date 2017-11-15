<?php

namespace AppBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

class HelpDataTypeExtension extends AbstractTypeExtension
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var string
     */
    private $prefix;

    public function __construct(TranslatorInterface $translator, $prefix)
    {
        $this->translator = $translator;
        $this->prefix = $prefix;
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
        $resolver->setDefaults(['helper' => new TestiAccompagnatoriProcedura($this->translator, $this->prefix)]);
    }

    public function getExtendedType()
    {
        return FormType::class;
    }
}
