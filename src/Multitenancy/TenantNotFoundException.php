<?php

namespace App\Multitenancy;

use Throwable;

class TenantNotFoundException extends \Exception
{
    public function __construct(string $message = "", int $code = 0, Throwable $previous = null)
    {
        parent::__construct('Tenant not found', $code, $previous);
    }

    public function getUserMessage()
    {
        return 'E\' stata richiesta una pagina che non è possibile servire.';
    }
}
