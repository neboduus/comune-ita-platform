<?php

namespace App\Form\FormIO;

use App\Entity\Allegato;
use App\Entity\CPSUser;
use App\Entity\FormIO;
use App\Entity\Pratica;
use App\Entity\SciaPraticaEdilizia;
use App\Form\Extension\TestiAccompagnatoriProcedura;
use App\Services\FormServerApiAdapterService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Form\FormError;
use \DateTime;

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
     * FormIORenderType constructor.
     * @param EntityManager $entityManager
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
        ]
      );
        $builder->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'onPreSubmit'));
    }


    public function getBlockPrefix()
    {
        return 'formio_render';
    }

    public function onPreSubmit(FormEvent $event)
    {

    /** @var SciaPraticaEdilizia $pratica */
        $pratica = $event->getForm()->getData();
        $compiledData = $flattenedData = array();
        if (isset($event->getData()['dematerialized_forms'])) {
            $data = json_decode($event->getData()['dematerialized_forms'], true);
            $flattenedData = $this->arrayFlat($data);
            $compiledData = $data;
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
            if (isset($this->schema[$key]['type']) && $this->schema[$key]['type'] == 'file') {
                foreach ($value as $file) {
                    $id = $file['data']['id'];
                    $attachment = $this->em->getRepository('App:Allegato')->find($id);
                    if ($attachment instanceof Allegato) {
                        $attachments[]= $id;
                        $pratica->addAllegato($attachment);
                    }
                }
            }
        }

        $this->em->persist($pratica);
    }

    /**
     * @param SciaPraticaEdilizia $pratica
     * @return false|string
     */
    private function setupHelperData(FormIO $pratica)
    {
        $data = $pratica->getDematerializedForms();

        if (empty($data)) {
            /** @var CPSUser $user */
            $user = $pratica->getUser();
            $cpsUserData = [];
            $applicant = [];

            $result = $this->formServerService->getFormSchema($pratica->getServizio()->getFormIoId());
            if ($result['status'] == 'success') {
                $schema = $this->arrayFlat($this->schema, true);
                foreach ($schema as $k => $v) {
                    $kParts = explode('.', $k);
                    if ($kParts[0] == 'applicant') {
                        array_pop($kParts);
                        $key = implode('.', $kParts);
                        if (!in_array($key, $applicant)) {
                            $applicant[] = $key;
                        }
                    }
                }
            }

            if (!empty($applicant) && $user instanceof CPSUser) {
                if (in_array('applicant.data.completename.data.name', $applicant)) {
                    $cpsUserData['data']['applicant']['data']['completename']['data']['name'] = $user->getNome();
                }

                if (in_array('applicant.data.completename.data.surname', $applicant)) {
                    $cpsUserData['data']['applicant']['data']['completename']['data']['surname'] = $user->getCognome();
                }

                if (in_array('applicant.data.Born.data.natoAIl', $applicant)) {
                    $cpsUserData['data']['applicant']['data']['Born']['data']['natoAIl'] = $user->getDataNascita()->format(DateTime::ISO8601);
                }

                if (in_array('applicant.data.Born.data.place_of_birth', $applicant)) {
                    $cpsUserData['data']['applicant']['data']['Born']['data']['place_of_birth'] = $user->getLuogoNascita();
                }

                if (in_array('applicant.data.fiscal_code.data.fiscal_code', $applicant)) {
                    $cpsUserData['data']['applicant']['data']['fiscal_code']['data']['fiscal_code'] = $user->getCodiceFiscale();
                }

                if (in_array('applicant.data.address.data.address', $applicant)) {
                    $cpsUserData['data']['applicant']['data']['address']['data']['address'] = $user->getIndirizzoResidenza();
                }

                if (in_array('applicant.data.address.data.house_number', $applicant)) {
                    $cpsUserData['data']['applicant']['data']['address']['data']['house_number'] = '';
                }

                if (in_array('applicant.data.address.data.municipality', $applicant)) {
                    $cpsUserData['data']['applicant']['data']['address']['data']['municipality'] = $user->getCittaResidenza();
                }

                if (in_array('applicant.data.address.data.postal_code', $applicant)) {
                    $cpsUserData['data']['applicant']['data']['address']['data']['postal_code'] = $user->getCapResidenza();
                }

                if (in_array('applicant.data.email_address', $applicant)) {
                    $cpsUserData['data']['applicant']['data']['email_address'] = $user->getEmail();
                }
            }
            return \json_encode($cpsUserData);
        }
        return \json_encode($data);
    }

    /**
     * @param $array
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
}
