<?php

namespace AppBundle\Form;

use AppBundle\Entity\OperatoreUser;
use AppBundle\Services\InstanceService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
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
    foreach ($servizi as $s) {
      if($s->getServiceGroup()){
        if (array_key_exists($s->getServiceGroup()->getName(),$serviceChoices)){
          $serviceChoices[$s->getServiceGroup()->getName().'services'][$s->getName()] = $s->getId();
        }else{
          $serviceChoices[$s->getServiceGroup()->getName()] = 'group';
          $serviceChoices[$s->getServiceGroup()->getName().'services'] = array(
           $s->getName() => $s->getId()
          );
        }
      }else{
        $serviceChoices[$s->getName()] = $s->getId();
      }
    }

    $builder
      ->add('nome')
      ->add('cognome')
      ->add('username')
      ->add('email')
      ->add('services', ChoiceType::class, [
        'data' => $serviziAbilitati,
        'choices' => $serviceChoices,
        'mapped' => false,
        'expanded' => true,
        'multiple' => true,
        'required' => false,
        'label' => 'Seleziona i servizi abilitati per l\'operatore'
      ]);

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
        }else{
          unset($data['services'][$k]);
        }
      }
      $data['services'] = $operatore->parseServizi($serviziAbilitati);
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
      'data_class' => 'AppBundle\Entity\OperatoreUser'
    ));
  }

  /**
   * {@inheritdoc}
   */
  public function getBlockPrefix()
  {
    return 'appbundle_operatoreuser';
  }


}
