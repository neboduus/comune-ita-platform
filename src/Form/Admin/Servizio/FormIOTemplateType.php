<?php

namespace App\Form\Admin\Servizio;

use App\Entity\Categoria;
use App\Entity\FormIO;
use App\Entity\SciaPraticaEdilizia;
use App\Entity\Servizio;
use App\Form\Extension\TestiAccompagnatoriProcedura;
use App\Model\FeedbackMessage;
use App\Model\FlowStep;
use App\Services\FormServerApiAdapterService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormError;


class FormIOTemplateType extends AbstractType
{
  /**
   * @var EntityManager
   */
  private $em;

  /**
   * @var FormServerApiAdapterService
   */
  private $formServerService;


  private $servicesI18nFields = [
    'name', 'description', 'who', 'howto', 'specialCases', 'moreInfo', 'compilationInfo', 'finalIndications', 'feedbackMessages', 'howToDo', 'whatYouNeed', 'whatYouGet', 'costs'
  ];

  /**
   * FormIOTemplateType constructor.
   * @param EntityManagerInterface $entityManager
   * @param FormServerApiAdapterService $formServerService
   */
  public function __construct(EntityManagerInterface $entityManager, FormServerApiAdapterService $formServerService)
  {
    $this->em = $entityManager;
    $this->formServerService = $formServerService;
  }

