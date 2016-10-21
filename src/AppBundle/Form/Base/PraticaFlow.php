<?php

namespace AppBundle\Form\Base;

use AppBundle\Entity\ComponenteNucleoFamiliare;
use AppBundle\Entity\CPSUser;
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

    /**
     * @param CPSUser $user
     * @param Pratica $pratica
     */
    public function populatePraticaFieldsWithUserValues(CPSUser $user, $pratica)
    {
        $pratica->setRichiedenteNome($user->getNome());
        $pratica->setRichiedenteCognome($user->getCognome());
        $pratica->setRichiedenteLuogoNascita($user->getLuogoNascita());
        $pratica->setRichiedenteDataNascita($user->getDataNascita());
        $pratica->setRichiedenteIndirizzoResidenza($user->getIndirizzoResidenza());
        $pratica->setRichiedenteCapResidenza($user->getCapResidenza());
        $pratica->setRichiedenteCittaResidenza($user->getCittaResidenza());
        $pratica->setRichiedenteTelefono($user->getTelefono());
        $pratica->setRichiedenteEmail($user->getEmailCanonical());
    }

    /**
     * @param Pratica $lastPratica
     * @param Pratica $pratica
     */
    public function populatePraticaFieldsWithLastPraticaValues($lastPratica, $pratica)
    {
        foreach ($lastPratica->getNucleoFamiliare() as $oldComponente) {
            $this->addNewComponenteToPraticaFromOldComponente($oldComponente, $pratica);
        }
    }

    /**
     * @param ComponenteNucleoFamiliare $componente
     * @param Pratica $pratica
     */
    private function addNewComponenteToPraticaFromOldComponente(ComponenteNucleoFamiliare $componente, Pratica $pratica)
    {
        $cloneComponente = new ComponenteNucleoFamiliare();
        $cloneComponente->setNome($componente->getNome());
        $cloneComponente->setCognome($componente->getCognome());
        $cloneComponente->setCodiceFiscale($componente->getCodiceFiscale());
        $cloneComponente->setRapportoParentela($componente->getRapportoParentela());
        $pratica->addNucleoFamiliare($cloneComponente);
    }
}
