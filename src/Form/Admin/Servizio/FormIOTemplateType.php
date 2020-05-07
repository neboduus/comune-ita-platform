<?php

namespace App\Form\Admin\Servizio;

use App\Entity\FormIO;
use App\Entity\SciaPraticaEdilizia;
use App\Entity\Servizio;
use App\Form\Extension\TestiAccompagnatoriProcedura;
use App\Model\FlowStep;
use App\Services\FormServerApiAdapterService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

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
            ->add(
                'service_id',
                HiddenType::class,
                [
                    'attr' => ['value' => $servizio->getFormIoId()],
                    'mapped' => false,
                    'required' => false,
                ]
            )
            ->add(
                'current_id',
                HiddenType::class,
                [
                    'attr' => ['value' => $servizio->getId()],
                    'mapped' => false,
                    'required' => false,
                ]
            );
        $builder->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'onPreSubmit'));
        //$builder->addEventListener(FormEvents::POST_SUBMIT, array($this, 'onPostSubmit'));
    }

    public function onPreSubmit(FormEvent $event)
    {
        /** @var Servizio $servizio */
        $servizio = $event->getForm()->getData();

        if (isset($event->getData()['service_id']) && !empty($event->getData()['service_id'])) {
            if (empty($servizio->getFormIoId())) {
                $serviceID = $event->getData()['service_id'];

                $response = false;
                if ($serviceID == 'new') {
                    $response = $this->formServerService->createForm($servizio);
                } else {
                    $serviceToClone = $this->em->getRepository('App:Servizio')->find($serviceID);
                    if ($serviceToClone instanceof Servizio) {
                        $this->cloneService($servizio, $serviceToClone);
                        $response = $this->formServerService->cloneForm($servizio, $serviceToClone);
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
    }

    public function getBlockPrefix()
    {
        return 'formio_template';
    }
}
