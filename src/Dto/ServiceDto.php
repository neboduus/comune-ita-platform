<?php


namespace App\Dto;

use App\Entity\Categoria;
use App\Entity\Pratica;
use App\Entity\ServiceGroup;
use App\Entity\Servizio;
use App\Model\FeedbackMessage;
use App\Model\FeedbackMessages;
use App\Model\Service;
use App\Services\Manager\BackofficeManager;
use App\Services\VersionService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use ReflectionException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;


class ServiceDto extends AbstractDto
{
  /**
   * @var RouterInterface
   */
  private $router;

  private $baseUrl;

  private $version;

  /**
   * @var VersionService
   */
  private $versionService;

  /**
   * @var EntityManagerInterface
   */
  private $entityManager;

  /**
   * @param RouterInterface $router
   */
  public function __construct(RouterInterface $router, VersionService $versionService, EntityManagerInterface $entityManager)
  {
    $this->router = $router;
    $this->versionService = $versionService;
    $this->entityManager = $entityManager;
  }

  /**
   * @return RouterInterface
   */
  public function getRouter(): RouterInterface
  {
    return $this->router;
  }

  /**
   * @param RouterInterface $router
   */
  public function setRouter(RouterInterface $router): void
  {
    $this->router = $router;
  }

  /**
   * @return mixed
   */
  public function getBaseUrl()
  {
    return $this->baseUrl;
  }

  /**
   * @param mixed $baseUrl
   */
  public function setBaseUrl($baseUrl): void
  {
    $this->baseUrl = $baseUrl;
  }

  /**
   * @return int
   */
  public function getVersion(): int
  {
    return $this->version;
  }

  /**
   * @param int $version
   */
  public function setVersion(int $version): void
  {
    $this->version = $version;
  }

