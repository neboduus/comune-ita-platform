<?php

namespace AppBundle\Form\FormIO;

use AppBundle\Entity\Allegato;
use AppBundle\Entity\CPSUser;
use AppBundle\Entity\DematerializedFormPratica;
use AppBundle\Entity\FormIO;
use AppBundle\Entity\Pratica;
use AppBundle\Form\Extension\TestiAccompagnatoriProcedura;
use AppBundle\FormIO\Schema;
use AppBundle\FormIO\SchemaFactoryInterface;
use AppBundle\Services\FormServerApiAdapterService;
use AppBundle\Services\UserSessionService;
use AppBundle\Validator\Constraints\ExpressionBasedFormIOConstraint;
use DateTime;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Validator\Constraints\NotEqualTo;
use function json_encode;


class FormIORenderType extends AbstractType
{
  /**
   * @var EntityManager
   */
  private $em;

  /**
   * @var FormServerApiAdapterService
   */
  private $formServerService;

  /**
   * @var
   */
  private $schema = false;

  /**
   * @var SchemaFactoryInterface
   */
  private $schemaFactory;

  /**
   * @var LoggerInterface
   */
  private $logger;

  /**
   * @var SessionInterface
   */
  private $session;

  /**
   * @var UserSessionService
   */
  private $userSessionService;

  private $genericViolationMessage = "Il form non sembra essere compilato correttamente";

  private $attachmentsViolationMessage = "Si è verificato un problema durante il salvataggio degli allegati, si prega di ricompilare la domanda e ripetere l'invio.";

  private $paymentViolationMessage = "Si è verificato un problema con i dati del pagamento, impossibile inviare la pratica";

  private $constraintGroups = ['flow_formIO_step1', 'flow_FormIOAnonymous_step1', 'Default'];

  private static $applicantUserMap = [
    'applicant.completename.name' => 'getNome',
    'applicant.completename.surname' => 'getCognome',
    'applicant.Born.natoAIl' => 'getDataNascita',
    'applicant.Born.place_of_birth' => 'getLuogoNascita',
    'applicant.fiscal_code.fiscal_code' => 'getCodiceFiscale',
    'applicant.address.address' => 'getIndirizzoResidenza',
    'applicant.address.house_number' => '',
    'applicant.address.municipality' => 'getCittaResidenza',
    'applicant.address.postal_code' => 'getCapResidenza',
    'applicant.address.county' => 'getProvinciaResidenza',
    'applicant.email_address' => 'getEmail',
    'applicant.email_repeat' => 'getEmail',
    'applicant.cell_number' => 'getCellulare',
    'applicant.phone_number' => 'getTelefono',
    'applicant.gender.gender' => 'getSessoAsString',
    'cell_number' => 'getCellulare'
  ];

  /**
   * FormIORenderType constructor.
   * @param EntityManagerInterface $entityManager
   * @param FormServerApiAdapterService $formServerService
   * @param SchemaFactoryInterface $schemaFactory
   * @param LoggerInterface $logger
   * @param SessionInterface $session
   * @param UserSessionService $userSessionService
   */
  public function __construct(
    EntityManagerInterface $entityManager,
    FormServerApiAdapterService $formServerService,
    SchemaFactoryInterface $schemaFactory,
    LoggerInterface $logger,
    SessionInterface $session,
    UserSessionService $userSessionService
  )
  {
    $this->em = $entityManager;
    $this->formServerService = $formServerService;
    $this->schemaFactory = $schemaFactory;
    $this->logger = $logger;
    $this->session = $session;
    $this->userSessionService = $userSessionService;
  }

  /**
   * @param FormBuilderInterface $builder
   * @param array $options
   */
  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    /** @var FormIO $pratica */
    $pratica = $builder->getData();
    $formID = $pratica->getServizio()->getFormIoId();

    $result = $this->formServerService->getFormSchema($pratica->getServizio()->getFormIoId());
    if ($result['status'] == 'success') {
      $this->schema = $result['schema'];
    }

    /** @var TestiAccompagnatoriProcedura $helper */
    $helper = $options["helper"];
    $helper->setStepTitle('steps.scia.modulo_default.label', true);

