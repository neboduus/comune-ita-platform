<?php


namespace App\Form;


use App\Entity\Ente;
use App\Entity\Pratica;
use App\BackOffice\BackOfficeInterface;
use App\Services\BackOfficeCollection;
use App\Services\InstanceService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;


class IntegrationsType extends AbstractType
{
  /**
   * @var BackOfficeCollection
   */
  private $backOfficeCollection;
  /**
   * @var InstanceService
   */
  private $is;

  public function __construct(InstanceService $is, BackOfficeCollection $backOffices)
  {
    $this->is = $is;
    $this->backOfficeCollection = $backOffices;
  }

  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    $statuses = BackOfficeInterface::INTEGRATION_STATUSES;

    /** @var Ente $ente */
    $ente = $this->is->getCurrentInstance();

    $backOffices = [];
    /** @var BackOfficeInterface $b */
    foreach ($this->backOfficeCollection->getBackOffices() as $b) {
      if (in_array($b->getPath(), $ente->getBackofficeEnabledIntegrations())) {
        $backOffices[$b->getName()] = $b->getIdentifier();
      }
    }

    $builder
      ->add('trigger', ChoiceType::class, [
        'choices' => $statuses,
        'mapped' => false
      ])
      ->add('action', ChoiceType::class, [
        'choices' => $backOffices,
        'mapped' => false,
      ]);
  }


  public function getBlockPrefix()
  {
    return 'integrations_data';
  }
}
