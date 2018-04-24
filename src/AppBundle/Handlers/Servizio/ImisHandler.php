<?php
/**
 * Created by PhpStorm.
 * User: coppo
 * Date: 26/04/18
 * Time: 16:02
 */

namespace AppBundle\Handlers\Servizio;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use GuzzleHttp\Client;
use AppBundle\Entity\CPSUser;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

class ImisHandler extends AbstractServizioHandler
{

/*
 * http://www2.comune.rovereto.tn.it/stcit/extra/precompilato/2018/CMRDRS68P42Z112Q/4938a204675df463c1ba9850d129b5a4/
 * Dopo l'indirizzo http://www2.comune.rovereto.tn.it/stcit/extra/precompilato/
 * i parametri devono essere indicati in questo modo :
 * Anno del precompilato:2018
 * Codice fiscale del soggetto che si è autenticato alla stanza del cittadino : 16 caratteri con lettere  in maiuscolo
 * MD5 della stringa formata dal concatenamento di AAAA, cf e la seguente password : 55234512A$
 *
 */

    private $baseUrl = 'http://www2.comune.rovereto.tn.it/stcit/extra/precompilato/';

    private $pwd = '55234512A$';


    public function __construct(TokenStorage $tokenStorage, LoggerInterface $logger)
    {
        parent::__construct($tokenStorage, $logger);
        $this->setCallToActionText('servizio.imis.download_pdf_imis');
    }

    /**
     * @return string
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * @param string $baseUrl
     * @return $this
     */
    public function setBaseUrl(string $baseUrl)
    {
        $this->baseUrl = $baseUrl;
        return $this;
    }

    /**
     * @return string
     */
    public function getPwd(): string
    {
        return $this->pwd;
    }

    /**
     * @param string $pwd
     * @return $this
     */
    public function setPwd(string $pwd)
    {
        $this->pwd = $pwd;
        return $this;
    }

    /**
     * @return array|Response|void
     */
    public function execute()
    {

        $user = $this->getUser();
        if ($user instanceof CPSUser) {
            $year = date('Y');
            $cf   = $user->getCodiceFiscale();

            // Todo: eliminare dopo prove
            //$cf   = 'CMRDRS68P42Z112Q';

            $url  = $this->baseUrl . $year . '/' . strtoupper($cf) . '/' . md5($year . $cf . $this->pwd) . '/';

            $client = new Client();
            $data = $client->get($url);

            if ($this->checkContentType($data->getHeader('Content-Type'))) {
                //$fileContent = file_get_contents($url);
                $fileContent = $data->getBody();

                $response = new Response($fileContent);
                $disposition = $response->headers->makeDisposition(
                    ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                    $cf . '-' . $year . '.pdf'
                );
                $response->headers->set('Content-Disposition', $disposition);
                return $response;

            }
        }
        return false;
    }


    /**
     * @return CPSUser|null
     */
    protected function getUser()
    {
        if (null === $token = $this->tokenStorage->getToken()) {
            return null;
        }

        if (!is_object($user = $token->getUser())) {
            return null;
        }

        return $user;
    }


    /**
     * @param $contentType
     * @return bool
     */
    protected function checkContentType( $contentType )
    {
        if ( is_array($contentType) ) {
            $contentType = $contentType[0];
        }
        if (strpos($contentType, 'application/pdf') === false) {
            return false;
        } else {
            return true;
        }
    }
}