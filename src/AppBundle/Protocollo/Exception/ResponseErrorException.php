<?php

namespace AppBundle\Protocollo\Exception;

use Exception;

class ResponseErrorException extends Exception
{
    public function __construct($message = "", $code = 0, Exception $previous = null)
    {
        parent::__construct("Unexpected response: " . $message, $code, $previous);
    }
}
