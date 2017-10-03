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

    private $vueApp;
    private $vueBundledData;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @return mixed
     */
    public function getGuideText()
    {
        return !empty( $this->guideText ) ? $this->guideText : null;
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
        return !empty( $this->descriptionText ) ? $this->descriptionText : null;
    }

    /**
     * @param mixed $descriptionText
     * @param boolean $translate
     *
     * @return TestiAccompagnatoriProcedura
     */
    public function setDescriptionText($descriptionText, $translate = false, $params = [])
    {
        $this->descriptionText = $translate ? $this->translator->trans($descriptionText, $params) : $descriptionText;

        return $this;
    }

    public function translate($id, array $parameters = array(), $domain = null, $locale = null)
    {
        return $this->translator->trans($id, $parameters, $domain, $locale);
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

    /**
     * @return mixed
     */
    public function getVueApp()
    {
        return $this->vueApp;
    }

    /**
     * @param mixed $vueApp
     *
     * @return $this
     */
    public function setVueApp($vueApp)
    {
        $this->vueApp = $vueApp;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getVueBundledData()
    {
        return $this->vueBundledData;
    }

    /**
     * @param mixed $vueBundledData
     *
     * @return $this
     */
    public function setVueBundledData($vueBundledData)
    {
        $this->vueBundledData = $vueBundledData;

        return $this;
    }
}
