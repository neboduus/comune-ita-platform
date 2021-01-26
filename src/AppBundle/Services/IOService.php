<?php


namespace AppBundle\Services;


use AppBundle\Entity\Pratica;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Client;
use League\HTMLToMarkdown\HtmlConverter;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;

class IOService
{

  /**
   * @var string
   */
  private $IOApiUrl;

  /**
   * @var LoggerInterface
   */
  private $logger;

  /**
   * @var TranslatorInterface
   */
  private $translator;

  /**
   * @var RouterInterface
   */
  private $router;


  public function __construct(string $IOApiUrl, LoggerInterface $logger, TranslatorInterface $translator, RouterInterface $router)
  {
    $this->IOApiUrl = $IOApiUrl;
    $this->logger = $logger;
    $this->translator = $translator;
    $this->router = $router;
  }

  /**
   * @param $fiscalCode
   * @param $primaryKey
   * @return array
   */
  private function getProfile($primaryKey, $fiscalCode)
  {
    $client = new Client(['base_uri' => $this->IOApiUrl]);

    $request = new Request(
      'GET',
      $client->getConfig('base_uri') . '/profiles/' . strtoupper($fiscalCode),
      ['Content-Type' => 'application/json', 'Ocp-Apim-Subscription-Key' => $primaryKey]
    );

    try {
      $response = $client->send($request);
      return json_decode($response->getBody(), true);
    } catch (\Throwable $exception) {
      $this->logger->error($exception->getMessage());
      return json_decode($exception->getResponse()->getBody()->getContents(), true);
    }
  }

  /**
   * @param $fiscalCode
   * @param $primaryKey
   * @return array
   */
  private function sendMessage($primaryKey, $fiscalCode, $subject, $markdown)
  {
    $converter = new HtmlConverter();
    $client = new Client(['base_uri' => $this->IOApiUrl]);

    $data = [
      'content' => [
        'subject' => $subject,
        'markdown' => $converter->convert($markdown),
      ],
      'fiscal_code' => strtoupper($fiscalCode)
    ];

    $request = new Request(
      'POST',
      $client->getConfig('base_uri') . '/messages',
      ['Content-Type' => 'application/json', 'Ocp-Apim-Subscription-Key' => $primaryKey],
      json_encode($data)
    );

    try {
      $response = $client->send($request);
      return json_decode($response->getBody(), true);
    } catch (\Throwable $exception) {
      $this->logger->error($exception->getMessage());
      return json_decode($exception->getResponse()->getBody()->getContents(), true);
    }
  }

  public function test($serviceId, $primaryKey, $secondaryKey, $fiscalCode)
  {
    $profileResponse = $this->getProfile($primaryKey, $fiscalCode);
    if (key_exists("detail", $profileResponse)) {
      return ["error"=> $profileResponse["detail"]];
    } elseif (key_exists("message", $profileResponse)) {
      return ["error"=> $profileResponse["message"]];
    }

    if (!$profileResponse['sender_allowed']) {
      return ['error' => "Sender not allowed"];
    }

    $messageResponse =$this->sendMessage(
      $primaryKey,
      $fiscalCode,
      $this->translator->trans('app_io.test.subject'),
      $this->translator->trans('app_io.test.markdown')
    );

    if (key_exists("detail", $profileResponse)) {
      return ["error"=> $profileResponse["detail"]];
    } elseif (key_exists("message", $profileResponse)) {
      return ["error"=> $profileResponse["message"]];
    }
    return $messageResponse;
  }

  public function sendMessageForPratica(Pratica $pratica, $message, $subject)
  {
    $sentAmount = 0;
    $primaryKey = $pratica->getServizio()->getIOServiceParameters()["primaryKey"];
    $fiscalCode = $pratica->getUser()->getCodiceFiscale();

    $profileResponse = $this->getProfile($primaryKey, $fiscalCode);
    if (key_exists("detail", $profileResponse)) {
      $this->logger->debug("Error in sendMessageForPratica: " . $profileResponse["detail"]);
      return $sentAmount;
    } elseif (key_exists("message", $profileResponse)) {
      $this->logger->debug("Error in sendMessageForPratica: " . $profileResponse["message"]);
      return $sentAmount;
    }

    if (!$profileResponse['sender_allowed']) {
      $this->logger->debug("Error in sendMessageForPratica: sender " . $fiscalCode . ' not allowed');
      return $sentAmount;
    }

    $messageResponse =$this->sendMessage(
      $primaryKey,
      $fiscalCode,
      $subject,
      $message
    );

    if (key_exists("detail", $messageResponse)) {
      $this->logger->debug("Error in sendMessageForPratica: " . $messageResponse["detail"]);
      return $sentAmount;
    } elseif (key_exists("message", $messageResponse)) {
      $this->logger->debug("Error in sendMessageForPratica: " . $messageResponse["messageA"]);
      return $sentAmount;
    } elseif (key_exists("id", $messageResponse)) {
      $sentAmount += 1;
    }

    return $sentAmount;
  }

}
