<?php


namespace App\Form\Admin\Ente;


use App\Model\DefaultProtocolSettings;
use App\Model\Mailer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
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
        'label' => 'Titolo',
        'required' => true
      ])
      ->add('host', TextType::class, [
        'label' => 'Host',
        'required' => true
      ])
      ->add('port', TextType::class, [
        'label' => 'Port',
        'required' => true
      ])
      ->add('user', TextType::class, [
        'label' => 'User',
        'required' => true
      ])
      ->add('password', TextType::class, [
        'label' => 'Password',
        'required' => true
      ])
      ->add('encription', TextType::class, [
        'label' => 'Encription',
        'required' => true
      ])
      ->add('sender', EmailType::class, [
        'label' => 'Sender',
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
