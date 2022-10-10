<?php


namespace App\Dto;

use App\Entity\Categoria;
use App\Entity\ServiceGroup;
use App\Entity\Servizio;
use App\Model\Service;
use App\Services\Manager\BackofficeManager;
use Doctrine\Common\Collections\ArrayCollection;
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
   * @param RouterInterface $router
   */
  public function __construct(RouterInterface $router)
  {
    $this->router = $router;
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
    $service->setName($servizio->getName());
    $service->setSlug($servizio->getSlug());
    $service->setTenant($servizio->getEnteId());

    $service->setTopics($servizio->getTopics() ? $servizio->getTopics()->getSlug() : null);
    $service->setTopicsId($servizio->getTopics() ? $servizio->getTopics()->getId() : null);
    $service->setShortDescription($servizio->getShortDescription() ?? '');
    $service->setDescription($servizio->getDescription() ?? '');
    $service->setHowto($servizio->getHowto());
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

    $entity->setName($service->getName());
    $entity->setSlug($service->getSlug());

    // Avoid validation error on patch
    if ($service->getTopics() instanceof Categoria) {
      $entity->setTopics($service->getTopics());
    }

    $entity->setDescription($service->getDescription() ?? '');
    $entity->setHowto($service->getHowto());
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
    $entity->setSticky($service->isSticky());
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

    $entity->setAllowReopening($service->isAllowReopening());
    $entity->setAllowWithdraw($service->isAllowWithdraw());
    $entity->setAllowIntegrationRequest($service->isAllowIntegrationRequest());
    $entity->setWorkflow($service->getWorkflow());
    $entity->setMaxResponseTime($service->getMaxResponseTime());
    $entity->setHowToDo($service->getHowToDo());
    $entity->setWhatYouNeed($service->getWhatYouNeed());
    $entity->setWhatYouGet($service->getWhatYouGet());
    $entity->setCosts($service->getCosts());

    $entity->setRecipients(new ArrayCollection($service->getRecipientsId()));
    $entity->setGeographicAreas(new ArrayCollection($service->getGeographicAreasId()));

    $entity->setLifeEvents($service->getLifeEvents());
    $entity->setBusinessEvents($service->getBusinessEvents());

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
    return $data;
  }

}
