<?php

namespace App\Services;

use App\Entity\OperatoreUser;
use App\Utils\StringUtils;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ExternalProtocolService
{
  const OPERATOR_USERNAME = 'protocollo';
  const OPERATOR_TMP_PASSWORD = 'changeme';

  /**
   * @var InstanceService
   */
  private InstanceService $instanceService;
  /**
   * @var RouterInterface
   */
  private RouterInterface $router;
  /**
   * @var EntityManagerInterface
   */
  private EntityManagerInterface $entityManager;
  /**
   * @var UserPasswordEncoderInterface
   */
  private UserPasswordEncoderInterface $passwordEncoder;

  private LoggerInterface $logger;

  private TranslatorInterface $translator;


  /**
   * @param InstanceService $instanceService
   * @param RouterInterface $router
   * @param EntityManagerInterface $entityManager
   * @param UserPasswordEncoderInterface $passwordEncoder
   * @param LoggerInterface $logger
   * @param TranslatorInterface $translator
   */
  public function __construct(
    InstanceService $instanceService,
    RouterInterface $router,
    EntityManagerInterface $entityManager,
    UserPasswordEncoderInterface $passwordEncoder,
    LoggerInterface $logger,
    TranslatorInterface $translator
  )
  {
    $this->instanceService = $instanceService;
    $this->router = $router;
    $this->entityManager = $entityManager;
    $this->passwordEncoder = $passwordEncoder;
    $this->logger = $logger;
    $this->translator = $translator;
  }

  /**
   * @throws Exception
   */
  public function setup(array $configuration) {
    $url = $configuration['url'] ?? '';

    if (!$url){
      $this->logger->debug('servizio.no_external_protocol_configuration_needed');
      return null;
    }

    $currentInstance = $this->instanceService->getCurrentInstance();
    $client = new Client();

    $url = $configuration['url'];

    $headers = [
      'Content-Type' => 'application/json',
    ];

    foreach($configuration['headers'] ?? [] as $header) {
      $exploded = explode('=', $header);
      $headers[$exploded[0]] = $exploded[1];
    }

    $tenant = null;
    $request = new Request(
      'GET',
      $url . '/tenants/?sdc_id=' . $currentInstance->getId(),
      $headers
    );

    $isNewTenant = false;

    try {
      $response = $client->send($request);
      $data = json_decode($response->getBody(), true);
      if ($data['count'] == 1) {
        $tenant = $data['results'][0];
      }
    } catch (GuzzleException $e) {
      $this->logger->error("Error retrieving tenant {$currentInstance->getId()} from external protocol: {$e->getMessage()}");
      throw new Exception($this->translator->trans('servizio.error_retrieving_protocol_tenant'));
    }

    $operatoreRepo = $this->entityManager->getRepository(OperatoreUser::class);
    $username = $tenant ? $tenant['sdc_username'] :  self::OPERATOR_USERNAME;
    $operatoreUser = $operatoreRepo->findOneBy(['username' => $username]);

    $password = null;
    if (!$operatoreUser) {
      $operatoreUser = new OperatoreUser();
      $password = StringUtils::randomPassword();
      $operatoreUser
        ->setEnte($this->instanceService->getCurrentInstance())
        ->setUsername(self::OPERATOR_USERNAME)
        ->setNome(self::OPERATOR_USERNAME)
        ->setCognome(self::OPERATOR_USERNAME)
        ->setSystemUser(true)
        ->setEnabled(true)
        ->setPlainPassword($password)
        ->setPassword(
          $this->passwordEncoder->encodePassword(
            $operatoreUser,
            $password
          )
        )
        ->setLastChangePassword(new \DateTime());
    } elseif (!$operatoreUser->isSystemUser()) {
      $operatoreUser->setSystemUser(true);
    }

    $this->entityManager->persist($operatoreUser);

    if (!$tenant) {
      // Fixme: non ho la password se l'utente non viene creato
      // Se l'operatore protocollo esiste già, ma non è presente una configurazione per il tenant esterna non ho modo
      // di recuperare la password dell'operatore necessaria per la configurazione, imposto quindi una password temporanea
      // e mostro un messaggio che informi l'amministratore della necessità di una modifica manuale

      $request = new Request(
        'POST',
        $url .'/tenants/',
        $headers,
        \json_encode([
          'description' => $currentInstance->getName(),
          'slug' => $currentInstance->getSlug(),
          'sdc_id' => $currentInstance->getId(),
          'sdc_base_url' => rtrim($this->router->generate('api_base', [], UrlGeneratorInterface::ABSOLUTE_URL), '/'),
          'sdc_username' => $operatoreUser->getUsername(),
          'sdc_password' => $password ?? self::OPERATOR_TMP_PASSWORD,
          'sdc_institution_code' => $currentInstance->getCodiceMeccanografico(),
          'sdc_aoo_code' => $currentInstance->getCodiceAmministrativo(),
          'latest_registration_number' => 0,
          'latest_registration_number_issued_at' => (new DateTime())->format('c'),
          'register_after_date' => (new DateTime())->format('c')
        ])
      );

      try {
        $response = $client->send($request);
        $tenant = json_decode($response->getBody(), true);
        $isNewTenant = true;
      } catch (GuzzleException $e) {
        $this->logger->error("Error creating tenant {$currentInstance->getId()} from external protocol: {$e->getMessage()}");
         throw new Exception($this->translator->trans('servizio.error_creating_protocol_tenant'));
      }
    }

    $this->entityManager->flush();

    if (!$password && $isNewTenant) {
      // Operatore già presente, nuovo tenant
      throw new Exception($this->translator->trans('servizio.configuration_needs_technical_support'));
    }

    return $tenant;
  }
}
