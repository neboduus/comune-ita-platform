<?php

namespace AppBundle\Form\FormIO;

use AppBundle\Entity\Allegato;
use AppBundle\Entity\CPSUser;
use AppBundle\Entity\DematerializedFormPratica;
use AppBundle\Entity\FormIO;
use AppBundle\Entity\Pratica;
use AppBundle\Entity\Servizio;
use AppBundle\Form\Extension\TestiAccompagnatoriProcedura;
use AppBundle\FormIO\Schema;
use AppBundle\FormIO\SchemaFactoryInterface;
use AppBundle\Services\FormServerApiAdapterService;
use AppBundle\Validator\Constraints\ServerSideFormIOConstraint;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\NotEqualTo;


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

  private $logger;

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
    'applicant.email_address' => 'getEmail',
    'applicant.email_repeat' => 'getEmail',
  ];

  /**
   * FormIORenderType constructor.
   * @param EntityManager $entityManager
   * @param FormServerApiAdapterService $formServerService
   */
  public function __construct(
    EntityManager $entityManager,
    FormServerApiAdapterService $formServerService,
    SchemaFactoryInterface $schemaFactory,
    LoggerInterface $logger
  )
  {
    $this->em = $entityManager;
    $this->formServerService = $formServerService;
    $this->schemaFactory = $schemaFactory;
    $this->logger = $logger;
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
      'groups' => ['flow_formIO_step1', 'Default']
    ]);
    $notEmptyConstraint->message = "Il form non sembra essere compilato correttamente";

    $serverSideCheckConstraint = new ServerSideFormIOConstraint([
      'formIOId' => $formID,
      'validateFields' => array_keys(self::$applicantUserMap),
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
            //$serverSideCheckConstraint, //@todo
          ],
        ]
      )
      ->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'onPreSubmit'));
  }

  public function getBlockPrefix()
  {
    return 'formio_render';
  }

  public function onPreSubmit(FormEvent $event)
  {
    /** @var Pratica|DematerializedFormPratica $pratica */
    $pratica = $event->getForm()->getData();
    $compiledData = $flattenedData = array();

    if (isset($event->getData()['dematerialized_forms'])) {
      $data = json_decode($event->getData()['dematerialized_forms'], true);
      $flattenedData = $this->arrayFlat($data);
      $compiledData = $data;
    }

    if ($pratica->getServizio()->isPaymentRequired() && !$this->isPaymentValid($data)) {
      $event->getForm()->addError(
        new FormError('Si è veridicato un problema con i dati del pagamneto, impossibile inviare la pratica.')
      );
    }

    $pratica->setDematerializedForms(
      array(
        'data' => $compiledData,
        'flattened' => $flattenedData,
        'schema' => $this->arrayFlat($this->schema, true)
      )
    );

    // Associo gli allegati alla pratica
    foreach ($flattenedData as $key => $value) {
      if ( isset($this->schema[$key]['type']) && $this->schema[$key]['type'] == 'file') {
        foreach ($value as $file) {
          $id = $file['data']['id'];
          $attachment = $this->em->getRepository('AppBundle:Allegato')->find($id);
          if ($attachment instanceof Allegato) {
            $attachments[]= $id;
            $pratica->addAllegato($attachment);
          }
        }
      }
    }

    if ($pratica->getUser() instanceof CPSUser) {
      $this->em->persist($pratica);
    }
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

      return \json_encode($cpsUserData);
    }
    return \json_encode($data);
  }

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
            if ($value instanceof \DateTime) {
              if ($component['form_type'] == DateTimeType::class) {
                $value = $value->format(\DateTime::ISO8601);
              } else {
                $value = $value->format('d/m/Y');
              }
            }
            if ($component['form_type'] == ChoiceType::class
              && isset($component['form_options']['choices'])
              && !empty($component['form_options']['choices'])) {
              $value = strtoupper($value);
              if (!in_array($value, $component['form_options']['choices'])){
                $value = null;
              }
            }
            if ($value) {
              $data->set($schemaFlatName, $value);
            }
          }
        } catch (\InvalidArgumentException $e) {
          $this->logger->error($e->getMessage());
        }
      }
    }

    return $data->toArray();
  }

  /**
   * @param array
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
      if ( !$isSchema && isset($this->schema[$key]['type']) &&
        ( $this->schema[$key]['type'] == 'file' || $this->schema[$key]['type'] == 'financial_report') )  {
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
