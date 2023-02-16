<?php

namespace App\Form;

use App\Entity\OperatoreUser;
use App\Services\InstanceService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;


class OperatoreUserType extends AbstractType
{

  /**
   * @var EntityManager
   */
  private $em;

  /**
   * @var InstanceService
   */
  private $instanceService;

  public function __construct(EntityManagerInterface $entityManager, InstanceService $instanceService)
  {
    $this->em = $entityManager;
    $this->instanceService = $instanceService;
  }
  /**
   * {@inheritdoc}
   */
  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    /** @var OperatoreUser $operatore */
    $operatore = $builder->getData();

    $serviziAbilitati = $operatore->getServiziAbilitati()->toArray();

    $erogatori = $this->instanceService->getCurrentInstance()->getErogatori()->toArray();
    $servizi = [];
    foreach($erogatori as $erogatore) {
      $serviziErogati = $erogatore->getServizi()->toArray();
      $servizi = array_merge($servizi, $serviziErogati);
    }



    $serviceChoices = [];
    $serviceNames = [];
    foreach ($servizi as $s) {
      $serviceNames[$s->getId()] = $s->getName();
      if($s->getServiceGroup()){
        if (array_key_exists($s->getServiceGroup()->getName(),$serviceChoices)){
          $serviceChoices[$s->getServiceGroup()->getName().'services'][$s->getId()] = $s->getId();
        }else{
          $serviceChoices[$s->getServiceGroup()->getName()] = 'group';
          $serviceChoices[$s->getServiceGroup()->getName().'services'] = array(
           $s->getId() => $s->getId()
          );
        }
      }else{
        $serviceChoices[$s->getId()] = $s->getId();
      }
    }


    $builder
      ->add('nome', TextType::class, [
        'label' => 'general.nome'
      ])
      ->add('cognome', TextType::class, [
        'label' => 'general.cognome'
      ])
      ->add('username', TextType::class, [
        'label' => 'general.username'
      ])
      ->add('email', EmailType::class, [
        'label' => 'general.email'
      ])
      ->add('enabled', CheckboxType::class, [
        'label' => 'general.enabled',
        'required' => false
      ])
      ->add('services', ChoiceType::class, [
        'label' => 'operatori.servizi_abilitati',
        'data' => $serviziAbilitati,
        'choices' => $serviceChoices,
        'choice_label' => function ($choice, $key, $value) use ($serviceNames) {
          if ($choice === 'group') {
            return $key;
          }
          return $serviceNames[$choice];
        },
        'mapped' => false,
        'expanded' => true,
        'multiple' => true,
        'required' => false
      ]);

    if ($operatore->getUsername() && !$operatore->isSystemUser()) {
      // Show the checkbox only in case of editing of an existing operator and if the operator is not a system user
      $builder->add('system_user', CheckboxType::class, [
        'label' => 'general.system_user',
        'help' => 'general.system_user_helper',
        'required' => false
      ]);
    }

    $builder->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'onPreSubmitWithGroups'));
  }

  public function onPreSubmitWithGroups(FormEvent $event)
  {
    /** @var OperatoreUser $operatore */
    $operatore = $event->getForm()->getData();
    $serviziAbilitati = new ArrayCollection();
    $data = $event->getData();

    if (isset($data['services']) && !empty($data['services'])) {
      foreach ($data['services'] as $k => $s) {
        if($s != 'group'){
          $serviziAbilitati->add($s);
        }
      }
      unset($data['services']);
      $event->setData($data);
    }
    $operatore->setServiziAbilitati($serviziAbilitati);
  }

  public function onPreSubmit(FormEvent $event)
  {
    /** @var OperatoreUser $operatore */
    $operatore = $event->getForm()->getData();
    $serviziAbilitati = new ArrayCollection();
    $data = $event->getData();

    if (isset($data['services']) && !empty($data['services'])) {
      foreach ($data['services'] as $s) {
        $serviziAbilitati->add($s);
      }
    }
    $operatore->setServiziAbilitati($serviziAbilitati);
  }

  /**
   * {@inheritdoc}
   */
  public function configureOptions(OptionsResolver $resolver)
  {
    $resolver->setDefaults(array(
      'data_class' => 'App\Entity\OperatoreUser'
    ));
  }

  /**
   * {@inheritdoc}
   */
  public function getBlockPrefix()
  {
    return 'App_operatoreuser';
  }


}
