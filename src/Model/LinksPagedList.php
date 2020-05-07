<?php

namespace App\Model;

use JMS\Serializer\Annotation as Serializer;
use Swagger\Annotations as SWG;

class LinksPagedList
{
    /**
     * @var string
     * @Serializer\Type("string")
     * @SWG\Property(description="Total number of objects")
     */
    private $self;

    /**
     * @var string
     * @Serializer\Type("string")
     * @SWG\Property(description="Link to to prev page")
     */
    private $prev;

    /**
     * @var string
     * @Serializer\Type("string")
     * @SWG\Property(description="Link to next page")
     */
    private $next;

    /**
     * @return string
     */
    public function getSelf(): string
    {
        return $this->self;
    }

    /**
     * @param string $self
     */
    public function setSelf(string $self): void
    {
        $this->self = $self;
    }

    /**
     * @return string
     */
    public function getPrev(): string
    {
        return $this->prev;
    }

    /**
     * @param string $prev
     */
    public function setPrev(string $prev): void
    {
        $this->prev = $prev;
    }

    /**
     * @return string
     */
    public function getNext(): string
    {
        return $this->next;
    }

    /**
     * @param string $next
     */
    public function setNext(string $next): void
    {
        $this->next = $next;
    }


    /**
     * @return array|mixed
     */
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}
