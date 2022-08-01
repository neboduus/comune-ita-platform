<?php
namespace App\Services;

/**
 * Class P7MSignatureCheckService
 */
class P7MSignatureCheckService
{
    /**
     * @param string $absolutePath
     * @return bool
     */
    public function check($absolutePath): bool
    {
        exec("openssl pkcs7 -inform DER -in $absolutePath -print_certs 2>&1", $output);
        $regex = '/(subject).*(serialNumber).*(issuer).*(BEGIN CERTIFICATE).*(END CERTIFICATE).*/';
        if (preg_match($regex, implode('', $output)) === 1) {
            return true;
        }

        return false;
    }

}
