<?php


namespace AppBundle\Form\Admin\Ente;


use AppBundle\Model\DefaultProtocolSettings;
use AppBundle\Model\Mailer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MailerType extends AbstractType
{
  /**
   * {@inheritdoc}
   */
  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    $builder
      ->add('title', TextType::class, [
        'label' => 'backoffice.entity.title',
        'required' => true
      ])
      ->add('host', TextType::class, [
        'label' => 'backoffice.entity.host',
        'required' => true
      ])
      ->add('port', IntegerType::class, [
        'label' => 'Port',
        'required' => true,
        'attr' => array('min' => 1, 'max' => 65535)
      ])
      ->add('user', TextType::class, [
        'label' => 'User',
        'required' => true
      ])
      ->add('password', TextType::class, [
        'label' => 'Password',
        'required' => true
      ])
      ->add('encription', ChoiceType::class, [
        'label' => 'backoffice.entity.encription',
        'required' => true,
        'choices' => [
          'SSL' => 'SSl',
          'TLS' => 'TLS',
          'STARTTLS' => 'STARTTLS',
        ],

      ])
      ->add('sender', EmailType::class, [
        'label' =>  'backoffice.entity.sender' ,
        'required' => true
      ])
    ;
  }

  /**
   * {@inheritdoc}
   */
  public function configureOptions(OptionsResolver $resolver)
  {
    $resolver->setDefaults(array(
      'data_class' => Mailer::class,
      'csrf_protection' => false
    ));
  }
}
