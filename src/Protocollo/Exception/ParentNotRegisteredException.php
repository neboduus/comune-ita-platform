<?php

namespace App\Protocollo\Exception;

class ParentNotRegisteredException extends BaseException
{
    protected $message = 'Parent item is not registered';
}
