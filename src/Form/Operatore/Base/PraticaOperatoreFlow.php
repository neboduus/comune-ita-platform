<?php

namespace App\Form\Operatore\Base;

use App\Entity\Pratica;
use App\Logging\LogConstants;
use Craue\FormFlowBundle\Form\FormFlow;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;


abstract class PraticaOperatoreFlow extends FormFlow
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    protected $handleFileUploads = false;

    /**
     * PraticaOperatoreFlow constructor.
     *
     * @param LoggerInterface $logger
     * @param TranslatorInterface $translator
     */
    public function __construct(
        LoggerInterface $logger,
        TranslatorInterface $translator
    ) {
        $this->logger = $logger;
        $this->translator = $translator;
    }

    public function getFormOptions($step, array $options = array())
    {
        $options = parent::getFormOptions($step, $options);

        /** @var Pratica $pratica */
        $pratica = $this->getFormData();

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

    public function hasUploadAllegati()
    {
        return true;
    }
}
