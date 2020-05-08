<?php

namespace AppBundle\Services;

class VersionService
{

    private $version;

    public function getVersion()
    {
        if ($this->version == null)
        {
          $this->version = file_get_contents('../web/VERSION');
        }
        return $this->version;
    }
}
