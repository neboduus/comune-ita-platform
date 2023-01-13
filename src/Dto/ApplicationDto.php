<?php


namespace App\Dto;

use App\Entity\Allegato;
use App\Entity\AllegatoMessaggio;
use App\Entity\Pratica;
use App\Entity\RichiestaIntegrazione;
use App\Entity\RispostaIntegrazione;
use App\Entity\RispostaIntegrazioneRepository;
use App\Entity\UserSession;
use App\Model\Application;
use App\Payment\GatewayCollection;
use App\Services\Manager\PraticaManager;
use App\Services\PraticaStatusService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class ApplicationDto extends AbstractDto
{

  /**
   * @var EntityManagerInterface
   */
  private $entityManager;

  /**
   * @var RouterInterface
   */
  private $router;

  private $baseUrl;

  private $version;
  /**
   * @var GatewayCollection
   */
  private $gatewayCollection;


  /**
   * @param EntityManagerInterface $entityManager
   * @param RouterInterface $router
   * @param GatewayCollection $gatewayCollection
   */
  public function __construct(EntityManagerInterface $entityManager, RouterInterface $router, GatewayCollection $gatewayCollection)
  {
    $this->entityManager = $entityManager;
    $this->router = $router;
    $this->gatewayCollection = $gatewayCollection;
  }

  /**
   * @return EntityManagerInterface
   */
  public function getEntityManager(): EntityManagerInterface
  {
    return $this->entityManager;
  }

  /**
   * @param EntityManagerInterface $entityManager
   */
  public function setEntityManager(EntityManagerInterface $entityManager): void
  {
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
   * @param Pratica $pratica
   * @param bool $loadFileCollection default is true, if false: avoids additional queries for file loading
   * @param int $version
   * @return Application
   */
  public function fromEntity(Pratica $pratica, bool $loadFileCollection = true, $version = 1): Application
  {

    $attachmentEndpointUrl = $this->router->generate('application_api_get', ['id' => $pratica->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

    $this->baseUrl = $attachmentEndpointUrl;
    $this->version = $version;

    $application = new Application();
    $application->setId($pratica->getId());
    $application->setUser($pratica->getUser()->getId());
    $application->setUserName($pratica->getUser()->getFullName());
    $application->setTenant($pratica->getEnte()->getId());
    $application->setService($pratica->getServizio()->getSlug());
    $application->setServiceId($pratica->getServizio()->getId());
    $application->setServiceName($pratica->getServizio()->getName());
    $application->setSubject($pratica->getOggetto() ?? $pratica->generateSubject());
    $application->setServiceGroupName($pratica->getServiceGroup() ? $pratica->getServiceGroup()->getName() : null);

    // Form data
    if ($pratica->getServizio()->getPraticaFCQN() == '\App\Entity\FormIO') {
      if ($version >= 2) {
        $application->setData($this->decorateDematerializedFormsV2($pratica->getDematerializedForms(), $attachmentEndpointUrl, $version));
      } else {
        $application->setData($this->decorateDematerializedForms($pratica->getDematerializedForms(), $attachmentEndpointUrl, $version));
      }
    } else {
      $application->setData($this->decorateLegacyDematerializedForms($pratica));
    }

    // Backoffice form data
    if ($pratica->getServizio()->getPraticaFCQN() == '\App\Entity\FormIO') {
      if ($version >= 2) {
        $application->setBackofficeData($this->decorateDematerializedFormsV2($pratica->getBackofficeFormData(), $attachmentEndpointUrl, $version));
      } else {
        $application->setBackofficeData($this->decorateDematerializedForms($pratica->getBackofficeFormData(), $attachmentEndpointUrl, $version));
      }
    } else {
      $application->setBackofficeData(null);
    }

    $application->setCompiledModules($loadFileCollection ? $this->prepareFileCollection($pratica->getModuliCompilati(), $attachmentEndpointUrl, $version) : []);

    $outcomeFile = ($loadFileCollection && $pratica->getRispostaOperatore() instanceof Allegato) ? $this->prepareFile($pratica->getRispostaOperatore(), $attachmentEndpointUrl, $version) : null;
    $application->setOutcomeFile($outcomeFile);

    $application->setOutcome($pratica->getEsito());
    $application->setOutcomeMotivation($pratica->getMotivazioneEsito());

    $application->setAttachments($this->prepareFileCollection($pratica->getAllegati(), $attachmentEndpointUrl));
    $application->setOutcomeAttachments($this->prepareFileCollection($pratica->getAllegatiOperatore(), $attachmentEndpointUrl));

    $application->setCreationTime($pratica->getCreationTime());
    try {
      $date = new \DateTime();
      $application->setCreatedAt($date->setTimestamp($pratica->getCreationTime()));
    } catch (\Exception $e) {
      $application->setCreatedAt($pratica->getCreationTime());
    }

    $application->setSubmissionTime($pratica->getSubmissionTime());
    if ($pratica->getSubmissionTime()) {
      try {
        $date = new \DateTime();
        $application->setSubmittedAt($date->setTimestamp($pratica->getSubmissionTime()));
      } catch (\Exception $e) {
        $application->setSubmittedAt($pratica->getSubmissionTime());
      }
    } else {
      $application->setSubmittedAt(null);
    }

    $application->setLatestStatusChangeTime($pratica->getLatestStatusChangeTimestamp());
    if ($pratica->getLatestStatusChangeTimestamp()) {
      try {
        $date = new \DateTime();
        $application->setLatestStatusChangeAt($date->setTimestamp($pratica->getLatestStatusChangeTimestamp()));
      } catch (\Exception $e) {
        $application->setLatestStatusChangeAt($pratica->getLatestStatusChangeTimestamp());
      }
    } else {
      $application->setLatestStatusChangeAt(null);
    }

    $application->setProtocolFolderNumber($pratica->getNumeroFascicolo());
    $application->setProtocolFolderCode($pratica->getCodiceFascicolo());
    $application->setProtocolNumber($pratica->getNumeroProtocollo());
    $application->setProtocolDocumentId($pratica->getIdDocumentoProtocollo());
    $application->setProtocolNumbers($pratica->getNumeriProtocollo()->toArray());

    if ($pratica->getProtocolTime()) {
      $application->setProtocolTime($pratica->getProtocolTime());
      try {
        $date = new \DateTime();
        $application->setProtocolledAt($date->setTimestamp($pratica->getProtocolTime()));
      } catch (\Exception $e) {
        $application->setProtocolledAt($pratica->getProtocolTime());
      }
    } else {
      $application->setProtocolledAt(null);
    }

    $application->setOutcome($pratica->getEsito());

    if ($pratica->getRispostaOperatore()) {
      $application->setOutcomeProtocolNumber($pratica->getRispostaOperatore()->getNumeroProtocollo());
      $application->setOutcomeProtocolDocumentId($pratica->getRispostaOperatore()->getIdDocumentoProtocollo());
      $application->setOutcomeProtocolNumbers($pratica->getRispostaOperatore()->getNumeriProtocollo()->toArray());
      if ($pratica->getRispostaOperatore()->getProtocolTime()) {
        $application->setOutcomeProtocolTime($pratica->getRispostaOperatore()->getProtocolTime());
        try {
          $date = new \DateTime();
          $application->setOutcomeProtocolledAt($date->setTimestamp($pratica->getRispostaOperatore()->getProtocolTime()));
        } catch (\Exception $e) {
          $application->setOutcomeProtocolledAt($pratica->getRispostaOperatore()->getProtocolTime());
        }
      }
    } else {
      $application->setOutcomeProtocolNumber(null);
      $application->setOutcomeProtocolDocumentId(null);
      $application->setOutcomeProtocolNumbers(null);
      $application->setOutcomeProtocolledAt(null);
    }

    $application->setPaymentType($pratica->getPaymentType());
    $application->setPaymentData($this->preparePaymentData($pratica));
    $application->setStatus($pratica->getStatus());
    $application->setStatusName(strtolower($pratica->getStatusName()));
    $application->setMeetings($this->getLinkedMeetingsIds($pratica));

    $application->setAuthentication(($pratica->getAuthenticationData()->getAuthenticationMethod() ?
      $pratica->getAuthenticationData() :
      UserAuthenticationData::fromArray(['authenticationMethod' => $pratica->getUser()->getIdp()])));

    // Fix for empty values
    if ($pratica->getSessionData() instanceof UserSession) {
      $sessionData = $pratica->getSessionData()->getSessionData();
      if (empty($application->getAuthentication()->offsetGet('sessionIndex')) && isset($sessionData['shibSessionIndex'])) {
        $application->getAuthentication()->offsetSet('sessionIndex', $sessionData['shibSessionIndex']);
      }
      if (empty($application->getAuthentication()->offsetGet('instant')) && isset($sessionData['shibAuthenticationIstant'])) {
        $application->getAuthentication()->offsetSet('instant', $sessionData['shibAuthenticationIstant']);
      }
    }

    $application->setLinks($this->getAvailableTransitions($pratica, $attachmentEndpointUrl, $version));
    $application->setIntegrations($this->prepareIntegrations($pratica));
    $application->setFlowChangedAt($pratica->getFlowChangedAt());
    $application->setUserCompilationNotes($pratica->getUserCompilationNotes());

    return $application;
  }

  private function prepareIntegrations(Pratica $pratica)
  {

    $integrations = [];
    /** @var RispostaIntegrazioneRepository $integrationAnswerRepo */
    $integrationAnswerRepo = $this->entityManager->getRepository('App\Entity\RispostaIntegrazione');

    $messageAttachementRepo = $this->entityManager->getRepository('App\Entity\AllegatoMessaggio');

    $attachmentsRepo = $this->entityManager->getRepository('App\Entity\Allegato');

    /** @var RichiestaIntegrazione $integrationRequest */
    foreach ($pratica->getRichiesteIntegrazione() as $integrationRequest) {

      $temp = [];
      $temp['outbound'] = $this->prepareFile($integrationRequest, $this->baseUrl, $this->version);
      if (!empty($integrationRequest->getAttachments())) {
        $attachments = $messageAttachementRepo->findBy(['id' => $integrationRequest->getAttachments()]);
        if (!empty($attachments)) {
          $temp['outbound']['attachments'] = $this->prepareFileCollection($attachments, $this->baseUrl, $this->version);
        }
      } else {
        $temp['outbound']['attachments'] = [];
      }

      $temp['inbound'] = null;
      $integrationAnswerCollection = $integrationAnswerRepo->findByIntegrationRequest($integrationRequest->getId());
      if (!empty($integrationAnswerCollection)) {
        /** @var RispostaIntegrazione $answer */
        $answer = $integrationAnswerCollection[0];

        $temp['inbound'] = $this->prepareFile($answer, $this->baseUrl, $this->version);

        $attachments = $attachmentsRepo->findBy(['id' => $answer->getAttachments()]);
        if (!empty($attachments)) {
          $temp['inbound']['attachments'] = $this->prepareFileCollection($attachments, $this->baseUrl, $this->version);
        } else {
          $temp['inbound']['attachments'] = [];
        }
      }

      $integrations[]= $temp;
    }
    return $integrations;
  }

  /**
   * @param Pratica $pratica
   * @return array
   */
  private function decorateLegacyDematerializedForms(Pratica $pratica): array
  {
    $decoratedData = [
      PraticaManager::APPLICANT_KEYS['name'] => $pratica->getRichiedenteNome(),
      PraticaManager::APPLICANT_KEYS['surname'] => $pratica->getRichiedenteCognome(),
      PraticaManager::APPLICANT_KEYS['email'] => $pratica->getRichiedenteEmail(),
      PraticaManager::APPLICANT_KEYS['fiscal_code'] => $pratica->getRichiedenteCodiceFiscale(),
    ];
    return $decoratedData;
  }

  public function decorateDematerializedForms($data, $attachmentEndpointUrl = '', $version = 1)
  {
    if (!isset($data['flattened'])) {
      return $data;
    }
    $decoratedData = $data['flattened'];
    foreach ($decoratedData as $k => $v) {

      if ($this->isUploadField($data['schema'], $k)) {
        $decoratedData[$k] = $this->prepareFormioFile($v, $attachmentEndpointUrl, $version);
      }

      if ($this->isDateField($k)) {
        $decoratedData[$k] = $this->prepareDateField($v);
      }
    }
    return $decoratedData;
  }

  public function decorateDematerializedFormsV2($data, $attachmentEndpointUrl = '', $version = 1)
  {

    if (!isset($data['flattened'])) {
      return $data;
    }

    $decoratedData = $data['flattened'];
    $keys = array_keys($decoratedData);

    $multiArray = array();

    foreach ($keys as $path) {
      $parts = explode('.', trim($path, '.'));
      $section = &$multiArray;
      $sectionName = '';

      $partsCount = count($parts);
      $counter = 0;

      foreach ($parts as $part) {
        $counter++;
        $sectionName = $part;

        // Salto data
        if ($part === 'data') {
          continue;
        }

        if (array_key_exists($sectionName, $section) === false) {
          $section[$sectionName] = array();
        }

        // Se è l'ultimo elemento assegno il valore
        if ($counter == $partsCount) {
          if ($this->isUploadField($data['schema'], $path)) {
            $section[$sectionName] = $this->prepareFormioFile($decoratedData[$path], $attachmentEndpointUrl, $version);
          } else if ($this->isDateField($path)) {
            $section[$sectionName] = $this->prepareDateField($decoratedData[$path]);
          } else {
            $section[$sectionName] = $decoratedData[$path];
          }
        }
        $section = &$section[$sectionName];

      }
    }

    return $multiArray;
  }

  private function inProtocolNumbers($needle, $protocolNumbers)
  {
    $found = false;

    foreach ($protocolNumbers as $protocolNumber) {
      $protocolNumber = json_decode(json_encode($protocolNumber), true);
      if ($protocolNumber["id"] == $needle["id"] && $protocolNumber["protocollo"] == $needle["protocollo"]) {
        $found = true;
      }
    }
    return $found;
  }

  /**
   * @param Pratica $pratica
   * @return mixed
   */
  public function preparePaymentData(Pratica $pratica)
  {
    if (!empty($pratica->getPaymentData())) {
      $availableGateways = $this->gatewayCollection->getAvailablePaymentGateways();
      $gatewayClassHandler = $availableGateways[$pratica->getPaymentType()]['handler'];
      return $gatewayClassHandler::getSimplifiedData($pratica->getPaymentData());
    }
    return [];
  }


  /**
   * @param Pratica $pratica
   * @param string $baseUrl
   * @return array
   */
  public function getAvailableTransitions(Pratica $pratica, $baseUrl = '', $version = 1)
  {
    $availableTransitions = [];
    if (isset(PraticaStatusService::TRANSITIONS_MAPPING[$pratica->getStatus()])) {
      $availableTransitions = PraticaStatusService::TRANSITIONS_MAPPING[$pratica->getStatus()];
      foreach ($availableTransitions as $k => $v) {
        // todo: fare refactoring completo della classe e generare con router
        $availableTransitions[$k]['url'] = $baseUrl . '/transition/' . $v['action'] . '?version=' . $version;

        if ($v['action'] == 'register' && !$pratica->getServizio()->isProtocolRequired()) {
          unset($availableTransitions[$k]);
        }

        if ($v['action'] == 'withdraw' && !$pratica->getServizio()->isAllowReopening()) {
          unset($availableTransitions[$k]);
        }
      }
    }
    return $availableTransitions;
  }

  public function getLinkedMeetingsIds(Pratica $pratica) {
    $meetings = [];
    foreach ($pratica->getMeetings() as $meeting) {
      $meetings[] = $meeting->getId();
    }
    return $meetings;
  }

  /**
   * @param Pratica|null $entity
   * @return Pratica
   */
  public function toEntity(Application $application, Pratica $entity = null)
  {
    if (!$entity) {
      $entity = new Pratica();
    }

    # Main document
    $entity->setNumeroProtocollo($application->getProtocolNumber());
    $entity->setNumeroFascicolo($application->getProtocolFolderNumber());
    $entity->setCodiceFascicolo($application->getProtocolFolderCode());
    $entity->setIdDocumentoProtocollo($application->getProtocolDocumentId());
    if ($application->getProtocolledAt()) {
      $entity->setProtocolTime($application->getProtocolledAt()->getTimestamp());
    }

    $applicationAttachments = array_merge($entity->getModuliCompilati()->getValues(), $entity->getAllegati()->getValues());

    foreach ($applicationAttachments as $attachment) {
      if ($application->getProtocolledAt()) {
        $attachment->setProtocolTime($application->getProtocolledAt()->getTimestamp());
      }
      $numeroDiProtocollo = [
        'id' => $attachment->getId(),
        'protocollo' => $application->getProtocolNumber(),
      ];

      if (!$this->inProtocolNumbers($numeroDiProtocollo, $entity->getNumeriProtocollo())) {
        $entity->addNumeroDiProtocollo($numeroDiProtocollo);
      }
    }

    # Outcome document
    $rispostaOperatore = $entity->getRispostaOperatore();
    if ($rispostaOperatore && $application->getOutcomeProtocolNumber()) {
      $rispostaOperatore->setNumeroProtocollo($application->getOutcomeProtocolNumber());
      $rispostaOperatore->setIdDocumentoProtocollo($application->getOutcomeProtocolDocumentId());
      if ($application->getOutcomeProtocolledAt()) {
        $rispostaOperatore->setProtocolTime($application->getOutcomeProtocolledAt()->getTimestamp());
      }

      $outcomeAttachments = array_merge([$entity->getRispostaOperatore()], $entity->getAllegatiOperatore()->getValues());

      foreach ($outcomeAttachments as $attachment) {
        if ($application->getOutcomeProtocolledAt()) {
          $attachment->setProtocolTime($application->getOutcomeProtocolledAt()->getTimestamp());
        }
        $numeroDiProtocollo = [
          'id' => $attachment->getId(),
          'protocollo' => $application->getOutcomeProtocolNumber(),
        ];

        if (!$this->inProtocolNumbers($numeroDiProtocollo, $rispostaOperatore->getNumeriProtocollo())) {
          $rispostaOperatore->addNumeroDiProtocollo($numeroDiProtocollo);
        }
      }
    }

    return $entity;
  }

}
