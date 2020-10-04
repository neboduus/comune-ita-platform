<?php


namespace App\Form\Base;


use EWZ\Bundle\RecaptchaBundle\Form\Type\EWZRecaptchaType;
use EWZ\Bundle\RecaptchaBundle\Validator\Constraints\IsTrue as RecaptchaTrue;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class MessageType
 */
class SummaryType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
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
    return 'pratica_summary';
  }
}
