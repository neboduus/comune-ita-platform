<?php


namespace AppBundle\Controller;


use AppBundle\Entity\CPSUser;
use AppBundle\Entity\User;
use AppBundle\Form\Base\MessageType;
use Psr\Http\Message\ResponseInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class MessagesController
 * @Route()
 */
class MessagesController extends Controller
{
    /**
     * @param Request $request
     * @param string  $threadId
     * @Route(name="messages_controller_enqueue_for_user", path="/user/messages/{threadId}")
     * @Route(name="messages_controller_enqueue_for_operatore", path="/operatori/messages/{threadId}")
     * @Method({"PUT"})
     * @return Response
     */
    public function postMessageAction(Request $request, $threadId)
    {
        $payload = $request->get('message');

        if ($payload != null && $this->performChecks($payload, $threadId, $this->getUser())) {
            $messagesAdapterService = $this->get('ocsdc.messages_adapter');
            $postedMessage = $messagesAdapterService->postMessageToThread(
                $this->getUser(),
                $payload['message'],
                $payload['thread_id']
            );

            $this->decorateMessages($postedMessage, $this->getUser());
            return JsonResponse::create($postedMessage);
        }

        return Response::create(null, Response::HTTP_BAD_REQUEST);
    }

    /**
     * @param Request $request
     * @Route(name="message_controller_get_threads_for_user", path="/user/threads")
     * @Route(name="message_controller_get_threads_for_operatore", path="/operatori/threads")
     * @Method({"GET"})
     */
    public function getThreadsAction(Request $request)
    {
        $messagesAdapterService = $this->get('ocsdc.messages_adapter');

        return JsonResponse::create($messagesAdapterService->getThreadsForUser($this->getUser()));
    }


    /**
     * @Route(name="messages_controller_get_messages_for_thread_and_user", path="/user/messages/{threadId}")
     * @Route(name="messages_controller_get_messages_for_thread_and_operatore", path="/operatori/messages/{threadId}")
     * @Method({"GET"})
     */
    public function getMessagesForThreadAction(Request $request, $threadId)
    {
        $user = $this->getUser();
        $payload = ['thread_id' => $threadId];
        if ($this->performChecks($payload, $threadId, $user, false)) {
            $messagesAdapterService = $this->get('ocsdc.messages_adapter');
            $undecoratedResponse = $messagesAdapterService->getMessagesForThread($threadId);
            $decoratedResponse = $this->decorateMessages($undecoratedResponse, $user);

            return JsonResponse::create($decoratedResponse);
        }

        return Response::create(null, Response::HTTP_NOT_FOUND);
    }

    private function performChecks($payload, $threadId, User $user, $checkSender = true)
    {
        if ($threadId !== $payload['thread_id']) {
            return false;
        }
        if (strpos($threadId, $user->getId()) < 0) {
            return false;
        }
        if ($checkSender && $user->getId() !== $payload['sender_id']) {
            return false;
        }

        return true;
    }

    private function decorateMessages($undecoratedResponse, User $user)
    {
        foreach ($undecoratedResponse as &$message) {
            $message->formattedDate = strftime("%e %b %Y %H:%M", $message->timestamp);
            $message->isMine = false;
            if ($message->senderId == $user->getId()) {
                $message->isMine = true;
            }
        }

        return $undecoratedResponse;
    }
}
