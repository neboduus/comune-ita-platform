<?php

namespace App\Protocollo\Exception;

class AlreadySentException extends BaseException
{
    protected $message = 'Item already sent';
}
