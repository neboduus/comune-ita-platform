<?php


namespace App\Protocollo;


use App\Entity\AllegatoInterface;
use App\Entity\ModuloCompilato;
use App\Entity\Pratica;
use App\Entity\RispostaOperatore;
use App\Services\FileService\AllegatoFileService;
use Exception;
use Psr\Log\LoggerInterface;
use Swift_Mailer;
use Swift_Message;
use Twig\Environment;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Error\Error;

class PecProtocolloHandler implements ProtocolloHandlerInterface
{

  const IDENTIFIER = 'pec';

  public function getIdentifier(): string
  {
    return self::IDENTIFIER;
  }
  const TYPE_SEND_APPLICATION = 'send_applcation';
  const TYPE_SEND_INTEGRATION = 'send_integration';
  const TYPE_SEND_ATTACHMENT = 'send_attachment';
  const TYPE_SEND_RESULT      = 'send_result';


  /** @var string */
  private $host;

  /** @var string */
  private $port;

  /** @var */
  private $user;

  /** @var */
  private $password;

  /** @var string */
  private $sender;

  /**
   * @var TranslatorInterface $translator
   */
  private $translator;

  /**
   * @var Environment
   */
  private $templating;

  /**
   * @var LoggerInterface
   */
  private $logger;

  /**
   * @var Swift_Mailer
   */
  private $mailer = null;
  /**
   * @var AllegatoFileService
   */
  private $fileService;

  /**
   * PecProtocolloHandler constructor.
   * @param string|null $host
   * @param string|null $port
   * @param string|null $user
   * @param string|null $password
   * @param string|null $sender
   * @param TranslatorInterface $translator
   * @param Environment $templating
   * @param LoggerInterface $logger
   * @param AllegatoFileService $fileService
   */
  public function __construct(?string $host, ?string $port, ?string  $user, ?string  $password, ?string $sender, TranslatorInterface $translator, Environment $templating, LoggerInterface $logger, AllegatoFileService $fileService)
  {
    $this->host = $host;
    $this->port = $port;
    $this->user = $user;
    $this->password = $password;
    $this->sender = $sender;
    $this->translator = $translator;
    $this->templating = $templating;
    $this->logger = $logger;

    $transport = (new \Swift_SmtpTransport($host, $port))
      ->setUsername($user)
      ->setPassword($password)
      ->setEncryption('ssl');

    // Create the Mailer using your created Transport
    $this->mailer = new Swift_Mailer($transport);
    $this->fileService = $fileService;
  }

  public function getName()
  {
    return 'Pec';
  }

  public function getExecutionType()
  {
    return self::PROTOCOL_EXECUTION_TYPE_INTERNAL;
  }

  public function getConfigParameters()
  {
    // Tutto su db, riabilitare in seguito
    /*return array(
      'sender',
      'email',
      'host',
      'port',
      'password'
    );*/
    return array(
      'send_attachment' => [
        'type' => 'bool',
      ],
      'receiver'
    );
  }

  /**
   * @param Pratica $pratica
   * @throws Exception
   */
  public function sendPraticaToProtocollo(Pratica $pratica)
  {
    $parameters = $pratica->getServizio()->getProtocolloParameters();
    $this->checkParameters($parameters);
    $message = $this->setupMessage($pratica, $this->sender, $parameters['receiver'], self::TYPE_SEND_APPLICATION);

    if ($pratica->getModuliCompilati()->count() > 0 ) {
      /** @var AllegatoInterface $moduloCompilato */
      $moduloCompilato = $pratica->getModuliCompilati()->first();
      $attachment = new \Swift_Attachment($this->fileService->getAttachmentContent($moduloCompilato), $moduloCompilato->getFilename(), $this->fileService->getAttachmentMimeType($moduloCompilato));
      $message->attach($attachment);
    }
    $result = $this->mailer->send($message);

    if (!$result) {
      throw new Exception("Error sendPraticaToProtocollo application: " . $pratica->getId());
    }
    // Todo: Se eseguito da cronjob la parte di diminio dell'id è swift.generated
    $pratica->setNumeroProtocollo($message->getId());
    $pratica->setNumeroFascicolo($message->getId());
  }

  /**
   * @param Pratica $pratica
   * @param AllegatoInterface $allegato
   * @throws Exception
   */
  public function sendRichiestaIntegrazioneToProtocollo(Pratica $pratica, AllegatoInterface $allegato)
  {
    $this->sendAllegatoToProtocollo($pratica, $allegato);
  }

  public function sendAllegatoRichiestaIntegrazioneToProtocollo(Pratica $pratica, AllegatoInterface $richiestaIntegrazione, AllegatoInterface $allegato)
  {
    $this->sendAllegatoToProtocollo($pratica, $allegato);
  }

  /**
   * @param Pratica $pratica
   * @param AllegatoInterface $allegato
   * @throws Exception
   */
  public function sendRispostaIntegrazioneToProtocollo(Pratica $pratica, AllegatoInterface $allegato)
  {
    $this->sendAllegatoToProtocollo($pratica, $allegato);
  }

