<?php


namespace App\Controller;


use App\Entity\User;
use App\Services\MessagesAdapterService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class MessagesController
 * @Route()
 */
class MessagesController extends Controller
{

  /** @var MessagesAdapterService */
  private $messagesAdapterService;

  /**
   * MessagesController constructor.
   */
  public function __construct(MessagesAdapterService $messagesAdapterService)
  {
    $this->messagesAdapterService = $messagesAdapterService;
  }


  /**
   * @param Request $request
   * @param string $threadId
   * @Route(name="messages_controller_enqueue_for_user", path="/user/messages/{threadId}")
   * @Route(name="messages_controller_enqueue_for_operatore", path="/operatori/messages/{threadId}")
   * @Method({"PUT"})
   * @return Response
   */
  public function postMessageAction(Request $request, $threadId)
  {
    $payload = $request->get('message');

    if ($payload != null && $this->performChecks($payload, $threadId, $this->getUser())) {
      $postedMessage = $this->messagesAdapterService->postMessageToThread(
        $this->getUser(),
        $payload['message'],
        $payload['thread_id']
      );

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
    $return = $this->messagesAdapterService->getDecoratedThreadsForUser($this->getUser());

    return JsonResponse::create($return);
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
      $response = $this->messagesAdapterService->getDecoratedMessagesForThread($threadId, $user);

      return JsonResponse::create($response);
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
}
