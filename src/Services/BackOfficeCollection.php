<?php


namespace App\Services;

use App\BackOffice\BackOfficeInterface;

class BackOfficeCollection
{
  /**
   * @var BackOfficeInterface[]
   */
  private $backOffices;

  private $nav;

  public function __construct()
  {
    $this->backOffices = [];
  }

  /**
   * @return BackOfficeInterface[]
   */
  public function getBackOffices()
  {
    return $this->backOffices;
  }

  /**
   * @param BackOfficeInterface[] $backOffices
   */
  public function setBackOffices($backOffices)
  {
    $this->backOffices = $backOffices;
  }

  /**
   * @param BackOfficeInterface $backOffice
   */
  public function addBackOffice($backOffice)
  {
    $this->backOffices[] = $backOffice;
  }

  public function getBackOffice($name)
  {
    $name = str_replace('AppBundle', 'App', $name);
    foreach ($this->backOffices as $backOffice) {
      if (get_class($backOffice) == $name) {
        return $backOffice;
      }
    }

    return null;
  }

  public function getNav()
  {
    if (null === $this->nav) {
      foreach ($this->backOffices as $backOffice) {
        $this->nav[$backOffice->getPath()] = $backOffice->getName();
      }
    }
    return $this->nav;
  }
}
