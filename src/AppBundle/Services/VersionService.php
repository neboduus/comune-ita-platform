<?php

namespace AppBundle\Services;

class VersionService
{

  private $version;

  /**
   * @param $version
   */
  public function __construct($version)
  {
    $this->version = $version;
  }

  /**
   * @return false|string
   */
  public function getVersion()
  {
    if ($this->version == null) {
      if (is_file('../web/VERSION')) {
        $this->version = file_get_contents('../web/VERSION');
      } else {
        $this->version = 'Unknown';
      }
    }
    return $this->version;
  }
}