  /**
   * @param Pratica $pratica
   * @param AllegatoInterface $rispostaIntegrazione
   * @param AllegatoInterface $allegato
   * @throws Exception
   */
  public function sendIntegrazioneToProtocollo(Pratica $pratica, AllegatoInterface $rispostaIntegrazione, AllegatoInterface $allegato)
  {
    $parameters = $pratica->getServizio()->getProtocolloParameters();
    $this->checkParameters($parameters);
    $message = $this->setupMessage($pratica, $this->sender, $parameters['receiver'], self::TYPE_SEND_INTEGRATION);

    $attachment = new \Swift_Attachment($this->fileService->getAttachmentContent($allegato), $allegato->getFilename(), $this->fileService->getMimeType($allegato));
    $message->attach($attachment);
    $result = $this->mailer->send($message);

    if (!$result) {
      throw new Exception("Error sendIntegrazioneToProtocollo application: " . $pratica->getId() );
    }
  }

  /**
   * @param Pratica $pratica
   * @throws Exception
   */
  public function sendRispostaToProtocollo(Pratica $pratica)
  {
    $parameters = $pratica->getServizio()->getProtocolloParameters();
    $this->checkParameters($parameters);
    $message = $this->setupMessage($pratica, $this->sender, $parameters['receiver'], self::TYPE_SEND_RESULT);

    $risposta = $pratica->getRispostaOperatore();
    if ($risposta instanceof RispostaOperatore) {
      $attachment = new \Swift_Attachment($this->fileService->getAttachmentContent($risposta), $risposta->getFilename(), $this->fileService->getMimeType($risposta));
      $message->attach($attachment);
    }
    $result = $this->mailer->send($message);

    if (!$result) {
      throw new Exception("Error sendRispostaToProtocollo application: " . $pratica->getId());
    }

    if ($risposta instanceof RispostaOperatore) {
      $risposta->setNumeroProtocollo($message->getId());
      $risposta->setIdDocumentoProtocollo($message->getId());
    }
  }

  /**
   * @param Pratica $pratica
   * @param AllegatoInterface $allegato
   * @throws Exception
   */
  public function sendAllegatoToProtocollo(Pratica $pratica, AllegatoInterface $allegato)
  {

    $parameters = $pratica->getServizio()->getProtocolloParameters();

    if (!isset($parameters['send_attachment']) || !$parameters['send_attachment']) {
      return;
    }

    // Avoid duplicate email
    if ($allegato->getType() == ModuloCompilato::TYPE_DEFAULT) {
      return;
    }

    // Note: Not used in this handler
    $this->checkParameters($parameters);
    $message = $this->setupMessage($pratica, $this->sender, $parameters['receiver'], self::TYPE_SEND_ATTACHMENT);

    $attachment = new \Swift_Attachment($this->fileService->getAttachmentContent($allegato), $allegato->getFilename(), $this->fileService->getMimeType($allegato));
    $message->attach($attachment);
    $result = $this->mailer->send($message);

    if (!$result) {
      throw new Exception("Error sendAllegatoToProtocollo application: " . $pratica->getId() . " attachment: " . $allegato->getId());
    }

    $allegato->setNumeroProtocollo($message->getId());
    $pratica->addNumeroDiProtocollo([
      'id' => $allegato->getId(),
      'protocollo' => $message->getId(),
    ]);
  }

  /**
   * @param Pratica $pratica
   * @param AllegatoInterface $allegato
   */
  public function sendAllegatoRispostaToProtocollo(Pratica $pratica, AllegatoInterface $allegato)
  {
    // Note: Not used in this handler
  }

  /**
   * @param Pratica $pratica
   */
  public function sendRitiroToProtocollo(Pratica $pratica)
  {
    // Note: Not used in this handler
  }


  /**
   * @param Pratica $pratica
   * @param $sender
   * @param $receiver
   * @param $type
   * @return Swift_Message
   * @throws Error
   */
  private function setupMessage(Pratica $pratica, $sender, $receiver, $type)
  {
    $ente = $pratica->getEnte();
    $praticaIdParts = explode('-', $pratica->getId());

    $subject = $pratica->getServizio()->getName() . ' - ' . $pratica->getUser()->getFullName() . ' ('. end($praticaIdParts) .')';

    if ($type == self::TYPE_SEND_ATTACHMENT) {
      $subject .= ' - allegato';
    }

    return (new Swift_Message())
      ->setSubject($subject)
      ->setFrom($sender, 'Stanza del Cittadino')
      ->setTo($receiver, $ente->getName())
      ->setBody(
        $this->templating->render(
          'Emails/Pec/content.html.twig',
          array(
            'pratica' => $pratica,
            'type'    => $type
          )
        ),
        'text/html'
      )
      ->addPart(
        $this->templating->render(
          'Emails/Pec/content.html.twig',
          array(
            'pratica' => $pratica,
            'type'    => $type
          )
        ),
        'text/plain'
      );
  }

  /**
   * @param $parameters
   * @throws Exception
   */
  private function checkParameters($parameters)
  {
    if ( !isset($parameters['receiver'])) {
      throw new Exception("Missing required field: receiver");
    }
  }
}
