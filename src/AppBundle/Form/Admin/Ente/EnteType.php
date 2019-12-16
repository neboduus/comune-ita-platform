<?php


namespace AppBundle\Form\Admin\Ente;


use AppBundle\Entity\Ente;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;


class EnteType extends AbstractType
{

  /**
   * @var EntityManager
   */
  private $em;

  public function __construct(EntityManager $entityManager)
  {
    $this->em = $entityManager;
  }

  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    /** @var Ente $ente */
    $ente = $builder->getData();
    $availableGateways = $this->em->getRepository('AppBundle:PaymentGateway')->findBy([
      'enabled' => 1
    ]);
    $gateways = [];
    foreach ($availableGateways as $g) {
      $gateways[$g->getName()]= $g->getIdentifier();
    }

    $builder
      ->add('codice_meccanografico', TextType::class)
      ->add('site_url', TextType::class)
      ->add('codice_amministrativo', TextType::class)
      ->add('meta', TextareaType::class, ['required' => false])
      ->add('gateways', ChoiceType::class, [
        'choices' => $gateways,
        'expanded' => true,
        'multiple' => true,
        'required' => false,
        'label' => 'Seleziona i metodi di pagamento disponibili per l\'ente',
      ])
      ->add('save', SubmitType::class, ['label' => 'Salva']);

    $builder->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'onPreSubmit'));
  }

  public function onPreSubmit(FormEvent $event)
  {
    /*$data = $event->getData();
    if (!$data['gateways'] instanceof ArrayCollection) {
      $gateways = $data['gateways'];
      $data['gateways'] = new ArrayCollection($gateways);
    }*/
  }

  public function getBlockPrefix()
  {
    return 'ente';
  }
}
