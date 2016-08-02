<?php

namespace AppBundle\Security;


use AppBundle\Entity\CPSUser;
use AppBundle\Services\CPSUserProvider;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Symfony\Component\VarDumper\VarDumper;

class CPSAuthenticator extends AbstractGuardAuthenticator
{
    private static $userDataKeys = [
        "codiceFiscale",
        "capDomicilio",
        "capResidenza",
        "cellulare",
        "cittaDomicilio",
        "cittaResidenza",
        "cognome",
        "dataNascita",
        "emailAddress",
        "emailAddressPersonale",
        "indirizzoDomicilio",
        "indirizzoResidenza",
        "luogoNascita",
        "nome",
        "provinciaDomicilio",
        "provinciaNascita",
        "provinciaResidenza",
        "sesso",
        "statoDomicilio",
        "statoNascita",
        "statoResidenza",
        "telefono",
        "titolo",
        "x509certificate_issuerdn",
        "x509certificate_subjectdn",
        "x509certificate_base64",
    ];

    private static function createUserDataFromRequest(Request $request)
    {
        $data = array_fill_keys( self::$userDataKeys, null);
        array_walk(
            $data,
            function(&$item, $key, HeaderBag $headers){
                /** @see \Symfony\Component\HttpFoundation\Request::overrideGlobals() Header keys transformation */
                $filteredKey = str_replace( '_', '-', strtolower($key) );
                if($headers->has($filteredKey)){
                    $item = $headers->get($filteredKey);
                }
            },
            $request->headers
        );
        return $data;
    }

    public function start(Request $request, AuthenticationException $authException = null)
    {
        $data = array(
            'message' => 'Authentication Required'
        );

        return new JsonResponse($data, 401);
    }

    public function getCredentials(Request $request)
    {
        $data = self::createUserDataFromRequest($request);
        if ($data["codiceFiscale"] === null){
            return null;
        }

        return $data;

    }

    /**
     * @param mixed $credentials
     * @param UserProviderInterface $userProvider
     *
     * @return CPSUser
     * @throws \InvalidArgumentException
     */
    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        if ($userProvider instanceof CPSUserProvider) {
            return $userProvider->provideUser($credentials);
        }
        throw new \InvalidArgumentException(
            sprintf("UserProvider for CPSAuthenticator must be a %s instance", CPSUserProvider::class)
        );
    }

    public function checkCredentials($credentials, UserInterface $user)
    {
        return true;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        $data = array(
            'message' => strtr($exception->getMessageKey(), $exception->getMessageData())
        );

        return new JsonResponse($data, 403);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        return null;
    }

    public function supportsRememberMe()
    {
        return false;
    }
}
