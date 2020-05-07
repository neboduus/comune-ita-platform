<?php


namespace App\Model;

use Swagger\Annotations as SWG;

class DateTimeInterval implements \JsonSerializable
{
    /**
     * @var \DateTime
     *
     * @SWG\Property(description="Datetime interval's start date", type="dateTime")
     */
    private $fromTime;

    /**
     * @var \DateTime
     *
     * @SWG\Property(description="Datetime interval's end date", type="dateTime")
     */
    private $toTime;

    /**
     * Set fromTime.
     *
     * @param \DateTime $fromTime
     *
     * @return DateTimeInterval
     */
    public function setFromTime($fromTime)
    {
        $this->fromTime = $fromTime;

        return $this;
    }

    /**
     * Get fromTime.
     *
     * @return \DateTime
     */
    public function getFromTime()
    {
        return $this->fromTime;
    }

    /**
     * Set toTime.
     *
     * @param \DateTime $toTime
     *
     * @return DateTimeInterval
     */
    public function setToTime($toTime)
    {
        $this->toTime = $toTime;

        return $this;
    }

    /**
     * Get toTime.
     *
     * @return \DateTime
     */
    public function getToTime()
    {
        return $this->toTime;
    }

    public function jsonSerialize()
    {
        return array(
            'from_time' => $this->fromTime->format(\DateTime::ATOM),
            'to_time' => $this->toTime->format(\DateTime::ATOM)
        );
    }
}
