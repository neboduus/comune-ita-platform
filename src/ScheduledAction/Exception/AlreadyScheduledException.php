<?php

namespace App\ScheduledAction\Exception;

use Exception;

class AlreadyScheduledException extends Exception
{
    protected $message = 'Item is already scheduled';

    public function __construct($message = "", $code = 0, Exception $previous = null)
    {
        parent::__construct($this->message, $code, $previous);
    }

}
