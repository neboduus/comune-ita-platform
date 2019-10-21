<?php


namespace AppBundle\Entity;


use Doctrine\ORM\Mapping as ORM;
use Psr\Log\LogLevel;
use Ramsey\Uuid\Uuid;
use Xiidea\EasyAuditBundle\Entity\BaseAuditLog;

/**
 * @ORM\Entity
 * @ORM\Table(name="audit_log")
 */
class AuditLog extends BaseAuditLog
{
    /**
     * @ORM\Column(type="guid")
     * @ORM\Id
     */
    protected $id;

    /**
     * Type Of Event(Internal Type ID)
     *
     * @var string
     * @ORM\Column(name="type_id", type="string", length=200, nullable=false)
     */
    protected $typeId;

    /**
     * Type Of Event
     *
     * @var string
     * @ORM\Column(name="type", type="string", length=200, nullable=true)
     */
    protected $type;

    /**
     * @var string
     * @ORM\Column(name="description", type="string", length=255, nullable=true)
     */
    protected $description;

    /**
     * Time Of Event
     * @var \DateTime
     * @ORM\Column(name="event_time", type="datetime")
     */
    protected $eventTime;

    /**
     * @var string
     * @ORM\Column(name="utente", type="string", length=255)
     */
    protected $user;

    /**
     * @var string
     * @ORM\Column(name="impersonating_user", type="string", length=255, nullable=true)
     */
    protected $impersonatingUser;

    /**
     * @var string
     * @ORM\Column(name="ip", type="string", length=20, nullable=true)
     */
    protected $ip;

    /**
     * @var string
     * @ORM\Column(name="level", type="string", length=255, nullable=true)
     */
    protected $level = LogLevel::INFO;

    /**
     * AuditLog constructor.
     * @throws \Exception
     */
    public function __construct()
    {
        $this->id = Uuid::uuid4();
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

}