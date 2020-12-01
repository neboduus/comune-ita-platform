<?php

namespace AppBundle\Form\Rest\Transition;


use AppBundle\Dto\Message;
use AppBundle\Entity\Servizio;
use AppBundle\Form\Rest\FileType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;

class OutcomeFormType extends AbstractType
{

  private $rootDir;
  private $allowedExtensions;

  public function __construct($rootDir, $allowedExtensions)
  {
    $this->rootDir = $rootDir;
    $this->allowedExtensions = array_merge(...$allowedExtensions);
  }

  /**
   * @param FormBuilderInterface $builder
   * @param array $options
   */
  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    $builder
      ->add('message', TextType::class, [
        'constraints' => [
          new NotBlank(),
          new NotNull(),
        ]
      ])
      ->add('attachments', CollectionType::class, [
        'entry_type' => FileType::class,
        "allow_add" => true,
        "allow_delete" => true,
        'prototype' => true,
        "label" => false
      ]);
  }

  public function onPreSubmit(FormEvent $event)
  {
    $data = $event->getData();
    foreach ($data->getAttachments() as $attachment) {
      if ($attachment->getId() == null) {
        if (!in_array($attachment->getMimeType(), $this->allowedExtensions)) {
          return $event->getForm()->addError(
            new FormError('Mime type non valido')
          );
        }

        $extension = explode('.', $attachment->getName());
        if (count($extension) < 2) {
          return $event->getForm()->addError(
            new FormError('E\'obbligatorio specificare l\'estensione del file le campo name')
          );

        } else if (!array_key_exists(end($extension), $this->allowedExtensions)) {
          return $event->getForm()->addError(new FormError('Estensione non valida'));
        }

        if (empty($attachment->getFile())) {
          return $event->getForm()->addError(new FormError('Il campo file è obbligatorio'));
        }
      }
    }
  }

  /**
   * @param OptionsResolver $resolver
   */
  public function configureOptions(OptionsResolver $resolver)
  {
    $resolver->setDefaults(array(
      'allow_extra_fields' => true,
      'csrf_protection' => false
    ));
  }

}
