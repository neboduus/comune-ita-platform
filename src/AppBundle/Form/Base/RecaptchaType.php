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


class RecaptchaType extends AbstractType
{
  public function buildForm(FormBuilderInterface $builder, array $options)
  {

    $builder
      ->add('recaptcha', EWZRecaptchaType::class,
        [
          'label' => 'Controllo antispam',
          'mapped' => false,
          'constraints' => array(
            new RecaptchaTrue()
          ),
        ]);
  }

  public function getBlockPrefix()
  {
    return 'pratica_recaptcha';
  }
}
