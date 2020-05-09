<?php

namespace App\Services;

use App\Entity\CPSUser;
use App\Entity\Ente;
use App\Entity\OperatoreUser;
use App\Entity\Pratica;
use App\Entity\Subscriber;
use App\Model\SubscriberMessage;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

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
     * @var \Twig\Environment
     */
    private $templating;

    private $blacklistedStates = [
        Pratica::STATUS_PRE_SUBMIT,
        Pratica::STATUS_REQUEST_INTEGRATION,
        Pratica::STATUS_PROCESSING,
        Pratica::STATUS_SUBMITTED_AFTER_INTEGRATION,
        Pratica::STATUS_DRAFT,
        Pratica::STATUS_CANCELLED_WAITALLEGATIOPERATORE
    ];

    public function __construct(
        \Swift_Mailer $mailer,
        TranslatorInterface $translator,
        \Twig\Environment $templating,
        ManagerRegistry $doctrine
    )
    {
        $this->mailer = $mailer;
        $this->translator = $translator;
        $this->templating = $templating;
        $this->doctrine = $doctrine;
    }

    /**
     * @param Pratica $pratica
     * @param $fromAddress
     * @param bool $resend
     * @return int
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
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

        if ($pratica->getStatus() == Pratica::STATUS_SUBMITTED) {
            $sql = "SELECT id from utente where servizi_abilitati like '%" . $pratica->getServizio()->getId() . "%'";
            $stmt = $this->doctrine->getManager()->getConnection()->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetchAll();

            $ids = [];
            foreach ($result as $id) {
                $ids[] = $id['id'];
            }

            $repo = $this->doctrine->getRepository(OperatoreUser::class);
            $operatori = $repo->findById($ids);
            if ($operatori != null && !empty($operatori)) {
                foreach ($operatori as $operatore) {
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

    private function CPSUserHasValidContactEmail(CPSUser $user)
    {
        $email = $user->getEmailContatto();

        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    private function CPSUserHasAlreadyBeenWarned(Pratica $pratica)
    {
        return $pratica->getLatestCPSCommunicationTimestamp() >= $pratica->getLatestStatusChangeTimestamp();
    }

    /**
     * @param Pratica $pratica
     * @param $fromAddress
     * @return \Swift_Message
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    private function setupCPSUserMessage(Pratica $pratica, $fromAddress)
    {
        $toEmail = $pratica->getUser()->getEmailContatto();
        $toName = $pratica->getUser()->getFullName();

        $ente = $pratica->getEnte();
        $fromName = $ente instanceof Ente ? $ente->getName() : null;

        $message = (new \Swift_Message())
            ->setSubject($this->translator->trans('pratica.email.status_change.subject'))
            ->setFrom($fromAddress, $fromName)
            ->setTo($toEmail, $toName)
            ->setBody(
                $this->templating->render(
                    'Emails/User/pratica_status_change.html.twig',
                    array(
                        'pratica' => $pratica,
                        'user_name' => $pratica->getUser()->getFullName()
                    )
                ),
                'text/html'
            )
            ->addPart(
                $this->templating->render(
                    'Emails/User/pratica_status_change.txt.twig',
                    array(
                        'pratica' => $pratica,
                        'user_name' => $pratica->getUser()->getFullName()
                    )
                ),
                'text/plain'
            );
        return $message;
    }

    /**
     * @param Pratica $pratica
     * @param $fromAddress
     * @param OperatoreUser|null $operatore
     * @return \Swift_Message
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    private function setupOperatoreUserMessage(Pratica $pratica, $fromAddress, OperatoreUser $operatore = null)
    {
        if ($operatore == null) {
            $operatore = $pratica->getOperatore();
        }

        $toEmail = $operatore->getEmail();
        $toName = $operatore->getFullName();

        $ente = $pratica->getEnte();
        $fromName = $ente instanceof Ente ? $ente->getName() : null;

        $message = (new \Swift_Message())
            ->setSubject($this->translator->trans('pratica.email.status_change.subject'))
            ->setFrom($fromAddress, $fromName)
            ->setTo($toEmail, $toName)
            ->setBody(
                $this->templating->render(
                    'Emails/Operatore/pratica_status_change.html.twig',
                    array(
                        'pratica' => $pratica,
                        'user_name' => $operatore->getFullName()
                    )
                ),
                'text/html'
            )
            ->addPart(
                $this->templating->render(
                    'Emails/Operatore/pratica_status_change.txt.twig',
                    array(
                        'pratica' => $pratica,
                        'user_name' => $operatore->getFullName()
                    )
                ),
                'text/plain'
            );
        return $message;
    }

    private function operatoreUserHasAlreadyBeenWarned(Pratica $pratica)
    {
        return $pratica->getLatestOperatoreCommunicationTimestamp() >= $pratica->getLatestStatusChangeTimestamp();
    }

    /**
     * @param $fromAddress
     * @param $fromName
     * @param $toAddress
     * @param $toName
     * @param $message
     * @param $subject
     * @param Ente $ente
     * @return int
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function dispatchMail($fromAddress, $fromName, $toAddress, $toName, $message, $subject, Ente $ente)
    {
        $sentAmount = 0;

        if ($this->isValidEmail($toAddress)) {
            $emailMessage = (new \Swift_Message())
                ->setSubject($subject)
                ->setFrom($fromAddress, $fromName)
                ->setTo($toAddress, $toName)
                ->setBody(
                    $this->templating->render(
                        'Emails/General/message.html.twig',
                        array(
                            'message' => $message,
                            'ente' => $ente
                        )
                    ),
                    'text/html'
                )
                ->addPart(
                    $this->templating->render(
                        'Emails/General/message.txt.twig',
                        array(
                            'message' => $message,
                            'ente' => $ente
                        )
                    ),
                    'text/plain'
                );
            $sentAmount += $this->mailer->send($emailMessage);
        }
        return $sentAmount;
    }

    public function isValidEmail($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    /**
     * @param SubscriberMessage $subscriberMessage
     * @param $fromAddress
     * @param UserInterface|OperatoreUser $operatore
     * @return int
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function dispatchMailForSubscriber(SubscriberMessage $subscriberMessage, $fromAddress, OperatoreUser $operatore)
    {
        $sentAmount = 0;

        if ($this->SubscriberHasValidContactEmail($subscriberMessage->getSubscriber())) {
            $message = $this->setupSubscriberMessage($subscriberMessage, $fromAddress, $operatore);
            $sentAmount += $this->mailer->send($message);
        }
        return $sentAmount;
    }

    private function SubscriberHasValidContactEmail(Subscriber $subscriber)
    {
        $email = $subscriber->getEmail();

        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    /**
     * @param SubscriberMessage $subscriberMessage
     * @param $fromAddress
     * @param OperatoreUser $operatoreUser
     * @return \Swift_Message
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    private function setupSubscriberMessage(SubscriberMessage $subscriberMessage, $fromAddress, OperatoreUser $operatoreUser)
    {
        $toEmail = $subscriberMessage->getSubscriber()->getEmail();
        $toName = $subscriberMessage->getFullName();

        $ente = $operatoreUser->getEnte();
        $fromName = $ente instanceof Ente ? $ente->getName() : null;

        $emailMessage = (new \Swift_Message())
            ->setSubject($subscriberMessage->getSubject())
            ->setFrom($fromAddress, $fromName)
            ->setTo($toEmail, $toName)
            ->setBcc($operatoreUser->getEmail(), $operatoreUser->getFullName())
            ->setBody(
                $this->templating->render(
                    'Emails/Subscriber/subscriber_message.html.twig',
                    array(
                        'message' => $subscriberMessage->getMessage(),
                    )
                ),
                'text/html'
            )
            ->addPart(
                $this->templating->render(
                    'Emails/Subscriber/subscriber_message.txt.twig',
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
}
