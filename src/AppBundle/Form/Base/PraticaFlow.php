<?php

namespace AppBundle\Form\Base;

use AppBundle\Entity\Pratica;
use AppBundle\Form\Extension\TestiAccompagnatoriProcedura;
use AppBundle\Logging\LogConstants;
use Craue\FormFlowBundle\Form\FormFlow;
use Psr\Log\LoggerInterface;
use Symfony\Component\Translation\TranslatorInterface;

abstract class PraticaFlow extends FormFlow
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger, TranslatorInterface $translator)
    {
        $this->logger = $logger;
        $this->translator = $translator;
    }

    public function getFormOptions($step, array $options = array())
    {
        $options = parent::getFormOptions($step, $options);

        /** @var Pratica $pratica */
        $pratica = $this->getFormData();
        $options["helper"] = new TestiAccompagnatoriProcedura($this->translator);

        $this->logger->info(
            LogConstants::PRATICA_COMPILING_STEP,
            [
                'step' => $step,
                'pratica' => $pratica->getId(),
                'user' => $pratica->getUser()->getId(),
            ]
        );

        return $options;
    }
}
