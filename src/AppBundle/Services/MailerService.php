<?php


namespace AppBundle\Services;


use AppBundle\Entity\CPSUser;
use AppBundle\Entity\Ente;
use AppBundle\Entity\OperatoreUser;
use AppBundle\Entity\Pratica;
use AppBundle\Entity\Subscriber;
use AppBundle\Model\SubscriberMessage;
use Symfony\Bundle\TwigBundle\TwigEngine;
use Symfony\Component\Form\Extension\Templating\TemplatingExtension;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;

class MailerService
{
    /**
     * @var \Swift_Mailer $mailer
     */
    private $mailer;

    /**
     * @var TranslatorInterface $translator
     */
    private $translator;

    /**
     * @var TemplatingExtension
     */
    private $templating;

    /**
     * @var RegistryInterface
     */
    private $doctrine;

    /**
     * MailerService constructor.
     * @param \Swift_Mailer $mailer
     */
    public function __construct(\Swift_Mailer $mailer, TranslatorInterface $translator, TwigEngine $templating, RegistryInterface $doctrine)
    {
        $this->mailer = $mailer;
        $this->translator = $translator;
        $this->templating = $templating;
        $this->doctrine = $doctrine;
    }

    private $blacklistedStates = [
        Pratica::STATUS_REQUEST_INTEGRATION,
        Pratica::STATUS_PROCESSING,
        Pratica::STATUS_SUBMITTED_AFTER_INTEGRATION,
        Pratica::STATUS_DRAFT,
        Pratica::STATUS_CANCELLED_WAITALLEGATIOPERATORE
    ];

    /**
     * @param Pratica $pratica
     * @param $fromAddress
     * @param bool $resend
     *
     * @return int
     */
    public function dispatchMailForPratica(Pratica $pratica, $fromAddress, $resend = false)
    {
        $sentAmount = 0;
        if (in_array($pratica->getStatus(), $this->blacklistedStates)) {
            return $sentAmount;
        }

        if ($this->CPSUserHasValidContactEmail($pratica->getUser()) &&
            ($resend || !$this->CPSUserHasAlreadyBeenWarned($pratica))
        ) {
            $CPSUsermessage = $this->setupCPSUserMessage($pratica, $fromAddress);
            $sentAmount += $this->mailer->send($CPSUsermessage);
            $pratica->setLatestCPSCommunicationTimestamp(time());
        }

        /**
         *Todo: se la pratica è in stato submitted (ancora non ha associato un operatore)
         *  - recuperare indirizzi email degli operatori abilitati alla pratica
         *  - inviare email ad operatori recuperati
         */

        if ($pratica->getStatus() == Pratica::STATUS_SUBMITTED)
        {

            $sql = "SELECT id from utente where servizi_abilitati like '%".$pratica->getServizio()->getId()."%'";
            $stmt = $this->doctrine->getManager()->getConnection()->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetchAll();

            $ids = [];
            foreach ($result as $id) {
                $ids[] = $id['id'];
            }

            $repo = $this->doctrine->getRepository('AppBundle:OperatoreUser');
            $operatori = $repo->findById($ids);
            if ( $operatori != null && !empty($operatori))
            {
                foreach ($operatori as $operatore)
                {
                    $operatoreUserMessage = $this->setupOperatoreUserMessage($pratica, $fromAddress, $operatore);
                    $sentAmount += $this->mailer->send($operatoreUserMessage);
                }
            }
        }

        if ($pratica->getOperatore() != null &&
            ($resend || !$this->operatoreUserHasAlreadyBeenWarned($pratica))
        ) {
            $operatoreUserMessage = $this->setupOperatoreUserMessage($pratica, $fromAddress);
            $sentAmount += $this->mailer->send($operatoreUserMessage);
            $pratica->setLatestOperatoreCommunicationTimestamp(time());
        }

        return $sentAmount;
    }

  /**
   * Sends generic email
   *
   * @param $fromAddress
   * @param $fromName
   * @param CPSUser $user
   * @param $message
   * @param $subject
   *
   * @return int
   * @throws \Twig\Error\Error
   */
  public function dispatchMail($fromAddress, $fromName,CPSUser $user, $message, $subject)
  {
    $sentAmount = 0;

    if ($this->isValidEmail($user->getEmail())){
      $emailMessage = \Swift_Message::newInstance()
        ->setSubject($subject)
        ->setFrom($fromAddress, $fromName)
        ->setTo($user->getEmail(), $user->getNome())
        ->setBody(
          $this->templating->render(
            'AppBundle:Emails/Subscriber:subscriber_message.html.twig',
            array(
              'message' => $message,
            )
          ),
          'text/html'
        )
        ->addPart(
          $this->templating->render(
            'AppBundle:Emails/Subscriber:subscriber_message.txt.twig',
            array(
              'message' => $message,
            )
          ),
          'text/plain'
        );
      $sentAmount += $this->mailer->send($emailMessage);
    }
    return $sentAmount;
  }

