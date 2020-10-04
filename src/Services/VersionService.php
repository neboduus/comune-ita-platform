<?php

namespace App\Services;

class VersionService
{

    private $version;

    public function getVersion()
    {
        if ($this->version == null)
        {
          if (is_file('../web/VERSION')) {
            $this->version = file_get_contents('../web/VERSION');
          } else {
            $this->version = 'Unknown';
          }
        }
        return $this->version;
    }
}
