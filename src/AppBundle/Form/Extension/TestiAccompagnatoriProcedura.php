<?php

namespace AppBundle\Form\Extension;

use Symfony\Component\Translation\TranslatorInterface;

class TestiAccompagnatoriProcedura
{
    private $guideText;

    private $descriptionText;

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
        return $this->guideText;
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
        return $this->descriptionText;
    }

    /**
     * @param mixed $descriptionText
     * @param boolean $translate
     *
     * @return TestiAccompagnatoriProcedura
     */
    public function setDescriptionText($descriptionText, $translate = false)
    {
        $this->descriptionText = $translate ? $this->translator->trans($descriptionText) : $descriptionText;;

        return $this;
    }

    public function translate($palceholder){
        return $this->translator->trans($palceholder);
    }

}