  /**
   * @param SubscriberMessage $subscriberMessage
   * @param $fromAddress
   * @param OperatoreUser $operatore
   * @return int
   */
    public function dispatchMailForSubscriber(SubscriberMessage $subscriberMessage, $fromAddress, OperatoreUser $operatore)
    {
        $sentAmount = 0;

        if ($this->SubscriberHasValidContactEmail($subscriberMessage->getSubscriber())){
            $message = $this->setupSubscriberMessage($subscriberMessage, $fromAddress, $operatore);
            $sentAmount += $this->mailer->send($message);
        }
        return $sentAmount;
    }

    /**
     * @param Pratica $pratica
     * @param $fromAddress
     * @return mixed
     */
    private function setupCPSUserMessage(Pratica $pratica, $fromAddress)
    {
        $toEmail = $pratica->getUser()->getEmailContatto();
        $toName = $pratica->getUser()->getFullName();

        $ente = $pratica->getEnte();
        $fromName = $ente instanceof Ente ? $ente->getName() : null;

        $message = \Swift_Message::newInstance()
            ->setSubject($this->translator->trans('pratica.email.status_change.subject'))
            ->setFrom($fromAddress, $fromName)
            ->setTo($toEmail, $toName)
            ->setBody(
                $this->templating->render(
                    'AppBundle:Emails/User:pratica_status_change.html.twig',
                    array(
                        'pratica' => $pratica,
                        'user_name'    => $pratica->getUser()->getFullName()
                    )
                ),
                'text/html'
            )
            ->addPart(
                $this->templating->render(
                    'AppBundle:Emails/User:pratica_status_change.txt.twig',
                    array(
                        'pratica' => $pratica,
                        'user_name'    => $pratica->getUser()->getFullName()
                    )
                ),
                'text/plain'
            );
        return $message;
    }


    private function setupOperatoreUserMessage(Pratica $pratica, $fromAddress, OperatoreUser $operatore = null)
    {
        if ($operatore == null)
        {
            $operatore = $pratica->getOperatore();
        }

        $toEmail = $operatore->getEmail();
        $toName = $operatore->getFullName();

        $ente = $pratica->getEnte();
        $fromName = $ente instanceof Ente ? $ente->getName() : null;

        $message = \Swift_Message::newInstance()
            ->setSubject($this->translator->trans('pratica.email.status_change.subject'))
            ->setFrom($fromAddress, $fromName)
            ->setTo($toEmail, $toName)
            ->setBody(
                $this->templating->render(
                    'AppBundle:Emails/Operatore:pratica_status_change.html.twig',
                    array(
                        'pratica' => $pratica,
                        'user_name'    => $operatore->getFullName()
                    )
                ),
                'text/html'
            )
            ->addPart(
                $this->templating->render(
                    'AppBundle:Emails/Operatore:pratica_status_change.txt.twig',
                    array(
                        'pratica' => $pratica,
                        'user_name'    => $operatore->getFullName()
                    )
                ),
                'text/plain'
            );
        return $message;
    }

    private function setupSubscriberMessage(SubscriberMessage $subscriberMessage, $fromAddress, OperatoreUser $operatoreUser)
    {
      $toEmail = $subscriberMessage->getSubscriber()->getEmail();
      $toName = $subscriberMessage->getFullName();

      $ente = $operatoreUser->getEnte();
      $fromName = $ente instanceof Ente ? $ente->getName() : null;

      $emailMessage = \Swift_Message::newInstance()
        ->setSubject($subscriberMessage->getSubject())
        ->setFrom($fromAddress, $fromName)
        ->setTo($toEmail, $toName)
        ->setBcc($operatoreUser->getEmail(), $operatoreUser->getFullName())
        ->setBody(
          $this->templating->render(
            'AppBundle:Emails/Subscriber:subscriber_message.html.twig',
            array(
              'message' => $subscriberMessage->getMessage(),
            )
          ),
          'text/html'
        )
        ->addPart(
          $this->templating->render(
            'AppBundle:Emails/Subscriber:subscriber_message.txt.twig',
            array(
              'message' => $subscriberMessage->getMessage(),
            )
          ),
          'text/plain'
        );
      if ($subscriberMessage->getAutoSend()) {
        $emailMessage->setCc($operatoreUser->getEmail(), $operatoreUser->getFullName());
      }


      return $emailMessage;
    }

    private function CPSUserHasAlreadyBeenWarned(Pratica $pratica)
    {
        return $pratica->getLatestCPSCommunicationTimestamp() >= $pratica->getLatestStatusChangeTimestamp();
    }

    private function operatoreUserHasAlreadyBeenWarned(Pratica $pratica)
    {
        return $pratica->getLatestOperatoreCommunicationTimestamp() >= $pratica->getLatestStatusChangeTimestamp();
    }

    private function CPSUserHasValidContactEmail(CPSUser $user)
    {
        $email = $user->getEmailContatto();

        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    private function SubscriberHasValidContactEmail(Subscriber $subscriber)
    {
        $email = $subscriber->getEmail();

        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    public function isValidEmail($email) {
      return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

}
