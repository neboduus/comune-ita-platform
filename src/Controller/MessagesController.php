<?php

namespace App\Controller;

use App\Entity\User;
use App\Multitenancy\TenantAwareController;
use App\Services\MessagesAdapterService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Multitenancy\Annotations\MustHaveTenant;

/**
 * Class MessagesController
 * @Route()
 * @MustHaveTenant()
 */
class MessagesController extends TenantAwareController
{
    private $messagesAdapterService;

    public function __construct(MessagesAdapterService $messagesAdapterService)
    {
        $this->messagesAdapterService = $messagesAdapterService;
    }

    /**
     * @Route(name="messages_controller_enqueue_for_user", path="/user/messages/{threadId}", methods={"PUT"})
     * @Route(name="messages_controller_enqueue_for_operatore", path="/operatori/messages/{threadId}", methods={"PUT"})
     * @param Request $request
     * @param string $threadId
     * @return Response
     */
    public function postMessage(Request $request, $threadId)
    {
        $payload = $request->get('message');
        /** @var User $user */
        $user = $this->getUser();
        if ($payload != null && $this->performChecks($payload, $threadId, $user)) {
            $postedMessage = $this->messagesAdapterService->postMessageToThread(
                $user,
                $payload['message'],
                $payload['thread_id']
            );

            return JsonResponse::create($postedMessage);
        }

        return Response::create(null, Response::HTTP_BAD_REQUEST);
    }

    /**
     * @Route(name="message_controller_get_threads_for_user", path="/user/threads", methods={"GET"})
     * @Route(name="message_controller_get_threads_for_operatore", path="/operatori/threads", methods={"GET"})
     * @return JsonResponse
     */
    public function getThreads()
    {
        /** @var User $user */
        $user = $this->getUser();
        $return = $this->messagesAdapterService->getDecoratedThreadsForUser($user);

        return JsonResponse::create($return);
    }


    /**
     * @Route(name="messages_controller_get_messages_for_thread_and_user", path="/user/messages/{threadId}", methods={"GET"})
     * @Route(name="messages_controller_get_messages_for_thread_and_operatore", path="/operatori/messages/{threadId}", methods={"GET"})
     * @param $threadId
     * @return JsonResponse|Response
     */
    public function getMessagesForThread($threadId)
    {
        /** @var User $user */
        $user = $this->getUser();
        $payload = ['thread_id' => $threadId];
        if ($this->performChecks($payload, $threadId, $user, false)) {
            $response = $this->messagesAdapterService->getDecoratedMessagesForThread($threadId, $user);

            return JsonResponse::create($response);
        }

        return Response::create(null, Response::HTTP_NOT_FOUND);
    }

    /**
     * @param array $payload
     * @param string $threadId
     * @param User $user
     * @param bool $checkSender
     * @return bool
     */
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
}
