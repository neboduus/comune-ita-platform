<?php


namespace AppBundle\Model;


class CallToAction implements \JsonSerializable
{
  /**
   * @var string
   */
  private $label;

  /**
   * @var string
   */
  private $link;


  /**
   * @return string
   */
  public function getLabel(): string
  {
    return $this->label;
  }

  /**
   * @param string $label
   */
  public function setLabel(string $label)
  {
    $this->label = $label;
  }

  /**
   * @return string
   */
  public function getLink(): string
  {
    return $this->link;
  }

  /**
   * @param string $link
   */
  public function setLink(string $link)
  {
    $this->link = $link;
  }

  public function jsonSerialize()
  {
    return get_object_vars($this);
  }

}
