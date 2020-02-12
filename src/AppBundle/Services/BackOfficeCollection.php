<?php


namespace AppBundle\Services;


class BackOfficeCollection
{
  private $backOffices;
  private $nav = false;

  public function __construct(iterable $backOffices)
  {
    $this->backOffices = iterator_to_array($backOffices);
  }

  /**
   * @return mixed
   */
  public function getBackOffices()
  {
    return $this->backOffices;
  }

  /**
   * @param mixed $backOffices
   */
  public function setBackOffices($backOffices)
  {
    $this->backOffices = $backOffices;
  }

  public function getNav()
  {
    if (!$this->nav) {
      foreach ($this->backOffices as $backOffice) {
        $this->nav[$backOffice->getPath()] = $backOffice->getName();
      }
    }
    return $this->nav;
  }


}
