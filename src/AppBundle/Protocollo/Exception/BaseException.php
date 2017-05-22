<?php

namespace AppBundle\Protocollo\Exception;

use Exception;

class BaseException extends Exception
{
    public function __construct($message = "", $code = 0, Exception $previous = null)
    {
        parent::__construct($this->message, $code, $previous);
    }
}
