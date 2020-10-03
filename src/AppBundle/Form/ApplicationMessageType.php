<?php

namespace AppBundle\Form;

use AppBundle\Entity\Message;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ApplicationMessageType extends AbstractType
{
  /**
   * @var EntityManager
   */
  private $em;

  public function __construct(EntityManagerInterface $entityManager)
  {
    $this->em = $entityManager;
  }

  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    $builder->add(
      'message', TextareaType::class, [
      'label' => 'operatori.messaggi.testo_messaggio',
      'required' => true,
      'attr' => [
        'rows' => '5',
        'class' => 'form-control input-inline',
      ],
    ])
      ->add(
        "attachments",
        HiddenType::class,
        [
          "mapped" => false,
          "label" => 'operatori.messaggi.allega_label',
          "required" => false,
        ]
      )
      ->add('applicant', SubmitType::class, [
        'label' => 'operatori.messaggi.invia',
        'attr' => [
          'class' => 'btn btn-primary'
        ]
      ])
      ->add('internal', SubmitType::class, [
        'label' => 'operatori.messaggi.aggiungi_nota_privata',
        'attr' => [
          'class' => 'btn btn-primary'
        ]
      ]);

      $builder->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'onPreSubmit'));

  }

  /**
   * FormEvents::PRE_SUBMIT $listener
   *
   * @param FormEvent $event
   */
  public function onPreSubmit(FormEvent $event)
  {
    $data = $event->getData();
    /** @var Message $message */
    $message = $event->getForm()->getData();

    $attachments = json_decode($data['attachments'], true);
    if ($attachments) {
      foreach ($attachments as $attachment) {
        if (isset($attachment['id'])) {
          $allegato = $this->em->getRepository('AppBundle:AllegatoMessaggio')->findOneBy(['id' => $attachment['id']]);
          if ($allegato) {
            $message->addAttachment($allegato);
          }
        }
      }
    }
  }

  public function configureOptions(OptionsResolver $resolver)
  {
    $resolver->setDefaults(array(
      'csrf_protection' => false
    ));
  }

  public function getBlockPrefix()
  {
    return 'message';
  }
}
