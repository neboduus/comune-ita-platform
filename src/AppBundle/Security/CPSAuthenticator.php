<?php

namespace AppBundle\Security;


use AppBundle\Entity\CPSUser;
use AppBundle\Services\CPSUserProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;

/**
 * Class CPSAuthenticator
 * @package AppBundle\Security
 */
class CPSAuthenticator extends AbstractGuardAuthenticator
{

    /**
     * @var array
     */
    //TODO:test this mapping
    private static $userDataKeys = [
        /**
         * The fpm process receives these values as $_SERVER from apache+mod_shib
         */
        "shibb_pat_attribute_codicefiscale" => "codiceFiscale",
        "shibb_pat_attribute_capdomicilio" => "capDomicilio",
        "shibb_pat_attribute_capresidenza" => "capResidenza",
        "shibb_pat_attribute_cellulare" => "cellulare",
        "shibb_pat_attribute_cittadomicilio" => "cittaDomicilio",
        "shibb_pat_attribute_cittaresidenza" => "cittaResidenza",
        "shibb_pat_attribute_cognome" => "cognome",
        "shibb_pat_attribute_datanascita" => "dataNascita",
        "shibb_pat_attribute_emailaddress" => "emailAddress",
        "shibb_pat_attribute_emailaddresspersonale" => "emailAddressPersonale",
        "shibb_pat_attribute_indirizzodomicilio" => "indirizzoDomicilio",
        "shibb_pat_attribute_indirizzoresidenza" => "indirizzoResidenza",
        "shibb_pat_attribute_luogonascita" => "luogoNascita",
        "shibb_pat_attribute_nome" => "nome",
        "shibb_pat_attribute_provinciadomicilio" => "provinciaDomicilio",
        "shibb_pat_attribute_provincianascita" => "provinciaNascita",
        "shibb_pat_attribute_provinciaresidenza" => "provinciaResidenza",
        "shibb_pat_attribute_sesso" => "sesso",
        "shibb_pat_attribute_statodomicilio" => "statoDomicilio",
        "shibb_pat_attribute_statonascita" => "statoNascita",
        "shibb_pat_attribute_statoresidenza" => "statoResidenza",
        "shibb_pat_attribute_telefono" => "telefono",
        "shibb_pat_attribute_titolo" => "titolo",
        "shibb_pat_attribute_x509certificate_issuerdn" => "x509certificate_issuerdn",
        "shibb_pat_attribute_x509certificate_subjectdn" => "x509certificate_subjectdn",
        "shibb_pat_attribute_x509certificate_base64" => "x509certificate_base64",        
    ];

    /**
     * @param Request                      $request
     * @param AuthenticationException|null $authException
     * @return Response
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        return new Response('Authentication Required', 401);
    }

    /**
     * @param Request $request
     * @return array|null
     */
    public function getCredentials(Request $request)
    {
        $data = self::createUserDataFromRequest($request);
        if ($data["codiceFiscale"] === null) {
            return null;
        }

        return $data;
    }

    /**
     * @param mixed                 $credentials
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

    /**
     * @param mixed         $credentials
     * @param UserInterface $user
     * @return bool
     */
    public function checkCredentials($credentials, UserInterface $user)
    {
        return true;
    }

    /**
     * @param Request                 $request
     * @param AuthenticationException $exception
     * @return Response
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        $message = strtr($exception->getMessageKey(), $exception->getMessageData());

        return new Response($message, 403);
    }

    /**
     * @param Request        $request
     * @param TokenInterface $token
     * @param string         $providerKey
     * @return null
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        return null;
    }

    /**
     * @inheritdoc
     * @return bool
     */
    public function supportsRememberMe()
    {
        return false;
    }

    private static function createUserDataFromRequest(Request $request)
    {
        $serverProps = $request->server->all();
        $data = [];
        foreach (self::$userDataKeys as $shibbKey => $ourKey) {
            $data[$ourKey] = isset($serverProps[$shibbKey])? $serverProps[$shibbKey] : null;
        }

        return $data;
    }
}
