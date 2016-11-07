<?php
namespace AppBundle\Form;

use Craue\FormFlowBundle\Storage\StorageKeyGeneratorInterface;

/**
 * Class UserSessionStorageKeyGenerator
 */
class UserSessionStorageKeyGenerator implements StorageKeyGeneratorInterface
{
    /**
     * @param string $key
     * @return string
     */
    public function generate($key)
    {
        return $key;
    }
}
