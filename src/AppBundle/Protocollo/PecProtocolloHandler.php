<?php


namespace AppBundle\Protocollo;


use AppBundle\Entity\AllegatoInterface;
use AppBundle\Entity\Ente;
use AppBundle\Entity\Pratica;
use AppBundle\Services\MailerService;
use Hoa\Event\Exception;
use Psr\Log\LoggerInterface;
use Swift_Mailer;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Bundle\TwigBundle\TwigEngine;
use Symfony\Component\Form\Extension\Templating\TemplatingExtension;
use Symfony\Component\Translation\TranslatorInterface;

class PecProtocolloHandler implements ProtocolloHandlerInterface
{

  const TYPE_SEND_APPLICATION = 'send_applcation';
  const TYPE_SEND_INTEGRATION = 'send_integration';
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
   * @var TwigEngine
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
   * PecProtocolloHandler constructor.
   * @param string $host
   * @param string $port
   * @param string $user
   * @param string $password
   * @param string $sender
   * @param TranslatorInterface $translator
   * @param TemplatingExtension $templating
   * @param LoggerInterface $logger
   */
  public function __construct(string $host, string $port, $user, $password, string $sender, TranslatorInterface $translator, TwigEngine $templating, LoggerInterface $logger)
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
      'receiver'
    );
  }

  /**
   * @param Pratica $pratica
   * @throws \Exception
   */
  public function sendPraticaToProtocollo(Pratica $pratica)
  {
    $parameters = $pratica->getServizio()->getProtocolloParameters();
    $this->checkParameters($parameters);
    $message = $this->setupMessage($pratica, $this->sender, $parameters['receiver'], self::TYPE_SEND_APPLICATION);

    if ($pratica->getModuliCompilati()->count() > 0 ) {
      $moduloCompilato = $pratica->getModuliCompilati()->first();
      $message->attach(\Swift_Attachment::fromPath($moduloCompilato->getFile()->getPathname()));
    }
    $result = $this->mailer->send($message);

    if (!$result) {
      throw new \Exception("Error sendPraticaToProtocollo application: " . $pratica->getId());
    }
    // Todo: Se eseguito da cronjob la parte di diminio dell'id è swift.generated
    $pratica->setNumeroProtocollo($message->getId());
    $pratica->setNumeroFascicolo($message->getId());
  }

  /**
   * @param Pratica $pratica
   * @param AllegatoInterface $allegato
   * @throws \Exception
   */
  public function sendIntegrazioneToProtocollo(Pratica $pratica, AllegatoInterface $allegato)
  {
    $parameters = $pratica->getServizio()->getProtocolloParameters();
    $this->checkParameters($parameters);
    $message = $this->setupMessage($pratica, $this->sender, $parameters['receiver'], self::TYPE_SEND_INTEGRATION);

    $message->attach(\Swift_Attachment::fromPath($allegato->getFile()->getPathname()));
    $result = $this->mailer->send($message);

    if (!$result) {
      throw new \Exception("Error sendIntegrazioneToProtocollo application: " . $pratica->getId() );
    }
  }

  /**
   * @param Pratica $pratica
   * @throws \Twig\Error\Error
   */
  public function sendRispostaToProtocollo(Pratica $pratica)
  {
    $parameters = $pratica->getServizio()->getProtocolloParameters();
    $this->checkParameters($parameters);
    $message = $this->setupMessage($pratica, $this->sender, $parameters['receiver'], self::TYPE_SEND_RESULT);

    $risposta = $pratica->getRispostaOperatore();
    if ($risposta != null ) {
      $message->attach(\Swift_Attachment::fromPath($risposta->getFile()->getPathname()));
    }
    $result = $this->mailer->send($message);

    if (!$result) {
      throw new \Exception("Error sendRispostaToProtocollo application: " . $pratica->getId());
    }
  }

  public function sendAllegatoToProtocollo(Pratica $pratica, AllegatoInterface $allegato)
  {
    // Note: Not used in this handler
  }

  public function sendAllegatoRispostaToProtocollo(Pratica $pratica, AllegatoInterface $allegato)
  {
    // Note: Not used in this handler
  }

  public function sendRitiroToProtocollo(Pratica $pratica)
  {
    // TODO: Implement sendRitiroToProtocollo() method.
  }


  /**
   * @param Pratica $pratica
   * @param $sender
   * @param $receiver
   * @param $type
   * @return \Swift_Message
   * @throws \Twig\Error\Error
   */
  private function setupMessage(Pratica $pratica, $sender, $receiver, $type)
  {
    $ente = $pratica->getEnte();
    $praticaIdParts = explode('-', $pratica->getId());
    $message = \Swift_Message::newInstance()
      ->setSubject($pratica->getServizio()->getName() . ' - ' . $pratica->getUser()->getFullName() . ' ('. end($praticaIdParts) .')')
      ->setFrom($sender, 'Stanza del Cittadino')
      ->setTo($receiver, $ente->getName())
      ->setBody(
        $this->templating->render(
          'AppBundle:Emails/Pec:content.html.twig',
          array(
            'pratica' => $pratica,
            'type'    => $type
          )
        ),
        'text/html'
      )
      ->addPart(
        $this->templating->render(
          'AppBundle:Emails/Pec:content.html.twig',
          array(
            'pratica' => $pratica,
            'type'    => $type
          )
        ),
        'text/plain'
      );

    return $message;
  }

  /**
   * @param $parameters
   * @throws \Exception
   */
  private function checkParameters($parameters)
  {
    foreach ($this->getConfigParameters() as $parameter) {
      if ( !isset($parameters[$parameter])) {
        throw new \Exception("Missing required field: " . $parameter);
      }
    }
  }
}
