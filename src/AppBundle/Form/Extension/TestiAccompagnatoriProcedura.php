<?php

namespace AppBundle\Form\Extension;

use Symfony\Component\Translation\TranslatorInterface;

class TestiAccompagnatoriProcedura
{
    private $guideText;

    private $descriptionText;

    private $stepTitle;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @return mixed
     */
    public function getGuideText()
    {
        return !empty($this->guideText) ? $this->guideText : null;
    }

    /**
     * @param mixed $guideText
     * @param boolean $translate
     *
     * @return TestiAccompagnatoriProcedura
     */
    public function setGuideText($guideText, $translate = false)
    {
        $this->guideText = $translate ? $this->translator->trans($guideText) : $guideText;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getDescriptionText()
    {
        return !empty($this->descriptionText) ? $this->descriptionText : null;
    }

    /**
     * @param mixed $descriptionText
     * @param boolean $translate
     *
     * @return TestiAccompagnatoriProcedura
     */
    public function setDescriptionText($descriptionText, $translate = false)
    {
        $this->descriptionText = $translate ? $this->translator->trans($descriptionText) : $descriptionText;

        return $this;
    }

    public function translate($palceholder){
        return $this->translator->trans($palceholder);
    }

    /**
     * @return mixed
     */
    public function getStepTitle()
    {
        return $this->stepTitle;
    }

    /**
     * @param mixed $stepTitle
     *
     * @return TestiAccompagnatoriProcedura
     */
    public function setStepTitle($stepTitle, $translate = false)
    {
        $this->stepTitle = $translate ? $this->translator->trans($stepTitle) : $stepTitle;

        return $this;
    }

}
