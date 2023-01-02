<?php

namespace App\Services;

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
      if (is_file('../public/VERSION')) {
        $this->version = file_get_contents('../public/VERSION');
      } else {
        $this->version = 'Unknown';
      }
    }
    return $this->version;
  }
}