    $data = $this->setupHelperData($pratica);

    $notEmptyConstraint = new NotEqualTo([
      'value' => '[]',
      'groups' => $this->constraintGroups,
    ]);
    $notEmptyConstraint->message = $this->genericViolationMessage;

    $notNullConstraint = new NotEqualTo([
      'value' => '',
      'groups' => $this->constraintGroups,
    ]);
    $notNullConstraint->message = $this->genericViolationMessage;

    $expressionBasedConstraint = new ExpressionBasedFormIOConstraint([
      'service' => $pratica->getServizio(),
      'groups' => $this->constraintGroups,
    ]);

    $builder
      ->add(
        'form_id',
        HiddenType::class,
        [
          'attr' => ['value' => $formID],
          'mapped' => false,
          'required' => false,
        ]
      )
      ->add(
        'dematerialized_forms',
        HiddenType::class,
        [
          'attr' => ['value' => $data],
          'mapped' => false,
          'required' => false,
          'constraints' => [
            $notEmptyConstraint,
            $notNullConstraint,
            $expressionBasedConstraint,
          ],
        ]
      )
      ->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'onPreSubmit'))
      ->addEventListener(FormEvents::POST_SUBMIT, array($this, 'onPostSubmit'));
  }

  public function getBlockPrefix()
  {
    return 'formio_render';
  }

  /**
   * @param FormEvent $event
   * @throws ORMException
   */
  public function onPreSubmit(FormEvent $event)
  {
    /** @var Pratica|DematerializedFormPratica $pratica */
    $pratica = $event->getForm()->getData();

    $compiledData = [];
    $flattenedData = [];
    $flattenedSchema = $this->arrayFlat($this->schema, true);

    if (isset($event->getData()['dematerialized_forms'])) {
      $compiledData = (array)json_decode($event->getData()['dematerialized_forms'], true);
      $flattenedData = $this->arrayFlat($compiledData);
    }

    if ($pratica->getServizio()->isPaymentRequired() && !$this->isPaymentValid($compiledData)) {
      $event->getForm()->addError(
        new FormError($this->paymentViolationMessage)
      );
    }

    if (empty($compiledData)){
      $this->logger->error("Form data is empty", ['pratica' => $pratica->getId()]);
      $event->getForm()->addError(new FormError($this->genericViolationMessage));
    }

    // Check sulla presenza del codice fiscale (per pratiche vuote)
    if (!isset($flattenedData['applicant.data.fiscal_code.data.fiscal_code'])) {
      $this->logger->error("Dematerialized form not well formed", [
        'pratica' => $pratica->getId()
      ]);
      $event->getForm()->addError(new FormError($this->genericViolationMessage));
    }

    // Check su cnformità codice fiscale
    /*if ($pratica->getUser() instanceof CPSUser && strcasecmp($flattenedData['applicant.data.fiscal_code.data.fiscal_code'], $pratica->getUser()->getCodiceFiscale()) != 0) {
      $this->logger->error("Fiscal code Mismatch", [
        'pratica' => $pratica->getId(),
        'cps' => $pratica->getUser()->getCodiceFiscale(),
        'form' => $flattenedData['applicant.data.fiscal_code.data.fiscal_code']]
      );
      $event->getForm()->addError(new FormError('Il codice fiscale inserito non è conforme con quello resitutito dal sistema di autenticazione.'));
    }*/

    $pratica->setDematerializedForms([
      'data' => $compiledData,
      'flattened' => $flattenedData,
      'schema' => $flattenedSchema
    ]);

    foreach ($flattenedData as $key => $value) {
      // Associa gli allegati alla pratica
      if (isset($this->schema[$key]['type']) && $this->schema[$key]['type'] == 'file') {
        foreach ($value as $file) {
          $id = $file['data']['id'];
          $attachment = $this->em->getRepository('AppBundle:Allegato')->find($id);
          if ($attachment instanceof Allegato) {
            $attachments[] = $id;
            $pratica->addAllegato($attachment);
          } else {
            $this->logger->error("The file present in form schema doesn't exist in database", ['pratica' => $pratica->getId(), 'allegato' => $id]);
            $event->getForm()->addError(new FormError($this->attachmentsViolationMessage));
          }
        }
      }
    }

    if ($pratica->getUser() instanceof CPSUser) {
      $this->em->persist($pratica);
    }
  }

  /**
   * @param FormEvent $event
   * @throws ORMException
   * @throws OptimisticLockException
   */
  public function onPostSubmit(FormEvent $event)
  {
    /** @var Pratica|DematerializedFormPratica $pratica */
    $pratica = $event->getForm()->getData();

    $user = $pratica->getUser();

    if (!$user instanceof CPSUser) {
      $user = $this->checkUser($pratica->getDematerializedForms());
      $pratica->setUser($user)
        ->setAuthenticationData($this->userSessionService->getCurrentUserAuthenticationData($user))
        ->setSessionData($this->userSessionService->getCurrentUserSessionData($user));
      $this->em->persist($pratica);

      $attachments = $pratica->getAllegati();
      if (!empty($attachments)) {
        /** @var Allegato $a */
        foreach ($attachments as $a) {
          $a->setOwner($user);
          $a->setHash($pratica->getHash());
          $this->em->persist($pratica);
        }
      }
      $this->em->flush();
    }
  }

  /**
   * @param array $data
   * @return CPSUser
   * @throws ORMException
   */
  private function checkUser(array $data): CPSUser
  {
    $cf = isset($data['flattened']['applicant.data.fiscal_code.data.fiscal_code']) ? $data['flattened']['applicant.data.fiscal_code.data.fiscal_code'] : false;

    $birthDay = null;
    if (isset($data['flattened']['applicant.data.Born.data.natoAIl']) && !empty($data['flattened']['applicant.data.Born.data.natoAIl'])) {
      $birthDay = DateTime::createFromFormat('d/m/Y', $data['flattened']['applicant.data.Born.data.natoAIl']);
    }
    $sessionString = md5($this->session->getId()) . '-' . time();
    $user = new CPSUser();
    $user
      ->setUsername($sessionString)
      ->setCodiceFiscale($cf . '-' . $sessionString)
      ->setSessoAsString(isset($data['flattened']['applicant.gender.gender']) ? $data['flattened']['applicant.gender.gender'] : '')
      ->setCellulareContatto(isset($data['flattened']['applicant.data.cell_number']) ? $data['flattened']['applicant.data.cell_number'] : '')
      ->setCpsTelefono(isset($data['flattened']['applicant.data.phone_number']) ? $data['flattened']['applicant.data.phone_number'] : '')
      ->setEmail(isset($data['flattened']['applicant.data.email_address']) ? $data['flattened']['applicant.data.email_address'] : $user->getId() . '@' . CPSUser::FAKE_EMAIL_DOMAIN)
      ->setEmailContatto(isset($data['flattened']['applicant.data.email_address']) ? $data['flattened']['applicant.data.email_address'] : $user->getId() . '@' . CPSUser::FAKE_EMAIL_DOMAIN)
      ->setNome(isset($data['flattened']['applicant.data.completename.data.name']) ? $data['flattened']['applicant.data.completename.data.name'] : '')
      ->setCognome(isset($data['flattened']['applicant.data.completename.data.surname']) ? $data['flattened']['applicant.data.completename.data.surname'] : '')
      ->setDataNascita($birthDay)
      ->setLuogoNascita(isset($data['flattened']['applicant.data.Born.data.place_of_birth']) && !empty($data['flattened']['applicant.data.Born.data.place_of_birth']) ? $data['flattened']['applicant.data.Born.data.place_of_birth'] : '')
      ->setSdcIndirizzoResidenza(isset($data['flattened']['applicant.data.address.data.address']) && !empty($data['flattened']['applicant.data.address.data.address']) ? $data['flattened']['applicant.data.address.data.address'] : '')
      ->setSdcCittaResidenza(isset($data['flattened']['applicant.data.address.data.municipality']) && !empty($data['flattened']['applicant.data.address.data.municipality']) ? $data['flattened']['applicant.data.address.data.municipality'] : '')
      ->setSdcCapResidenza(isset($data['flattened']['applicant.data.address.data.postal_code']) && !empty($data['flattened']['applicant.data.address.data.postal_code']) ? $data['flattened']['applicant.data.address.data.postal_code'] : '')
      ->setSdcProvinciaResidenza(isset($data['flattened']['applicant.data.address.data.county']) && !empty($data['flattened']['applicant.data.address.data.county']) ? $data['flattened']['applicant.data.address.data.county'] : '');

    $user->addRole('ROLE_USER')
      ->addRole('ROLE_CPS_USER')
      ->setEnabled(true)
      ->setPassword('');

    $this->em->persist($user);
    return $user;
  }

  /**
   * @param FormIO $pratica
   * @return false|string
   */
  private function setupHelperData(FormIO $pratica)
  {
    $data = $pratica->getDematerializedForms();

    /** @var CPSUser $user */
    $user = $pratica->getUser();

    // Precompilo i campi dell'applicant solo se user è un CPSUser
    if (empty($data) && $user instanceof CPSUser) {
      $schema = $this->schemaFactory->createFromFormId($pratica->getServizio()->getFormIoId());
      $cpsUserData = ['data' => $this->getMappedFormDataWithUserData($schema, $user)];

      return json_encode($cpsUserData);
    }
    return json_encode($data);
  }

  /**
   * @param Schema $schema
   * @param CPSUser $user
   * @return mixed
   */
  private function getMappedFormDataWithUserData(Schema $schema, CPSUser $user)
  {
    $data = $schema->getDataBuilder();
    if ($schema->hasComponents()) {
      foreach (self::$applicantUserMap as $schemaFlatName => $userMethod) {
        try {
          if ($schema->hasComponent($schemaFlatName) && method_exists($user, $userMethod)) {
            $component = $schema->getComponent($schemaFlatName);
            $value = $user->{$userMethod}();
            // se il campo è datatime popola con iso8601 altrimenti testo
            if ($value instanceof DateTime) {
              if ($component['form_type'] == DateTimeType::class) {
                $value = $value->format(DateTime::ISO8601);
              } else {
                $value = $value->format('d/m/Y');
              }
            }
            if ($component['form_type'] == ChoiceType::class
              && isset($component['form_options']['choices'])
              && !empty($component['form_options']['choices'])) {
              // FIXME: fai le cose piu ordinate!!
              if ($schemaFlatName !== 'applicant.gender.gender') {
                $value = strtoupper($value);
              }
              if (!in_array($value, $component['form_options']['choices'])) {
                $value = null;
              }
            }
            if ($value) {
              $data->set($schemaFlatName, $value);
            }
          }
        } catch (InvalidArgumentException $e) {
          $this->logger->error($e->getMessage());
        }
      }
    }

    return $data->toArray();
  }

  /**
   * @param $array
   * @param bool $isSchema
   * @param string $prefix
   * @return array
   */
  private function arrayFlat($array, $isSchema = false, $prefix = '')
  {
    $result = array();
    foreach ($array as $key => $value) {

      if ($key === 'metadata' || $key === 'state') {
        continue;
      }

      $isFile = false;
      if (!$isSchema && isset($this->schema[$key]['type']) &&
        ($this->schema[$key]['type'] == 'file' || $this->schema[$key]['type'] == 'financial_report')) {
        $isFile = true;
      }
      $new_key = $prefix . (empty($prefix) ? '' : '.') . $key;

      if (is_array($value) && !$isFile) {
        $result = array_merge($result, $this->arrayFlat($value, $isSchema, $new_key));
      } else {
        $result[$new_key] = $value;
      }
    }
    return $result;
  }

  /**
   * @param $data
   * @return bool
   */
  private function isPaymentValid($data)
  {
    if (!isset($data['payment_amount'])) {
      return false;
    }

    if (isset($data['payment_financial_report'])) {
      $financialReport = 0;
      foreach ($data['payment_financial_report'] as $f) {
        $financialReport += $f['importo'];
      }

      if ($data['payment_amount'] != $financialReport) {
        return false;
      }
    }
    return true;
  }
}
