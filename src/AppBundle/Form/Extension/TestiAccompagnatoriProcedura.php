<?php

namespace AppBundle\Form\Extension;


class TestiAccompagnatoriProcedura
{
    private $guideText;

    private $descriptionText;

    /**
     * @return mixed
     */
    public function getGuideText()
    {
        return $this->guideText;
    }

    /**
     * @param mixed $guideText
     *
     * @return TestiAccompagnatoriProcedura
     */
    public function setGuideText($guideText)
    {
        $this->guideText = $guideText;

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
     *
     * @return TestiAccompagnatoriProcedura
     */
    public function setDescriptionText($descriptionText)
    {
        $this->descriptionText = $descriptionText;

        return $this;
    }

}
