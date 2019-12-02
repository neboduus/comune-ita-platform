<?php

namespace AppBundle\Form\FormIO;

use AppBundle\Entity\FormIO;
use AppBundle\Entity\SciaPraticaEdilizia;
use AppBundle\Form\Extension\TestiAccompagnatoriProcedura;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Form\FormError;


class FormIORenderType extends AbstractType
{
  /**
   * @var EntityManager
   */
  private $em;

  /**
   * ChooseAllegatoType constructor.
   *
   * @param EntityManager $entityManager
   */
  public function __construct(EntityManager $entityManager)
  {
    $this->em = $entityManager;
  }

  /**
   * @param FormBuilderInterface $builder
   * @param array $options
   */
  public function buildForm(FormBuilderInterface $builder, array $options)
  {

    /** @var FormIO $pratica */
    $pratica = $builder->getData();

    $formID = false;
    $flowsteps = $pratica->getServizio()->getFlowSteps();
    $additionalData = $pratica->getServizio()->getAdditionalData();
    if (!empty($flowsteps)) {
      foreach ($flowsteps as $f) {
        if ($f['type'] == 'formio' && $f['parameters']['formio_id'] && !empty($f['parameters']['formio_id'])) {
          $formID = $f['parameters']['formio_id'];
          break;
        }
      }
    }
    // Retrocompatibilità
    if (!$formID) {
      $formID = $additionalData['formio_id'];
    }


    /** @var TestiAccompagnatoriProcedura $helper */
    $helper = $options["helper"];
    $helper->setStepTitle('steps.scia.modulo_default.label', true);
    $data = $this->setupHelperData($pratica, $helper);
    $builder
      ->add('form_id', HiddenType::class,
        [
          'attr' => ['value' => $formID],
          'mapped' => false,
          'required' => false,
        ]
      )
      ->add('dematerialized_forms', HiddenType::class,
        [
          'attr' => ['value' => $data],
          'mapped' => false,
          'required' => false,
        ]
      );
    $builder->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'onPreSubmit'));
    //$builder->addEventListener(FormEvents::POST_SUBMIT, array($this, 'onPostSubmit'));
  }


  public function getBlockPrefix()
  {
    return 'formio_render';
  }

  public function onPreSubmit(FormEvent $event)
  {

    $options = $event->getForm()->getConfig()->getOptions();
    /** @var TestiAccompagnatoriProcedura $helper */
    $helper = $options["helper"];

    /** @var SciaPraticaEdilizia $pratica */
    $pratica = $event->getForm()->getData();
    $compiledData = array();
    if (isset($event->getData()['dematerialized_forms'])) {

      $flattenedData = array();
      $flattenedData = $this->arrayFlat(json_decode($event->getData()['dematerialized_forms'], true));
      $compiledData = json_decode($event->getData()['dematerialized_forms'], true);
    }

    $pratica->setDematerializedForms(
      array(
        'data' => $compiledData,
        'flattened' => $flattenedData
      )
    );
    $this->em->persist($pratica);
  }

  /**
   * @param SciaPraticaEdilizia $pratica
   * @param TestiAccompagnatoriProcedura $helper
   * @return array
   */
  private function setupHelperData(FormIO $pratica, TestiAccompagnatoriProcedura $helper)
  {
    return json_encode($pratica->getDematerializedForms());

//        $skeleton = new MappedPraticaEdilizia($pratica->getDematerializedForms());
//
//        $allegati = array();
//        $allegatiCorrenti = $skeleton->getVincoli()->toHash();
//        $allegatiRichiesti = $this->getRequiredFields($skeleton);
//
//        foreach ($allegatiCorrenti as $key => $value) {
//            if (is_array($integrazioneAllegati) && !in_array($key, $integrazioneAllegati)){
//                unset($allegatiCorrenti[$key]);
//                continue;
//            }
//            $allegati[$key]['title'] = $helper->translate('steps.scia.vincoli.files.' . $key . '.title');
//            $allegati[$key]['description'] = $helper->translate('steps.scia.vincoli.files.' . $key . '.description');
//            $allegati[$key]['type'] = Vincoli::TYPE;
//            $allegati[$key]['identifier'] = $key;
//            $allegati[$key]['checked'] = false;
//            $allegati[$key]['files'] = [];
//            if (!empty($value)) {
//                $allegati[$key]['checked'] = true;
//                $allegati[$key]['files'] = $value;
//            }
//        }
//
//        $idPratica = $pratica->getId();
//
//        $helper->setVueApp(Vincoli::TYPE);
//        $helper->setVueBundledData(json_encode([
//            'allegatiCorrenti' => $allegatiCorrenti,
//            'allegati' => $allegati,
//            'allegatiRichiesti' => array_fill_keys($allegatiRichiesti, true),
//            'idPratica' => $idPratica,
//            'prefix' => $helper->getPrefix()
//        ]));
//
//        return $allegatiCorrenti;
  }

  /*private function getRequiredFields(MappedPraticaEdilizia $skeleton)
  {
      return is_array($integrazioneAllegati) ?
          $integrazioneAllegati :
          $skeleton->getVincoli()->getRequiredFields($skeleton->getTipoIntervento());
  }*/

  private function arrayFlat($array, $prefix = '')
  {
    $result = array();

    foreach ($array as $key => $value) {
      $new_key = $prefix . (empty($prefix) ? '' : '.') . $key;

      if (is_array($value)) {
        $result = array_merge($result, $this->arrayFlat($value, $new_key));
      } else {
        $result[$new_key] = $value;
      }
    }

    return $result;
  }
}