  /**
   * @param Servizio $servizio
   * @param $formServerUrl
   * @param bool $loadFileCollection default is true, if false: avoids additional queries for file loading
   * @param int $version
   * @return Service
   * @throws ReflectionException
   */
  public function fromEntity(Servizio $servizio, $formServerUrl, bool $loadFileCollection = true, int $version = 1): Service
  {

    $attachmentEndpointUrl = $this->router->generate('service_api_get', ['id' => $servizio->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

    $this->baseUrl = $attachmentEndpointUrl;
    $this->version = $version;

    $service = new Service();
    $service->setId($servizio->getId());
    $service->setIdentifier($servizio->getIdentifier());
    $service->setName($servizio->getName());
    $service->setSlug($servizio->getSlug());
    $service->setTenant($servizio->getEnteId());

    $service->setTopics($servizio->getTopics() ? $servizio->getTopics()->getSlug() : null);
    $service->setTopicsId($servizio->getTopics() ? $servizio->getTopics()->getId() : null);
    $service->setShortDescription($servizio->getShortDescription() ?? '');
    $service->setDescription($servizio->getDescription() ?? '');
    $service->setHowto($servizio->getHowto());
    $service->setHowToDo($servizio->getHowToDo());
    $service->setWho($servizio->getWho() ?? '');
    $service->setSpecialCases($servizio->getSpecialCases() ?? '');
    $service->setMoreInfo($servizio->getMoreInfo() ?? '');
    $service->setConstraints($servizio->getConstraints());
    $service->setTimesAndDeadlines($servizio->getTimesAndDeadlines());
    $service->setBookingCallToAction($servizio->getBookingCallToAction());
    $service->setConditions($servizio->getConditions());
    $service->setConditionsAttachments($this->preparePublicFiles($servizio->getConditionsAttachments(), $attachmentEndpointUrl));
    $service->setCoverage($servizio->getCoverage());

    $service->setCompilationInfo($servizio->getCompilationInfo() ?? '');
    $service->setFinalIndications($servizio->getFinalIndications() ?? '');
    $service->setFlowSteps($this->prepareFlowSteps($servizio->getFlowSteps(), $formServerUrl));
    $service->setProtocolRequired($servizio->isProtocolRequired());
    $service->setProtocolloParameters([]);
    $service->setPaymentRequired($servizio->getPaymentRequired());
    $service->setProtocolHandler($servizio->getProtocolHandler());
    $service->setPaymentParameters([]);
    $service->setIntegrations($this->decorateIntegrationsData($servizio->getIntegrations()));
    $service->setSticky($servizio->isSticky());
    $service->setStatus($servizio->getStatus());
    $service->setAccessLevel($servizio->getAccessLevel());
    $service->setLoginSuggested($servizio->isLoginSuggested());
    $service->setScheduledFrom($servizio->getScheduledFrom());
    $service->setScheduledTo($servizio->getScheduledTo());
    $service->setServiceGroupId($servizio->getServiceGroup() ? $servizio->getServiceGroup()->getId() : null);
    $service->setServiceGroup($servizio->getServiceGroup() ? $servizio->getServiceGroup()->getSlug() : null);

    $service->setSharedWithGroup($servizio->isSharedWithGroup());

    $userGroupId = [];
    if($servizio->getUserGroups()){
      foreach ($servizio->getUserGroups() as $userGroup){
        $userGroupId[] = $userGroup->getId();
      }
    }
    $service->setUserGroupIds($userGroupId);

    $service->setAllowReopening($servizio->isAllowReopening());
    $service->setAllowWithdraw($servizio->isAllowWithdraw());
    $service->setAllowIntegrationRequest($servizio->isAllowIntegrationRequest());
    $service->setWorkflow($servizio->getWorkflow());
    $service->setMaxResponseTime($servizio->getMaxResponseTime());
    $service->setHowto($servizio->getHowToDo());
    $service->setWhatYouNeed($servizio->getWhatYouNeed());
    $service->setWhatYouGet($servizio->getWhatYouGet());
    $service->setCosts($servizio->getCosts());
    $service->setSource($servizio->getSource());

    $service->setCostsAttachments($this->preparePublicFiles($servizio->getCostsAttachments(), $attachmentEndpointUrl));

    $recipients = [];
    $recipientsId = [];

    if ($servizio->getRecipients()) {
      foreach ($servizio->getRecipients() as $r) {
        $recipients[] = $r->getName();
        $recipientsId[] = $r->getId();
      }
    }

    $service->setRecipients($recipients);
    $service->setRecipientsId($recipientsId);

    $geographicAreas = [];
    $geographicAreasId = [];

    if ($servizio->getGeographicAreas()) {
      foreach ($servizio->getGeographicAreas() as $g) {
        $geographicAreas[] = $g->getName();
        $geographicAreasId[] = $g->getId();
      }
    }
    $service->setGeographicAreas($geographicAreas);
    $service->setGeographicAreasId($geographicAreasId);

    $service->setLifeEvents($servizio->getLifeEvents());
    $service->setBusinessEvents($servizio->getBusinessEvents());
    $service->setExternalCardUrl($servizio->getExternalCardUrl());

    $service->setFeedbackMessages($this->decorateFeedbackMessages($servizio->getFeedbackMessages()));

    $service->setAppVersion($this->versionService->getVersion());

    $service->setCreatedAt($servizio->getCreatedAt());
    $service->setUpdatedAt($servizio->getUpdatedAt());
    $service->setSatisfyEntrypointId($servizio->getSatisfyEntrypointId());

    return $service;
  }

  private function prepareFlowSteps($flowSteps, $formServerUrl)
  {
    if (empty($flowSteps)) {
      return $flowSteps;
    }
    foreach ($flowSteps as $flowStep) {
      $flowStep->addParameter("url", $formServerUrl . '/form/');
    }
    return $flowSteps;
  }

  /**
   * @param Servizio|null $entity
   * @return Servizio
   */
  public function toEntity(Service $service, Servizio $entity = null): ?Servizio
  {
    if (!$entity) {
      $entity = new Servizio();
    }

    $entity->setIdentifier($service->getIdentifier());
    $entity->setName($service->getName());
    $entity->setSlug($service->getSlug());

    // Avoid validation error on patch
    if ($service->getTopics() instanceof Categoria) {
      $entity->setTopics($service->getTopics());
    }

    $entity->setDescription($service->getDescription() ?? '');
    $entity->setShortDescription($service->getShortDescription() ?? '');
    $entity->setHowto($service->getHowto());
    $entity->setHowToDo($service->getHowToDo());
    $entity->setWho($service->getWho() ?? '');
    $entity->setSpecialCases($service->getSpecialCases() ?? '');
    $entity->setMoreInfo($service->getMoreInfo() ?? '');
    $entity->setConstraints($service->getConstraints());
    $entity->setTimesAndDeadlines($service->getTimesAndDeadlines());
    $entity->setBookingCallToAction($service->getBookingCallToAction());
    $entity->setConditions($service->getConditions());
    $entity->setCompilationInfo($service->getCompilationInfo() ?? '');
    $entity->setFinalIndications($service->getFinalIndications() ?? '');
    $entity->setCoverage(implode(',', (array)$service->getCoverage())); //@TODO
    $entity->setFlowSteps($service->getFlowSteps());
    $entity->setProtocolRequired($service->isProtocolRequired());
    $entity->setProtocolHandler($service->getProtocolHandler());
    $entity->setProtocolloParameters($service->getProtocolloParameters());
    $entity->setPaymentRequired($service->getPaymentRequired());
    $entity->setPaymentParameters($service->getPaymentParameters());
    $entity->setIntegrations($this->normalizeIntegrationsData($service->getIntegrations()));
    $entity->setSticky($service->isSticky() ?? false);
    $entity->setStatus($service->getStatus());
    $entity->setAccessLevel($service->getAccessLevel());
    $entity->setLoginSuggested($service->isLoginSuggested());
    $entity->setScheduledFrom($service->getScheduledFrom());
    $entity->setScheduledTo($service->getScheduledTo());
    $entity->setIOServiceParameters($service->getIoParameters());

    // Avoid validation error on patch
    if ($service->getServiceGroup() instanceof ServiceGroup) {
      $entity->setServiceGroup($service->getServiceGroup());
    }
    $entity->setSharedWithGroup($service->isSharedWithGroup());
    $userGroups = new ArrayCollection();
    foreach ($service->getUserGroupIds() as $userGroupId){
      $userGroup = $this->entityManager->getRepository('App\Entity\UserGroup')->find($userGroupId);
      $userGroups->add($userGroup);
    }
    $entity->replaceUserGroups($userGroups);

    $entity->setAllowReopening($service->isAllowReopening());
    $entity->setAllowWithdraw($service->isAllowWithdraw());
    $entity->setAllowIntegrationRequest($service->isAllowIntegrationRequest());
    $entity->setWorkflow($service->getWorkflow());
    $entity->setMaxResponseTime($service->getMaxResponseTime());
    $entity->setHowToDo($service->getHowToDo());
    $entity->setWhatYouNeed($service->getWhatYouNeed());
    $entity->setWhatYouGet($service->getWhatYouGet());
    $entity->setCosts($service->getCosts());

    $entity->setLifeEvents($service->getLifeEvents());
    $entity->setBusinessEvents($service->getBusinessEvents());
    $entity->setExternalCardUrl($service->getExternalCardUrl());

    if (!empty($service->getRecipients())) {
      $entity->setRecipients(new ArrayCollection($service->getRecipients()));
    }

    if (!empty($service->getGeographicAreas())) {
      $entity->setGeographicAreas(new ArrayCollection($service->getGeographicAreas()));
    }

    $entity->setSource($service->getSource());
    $entity->setFeedbackMessages($this->normalizeFeedbackMessages($service->getFeedbackMessages()));
    $entity->setSatisfyEntrypointId($service->getSatisfyEntrypointId());

    return $entity;
  }

  private function normalizeIntegrationsData($integrations): ?array
  {
    if (isset($integrations['trigger']) && $integrations['trigger']) {
      return [$integrations['trigger'] => BackofficeManager::getBackofficeClassByIdentifier($integrations['action'])];
    } else {
      return null;
    }
  }

  /**
   * @throws ReflectionException
   */
  private function decorateIntegrationsData($integrations): array
  {
    $data = [];
    if (empty($integrations)) {
      return $data;
    }

    foreach ($integrations as $status => $className) {
      $data["trigger"] = $status;
      $data["action"] = (new \ReflectionClass($className))->getConstant("IDENTIFIER");
    }
    return $data;
  }

  /**
   * @param $data
   * @return mixed
   */
  public static function normalizeData($data)
  {
    // Todo: find better way
    if (isset($data['flow_steps']) && count($data['flow_steps']) > 0) {
      $temp = [];
      foreach ($data['flow_steps'] as $f) {
        $f['parameters'] = \json_encode($f['parameters']);
        $temp[] = $f;
      }
      $data['flow_steps'] = $temp;
    }

    // Todo: find better way
    if (isset($data['payment_parameters']['gateways']) && count($data['payment_parameters']['gateways']) > 0) {
      $sanitizedGateways = [];
      foreach ($data['payment_parameters']['gateways'] as $gateway) {
        $parameters = \json_encode($gateway['parameters']);
        $gateway['parameters'] = $parameters;
        $sanitizedGateways [$gateway['identifier']] = $gateway;
      }
      $data['payment_parameters']['gateways'] = $sanitizedGateways;
    }

    // Todo: find better way
    if (isset($data['protocollo_parameters'])) {
      $data['protocollo_parameters'] = \json_encode($data['protocollo_parameters']);
    }

    if (isset($data['feedback_messages'])) {
      foreach ($data['feedback_messages'] as $k => $v) {
        $trigger = Pratica::getStatusCodeByName($k);
        $data['feedback_messages'][$k]['trigger'] = $trigger;
        $data['feedback_messages'][$k]['name'] = FeedbackMessage::STATUS_NAMES[$trigger];
      }
    }
    return $data;
  }

  /**
   * @param $feedbackMessages
   * @return FeedbackMessages
   */
  public static function decorateFeedbackMessages($feedbackMessages): FeedbackMessages
  {
    // Conversione dei messages del servizio da un array di oggetti di tipo FeedbackMessage a un oggetto
    // di tipo FeedbackMessages

    $messages = new FeedbackMessages();

    foreach ($feedbackMessages as $feedbackMessage) {
      // Se è di tipo array converto in FeedbackMessage
      if (!$feedbackMessage instanceof FeedbackMessage) {
        $temp = new FeedbackMessage();
        $temp->setName($feedbackMessage['name']);
        $temp->setTrigger($feedbackMessage['trigger']);
        $temp->setSubject($feedbackMessage['subject']);
        $temp->setMessage($feedbackMessage['message']);
        if (isset($feedbackMessage['isActive'])) {
          $temp->setIsActive($feedbackMessage['isActive']);
        } elseif (isset($feedbackMessage['is_active'])) {
          $temp->setIsActive($feedbackMessage['is_active']);
        }

        $feedbackMessage = $temp;
      }
      $messages->setMessageByStatusCode($feedbackMessage->getTrigger(), $feedbackMessage);
    }
    return $messages;
  }

  public function normalizeFeedbackMessages(FeedbackMessages $feedbackMessages): array
  {
    $data = array();
    $feedbackMessagesStatuses = array_keys(FeedbackMessage::STATUS_NAMES);
    foreach ($feedbackMessagesStatuses as $status) {
      $data[$status] = $feedbackMessages->getMessageByStatusCode($status);
    }
    return $data;
  }

}
