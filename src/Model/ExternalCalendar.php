<?php


namespace App\Model;

use Swagger\Annotations as SWG;

class ExternalCalendar implements \JsonSerializable
{
    /**
     * @SWG\Property(description="External calendar's name", type="string")
     */
    private $name;

    /**
     * @SWG\Property(description="External calendar's url", type="string")
     */
    private $url;

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return ExternalCalendar
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get url.
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set url.
     *
     * @param string $url
     *
     * @return ExternalCalendar
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    public function jsonSerialize()
    {
        return array(
            'name' => $this->name,
            'url' => $this->url
        );
    }
}
