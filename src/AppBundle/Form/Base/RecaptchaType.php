<?php

namespace AppBundle\Form\Base;

use AppBundle\Entity\Pratica;
use AppBundle\Form\Extension\TestiAccompagnatoriProcedura;
use EWZ\Bundle\RecaptchaBundle\Form\Type\EWZRecaptchaType;
use EWZ\Bundle\RecaptchaBundle\Validator\Constraints\IsTrue as RecaptchaTrue;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;


class RecaptchaType extends AbstractType
{
  public function buildForm(FormBuilderInterface $builder, array $options)
  {

    $constraint = new RecaptchaTrue();
    $constraint->message = 'Questo valore non è un captcha valido.';
    $constraint->groups = ['recaptcha'];


    $builder
      ->add('recaptcha', EWZRecaptchaType::class,
        [
          'label' => false,
          'mapped' => false,
          'constraints' => [$constraint]
        ]);
  }

  public function getBlockPrefix()
  {
    return 'pratica_recaptcha';
  }
}
