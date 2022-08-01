<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Ramsey\Uuid\Uuid;

/**
 * @ORM\Entity
 * @ORM\Table(name="scheduled_action")
 */
class ScheduledAction
{
    const STATUS_PENDING = 1;

    const STATUS_DONE = 3;

    const STATUS_INVALID = 4;

    /**
     * Hook timestampable behavior
     * updates createdAt, updatedAt fields
     */
    use TimestampableEntity;

    /**
     * @ORM\Column(type="guid")
     * @ORM\Id
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    private $service;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=100)
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $params;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $hostname;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $log;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", nullable=true, options={"default":"1"})
     */
    private $status;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", nullable=true, options={"default":"0"})
     */
    private $retry;

    public function __construct()
    {
        if ( !$this->id) {
            $this->id = Uuid::uuid4();
        }
        $this->createdAt = new \DateTime('now', new \DateTimeZone(date_default_timezone_get()));
        $this->updatedAt = new \DateTime('now', new \DateTimeZone(date_default_timezone_get()));
        $this->status = self::STATUS_PENDING;
    }


    public function getId()
    {
      return $this->id;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return ScheduledAction
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return string
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * @param string $params
     *
     * @return ScheduledAction
     */
    public function setParams($params)
    {
        $this->params = $params;

        return $this;
    }

    /**
     * @return string
     */
    public function getService()
    {
        return $this->service;
    }

    /**
     * @param string $service
     *
     * @return ScheduledAction
     */
    public function setService($service)
    {
        $this->service = $service;

        return $this;
    }

    /**
     * @return string
     */
    public function getHostname(): ?string
    {
      return $this->hostname;
    }

    /**
     * @param string $hostname
     */
    public function setHostname(?string $hostname): void
    {
      $this->hostname = $hostname;
    }

    /**
     * @return string
     */
    public function getLog(): ?string
    {
      return $this->log;
    }

    /**
     * @param string $log
     */
    public function setLog(?string $log): void
    {
      $this->log = $log;
    }

    /**
     * @return int
     */
    public function getStatus(): int
    {
      return $this->status;
    }

    /**
     * @param int $status
     */
    public function setStatus(int $status): void
    {
      $this->status = $status;
    }

    /**
     * @return int
     */
    public function getRetry(): ?int
    {
      return $this->retry;
    }

    /**
     * @param int $retry
     */
    public function setRetry(?int $retry): void
    {
      $this->retry = $retry;
    }

   /**
    * @param int $retry
    */
    public function incRetry(): void
    {
      $this->retry++;
    }

    public function setDone()
    {
      $this->status = self::STATUS_DONE;
    }

    public function setInvalid()
    {
      $this->status = self::STATUS_INVALID;
    }

}
