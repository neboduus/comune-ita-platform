<?php

namespace App\Handlers\Servizio;

use App\Entity\CPSUser;
use App\Entity\Ente;
use App\Entity\Servizio;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ImisHandler extends AbstractServizioHandler
{
  /*
   * http://www2.comune.rovereto.tn.it/stcit/extra/precompilato/2018/CMRDRS68P42Z112Q/4938a204675df463c1ba9850d129b5a4/
   * Dopo l'indirizzo http://www2.comune.rovereto.tn.it/stcit/extra/precompilato/
   * i parametri devono essere indicati in questo modo :
   * Anno del precompilato:2018
   * Codice fiscale del soggetto che si è autenticato alla stanza del cittadino : 16 caratteri con lettere  in maiuscolo
   * MD5 della stringa formata dal concatenamento di AAAA, cf e la password
   *
   */

  private $baseUrl = 'http://www2.comune.rovereto.tn.it/stcit/extra/precompilato/';

  private $pwd = '55234512A$';

  public function __construct(TokenStorageInterface $tokenStorage, LoggerInterface $logger, UrlGeneratorInterface $router)
  {
    parent::__construct($tokenStorage, $logger, $router);
    $this->setCallToActionText('servizio.imis.download_pdf_imis');
  }

  public function getErrorMessage()
  {
    return "Non é possibile effettuare il download del file. Non risultano immobili a suo nome all'interno del comune.";
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
   * @param Servizio $servizio
   * @param Ente $ente
   * @return Response
   * @throws \Exception
   */
  public function execute(Servizio $servizio, Ente $ente)
  {
    $user = $this->getUser();
    if ($user instanceof CPSUser) {
      $year = date('Y');
      $cf = $user->getCodiceFiscale();

      // Todo: eliminare dopo prove
      //$cf   = 'CMRDRS68P42Z112Q';

      $url = $this->baseUrl.$year.'/'.strtoupper($cf).'/'.md5($year.$cf.$this->pwd).'/';

      $client = new Client();
      $data = $client->get($url);

      if ($this->checkContentType($data->getHeader('Content-Type'))) {
        //$fileContent = file_get_contents($url);
        $fileContent = $data->getBody();

        $response = new Response($fileContent);
        $disposition = $response->headers->makeDisposition(
          ResponseHeaderBag::DISPOSITION_ATTACHMENT,
          $cf.'-'.$year.'.pdf'
        );
        $response->headers->set('Content-Disposition', $disposition);

        return $response;

      }
    }

    throw new \Exception("User not found");
  }

  /**
   * @param $contentType
   * @return bool
   */
  protected function checkContentType($contentType)
  {
    if (is_array($contentType)) {
      $contentType = $contentType[0];
    }
    if (strpos($contentType, 'application/pdf') === false) {
      return false;
    } else {
      return true;
    }
  }
}
