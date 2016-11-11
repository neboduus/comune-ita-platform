<?php

namespace AppBundle\Protocollo\Exception;

class AlreadyScheduledException extends BaseException
{
    protected $message = 'Item is already scheduled';

}