  /**
   * @param FormBuilderInterface $builder
   * @param array $options
   */
  public function buildForm(FormBuilderInterface $builder, array $options)
  {

    /** @var Servizio $servizio */
    $servizio = $builder->getData();
    $builder
      ->add('service_id', HiddenType::class,
        [
          'attr' => ['value' => $servizio->getFormIoId()],
          'mapped' => false,
          'required' => false,
        ])
      ->add('current_id', HiddenType::class,
        [
          'attr' => ['value' => $servizio->getId()],
          'mapped' => false,
          'required' => false,
        ]);
    $builder->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'onPreSubmit'));
    //$builder->addEventListener(FormEvents::POST_SUBMIT, array($this, 'onPostSubmit'));
  }

  public function onPreSubmit(FormEvent $event)
  {
    /** @var Servizio $servizio */
    $servizio = $event->getForm()->getData();

    if (isset($event->getData()['service_id']) && !empty($event->getData()['service_id'])) {

      if ( empty($servizio->getFormIoId()) ) {
        $serviceID = $event->getData()['service_id'];

        $response = false;
        if ( $serviceID == 'new' ) {
          $response = $this->formServerService->createForm($servizio);
        } else {
          $serviceToClone = $this->em->getRepository('App\Entity\Servizio')->find($serviceID);

          if ($serviceToClone instanceof Servizio) {
            $this->cloneService($servizio, $serviceToClone);
            $response = $this->formServerService->cloneForm($servizio, $serviceToClone);
            $this->cloneTranslations($servizio, $serviceToClone);
          }
        }

        if ($response['status'] == 'success') {

          $formId = $response['form_id'];
          $flowStep = new FlowStep();
          $flowStep
            ->setIdentifier($formId)
            ->setType('formio')
            ->addParameter('formio_id', $formId);

          $servizio->setFlowSteps([$flowStep]);

          // Backup
          $additionalData = $servizio->getAdditionalData();
          $additionalData['formio_id'] = $formId;
          $servizio->setAdditionalData($additionalData);

        } else {
          $event->getForm()->addError(
            new FormError($response['message'])
          );
        }

      }
    } else {
        $event->getForm()->addError(
          new FormError('Devi selezionare almeno un template per continuare')
        );
    }
    $this->em->persist($servizio);
  }

  /**
   * @param SciaPraticaEdilizia $pratica
   * @param TestiAccompagnatoriProcedura $helper
   * @return array
   */
  private function setupHelperData(FormIO $pratica, TestiAccompagnatoriProcedura $helper)
  {
    return json_encode($pratica->getDematerializedForms());
  }

  private function cloneService(Servizio $service, Servizio $ServiceToClone)
  {

    $service->setName($ServiceToClone->getName() . " (copia)");
    $service->setDescription($ServiceToClone->getDescription() ?? '');
    $service->setShortDescription($ServiceToClone->getShortDescription() ?? $ServiceToClone->getName());
    $service->setHowto($ServiceToClone->getHowto() ?? '');
    $service->setHowToDo($ServiceToClone->getHowToDo() ?? '');
    $service->setWhatYouNeed($ServiceToClone->getWhatYouNeed() ?? '');
    $service->setWhatYouGet($ServiceToClone->getWhatYouGet() ?? '');
    $service->setCosts($ServiceToClone->getCosts() ?? '');
    $service->setWho($ServiceToClone->getWho() ?? '');
    $service->setSpecialCases($ServiceToClone->getSpecialCases() ?? '');
    $service->setMoreInfo($ServiceToClone->getMoreInfo() ?? '');
    $service->setConstraints($ServiceToClone->getConstraints());
    $service->setConditions($ServiceToClone->getConditions());
    $service->setTimesAndDeadlines($ServiceToClone->getTimesAndDeadlines());
    $service->setLifeEvents($ServiceToClone->getLifeEvents());
    $service->setBusinessEvents($ServiceToClone->getBusinessEvents());
    $service->setBookingCallToAction($ServiceToClone->getBookingCallToAction());
    $service->setCompilationInfo($ServiceToClone->getCompilationInfo() ?? '');
    $service->setFinalIndications($ServiceToClone->getFinalIndications() ?? '');
    $service->setCoverage($ServiceToClone->getCoverage() ?? '');
    $service->setHandler($ServiceToClone->getHandler() ?? 'default');
    $service->setServiceGroup($ServiceToClone->getServiceGroup() ?? null);
    $service->setAccessLevel($ServiceToClone->getAccessLevel() ?? Servizio::ACCESS_LEVEL_SPID_L2);
    $service->setLoginSuggested($ServiceToClone->isLoginSuggested() ?? false);
    $service->setSticky($ServiceToClone->isSticky() ?? false);
    $service->setProtocolRequired($ServiceToClone->isProtocolRequired() ?? false);
    $service->setProtocolHandler($ServiceToClone->getProtocolHandler());
    $service->setProtocolloParameters($ServiceToClone->getProtocolloParameters() ?? []);
    $service->setPaymentRequired($ServiceToClone->isPaymentRequired() ?? false);
    $service->setPaymentParameters($ServiceToClone->getPaymentParameters() ?? []);
    $service->setIntegrations($ServiceToClone->getIntegrations() ?? []);
    $service->setTopics($ServiceToClone->getTopics());
    $service->setRecipients($ServiceToClone->getRecipients());
    $service->setGeographicAreas($ServiceToClone->getGeographicAreas());
    // If the category is not valid, the standard category is already set
    if ($ServiceToClone->getTopics() && $ServiceToClone->getTopics() instanceof Categoria)
    {
      $service->setTopics($ServiceToClone->getTopics());
    }

    $feedbackMessages = [];
    $defaultFeedbackMessages = $service->getFeedbackMessages();
    foreach ($ServiceToClone->getFeedbackMessages() as $status => $item) {
      $feedbackMessage = new FeedbackMessage();
      $feedbackMessage->setIsActive($item['isActive'] ?? $item['is_active']);
      $feedbackMessage->setMessage($item['message']);
      $feedbackMessage->setName($item['name']);

      $defaultMessage = $defaultFeedbackMessages[$status];
      // In precedenza la creazione di un servizio da template non copiava l'oggetto del messaggio quindi ci sono
      // dei servizi che non lo hanno valorizzato. I messaggi di default vengono pre popolati alla creazione di un
      // servizio quindi se il servizio che si cerca d'importare non ha un oggetto viene utilizzato il valore di default
      $feedbackMessage->setSubject($item['subject'] ?? $defaultMessage['subject']);
      $feedbackMessage->setTrigger($status);
      $feedbackMessages[] = $feedbackMessage;
    }
    $service->setFeedbackMessages($feedbackMessages);
  }

  public function getBlockPrefix()
  {
    return 'formio_template';
  }


  private function cloneTranslations(Servizio $service, Servizio $ServiceToClone){

    // Clone translations
    $repository = $this->em->getRepository('Gedmo\Translatable\Entity\Translation');
    $translations = $repository->findTranslations($ServiceToClone);
    foreach ($translations as $key => $value){
      foreach ($this->servicesI18nFields as $field) {
        if(isset($value[$field])){
          $repository->translate($service, $field, $key, $value[$field]);
        }
      }
    }
    $this->em->persist($service);
    $this->em->flush();
  }
}
