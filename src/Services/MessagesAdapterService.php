<?php
namespace App\Services;

use App\Entity\CPSUser;
use App\Entity\Ente;
use App\Entity\OperatoreUser;
use App\Entity\Servizio;
use App\Entity\User;
use FOS\UserBundle\Model\UserInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * Class MessagesAdapterService
 * @property RegistryInterface doctrine
 * @property boolean
 */
class MessagesAdapterService
{
    const REMOTE_ENDPOINT_UNAVAILABLE_EXCEPTION_MESSAGE = 'Remote messages endpoint is unavailable';

    /**
     * @var Client
     */
    private $client;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * MessagesAdapterService constructor.
     * @param Client $client
     * @param LoggerInterface $logger
     * @param RegistryInterface $doctrine
     * @param $messagesEnabled
     */
    public function __construct(Client $client, LoggerInterface $logger, RegistryInterface $doctrine, $messagesEnabled)
    {
        $this->client = $client;
        $this->logger = $logger;
        $this->doctrine = $doctrine;
        $this->messagesEnabled = $messagesEnabled;
    }

    /**
     * @param User $user
     * @return mixed|\Psr\Http\Message\ResponseInterface
     */
    public function getDecoratedThreadsForUser(User $user)
    {
        if(!$this->messagesEnabled) {
            return null;
        }

        try {
            $response = \GuzzleHttp\json_decode((string) $this->client->get('/user/'.$user->getId().'/threads')->getBody());

            $repo = null;
            switch (get_class($user)) {
                case CPSUser::class :
                    $repo = $this->doctrine->getRepository('App:OperatoreUser');
                    $response = $this->decorateThreadsForUser($response, $repo);
                    break;
                case OperatoreUser::class :
                    $repo = $this->doctrine->getRepository('App:CPSUser');
                    $response = $this->decorateThreadsForOperatore($response, $repo);
                    break;
            }

            return $response;
        } catch (RequestException $e) {
            $this->logger->error(self::REMOTE_ENDPOINT_UNAVAILABLE_EXCEPTION_MESSAGE,[
                'message' => $e->getMessage(),
                'response' => $e->getResponse() ? $e->getResponse()->getBody() : null,
                'code' => $e->getCode(),
            ]);


            return null;
        }
    }

    /**
     * @param UserInterface $sender
     * @param string        $content
     * @param string        $threadId
     * @return mixed
     */
    public function postMessageToThread(UserInterface $sender, $content, $threadId)
    {
        if(!$this->messagesEnabled) {
            return null;
        }

        $message = [
            'senderId' => $sender->getId(),
            'content' => $content,
            'threadId' => $threadId,
        ];
        try {
            $response = \GuzzleHttp\json_decode((string) $this->client->put('/thread/'.$threadId, ['json' => $message])->getBody());
            return $this->decorateMessages($response, $sender);
        } catch (RequestException $e) {
            $this->logger->error(self::REMOTE_ENDPOINT_UNAVAILABLE_EXCEPTION_MESSAGE,[
                'message' => $e->getMessage(),
                'response' => $e->getResponse() ? $e->getResponse()->getBody() : null,
                'code' => $e->getCode(),
            ]);


            return null;
        }
    }

    /**
     * @param string $threadId
     * @return array
     */
    public function getDecoratedMessagesForThread($threadId, User $user)
    {
        if(!$this->messagesEnabled) {
            return null;
        }

        try {
            $response = \GuzzleHttp\json_decode((string) $this->client->get('/thread/'.$threadId)->getBody());
            return $this->decorateMessages($response, $user);
        } catch (RequestException $e) {
            $this->logger->error(self::REMOTE_ENDPOINT_UNAVAILABLE_EXCEPTION_MESSAGE,[
                'message' => $e->getMessage(),
                'response' => $e->getResponse() ? $e->getResponse()->getBody() : null,
                'code' => $e->getCode(),
            ]);


            return null;
        }
    }

    /**
     * @param CPSUser  $user
     * @param Ente     $ente
     * @param Servizio $servizio
     * @return mixed
     */
    public function getThreadsForUserEnteAndService(User $user, Ente $ente, Servizio $servizio)
    {
        if(!$this->messagesEnabled) {
            return null;
        }

        //find operatore
        $operatore = $this->getOperatoreForEnteAndServizio($ente, $servizio);
        if (!$operatore) {
            return null;
        }
        $threadId = $user->getId().'~'.$operatore->getId();
        try {
            $response = \GuzzleHttp\json_decode((string)$this->client->get('/thread/'.$threadId)->getBody());
            if (count($response) > 0 && $this->checkThreadIdIsCorrect($response[0])) {
                $repo = $this->doctrine->getRepository('App:OperatoreUser');
                $response = $this->decorateThreadsForUser($response, $repo);
                return $response;
            }
        } catch (RequestException $e) {
            $this->logger->error(self::REMOTE_ENDPOINT_UNAVAILABLE_EXCEPTION_MESSAGE,[
                'message' => $e->getMessage(),
                'custom' => $e->getRequest()->getUri(),
                'response' => $e->getResponse() ? $e->getResponse()->getBody() : null,
                'code' => $e->getCode(),
            ]);

            return null;
        }
        return $this->createThreadsForUserEnteAndService($user, $ente, $servizio);
    }

    /**
     * @param CPSUser  $user
     * @param Ente     $ente
     * @param Servizio $servizio
     * @return mixed
     */
    public function createThreadsForUserEnteAndService(CPSUser $user, Ente $ente, Servizio $servizio)
    {
        if(!$this->messagesEnabled) {
            return null;
        }

        //find operatore
        $operatore = $this->getOperatoreForEnteAndServizio($ente, $servizio);
        $threadId = $user->getId().'~'.$operatore->getId();
        try {
            $response =  \GuzzleHttp\json_decode((string)$this->client->put('/thread', ['json' => ['threadId' => $threadId, 'servizioId' => $servizio->getId()]])->getBody());
            $repo = $this->doctrine->getRepository('App:OperatoreUser');
            $response = $this->decorateThreadsForUser($response, $repo);
            return $response;
        } catch (RequestException $e) {
            $this->logger->error(self::REMOTE_ENDPOINT_UNAVAILABLE_EXCEPTION_MESSAGE,[
                'message' => $e->getMessage(),
                'response' => $e->getResponse() ? $e->getResponse()->getBody() : null,
                'code' => $e->getCode(),
            ]);

            return null;
        }
    }

    private function getOperatoreForEnteAndServizio(Ente $ente, Servizio $servizio)
    {
        $operatori = $ente->getOperatori();
        foreach ($operatori as $operatore) {
            if ($operatore->getServiziAbilitati()->contains($servizio->getId())) {
                return $operatore;
            }
        }
    }

    private function checkThreadIdIsCorrect($t)
    {
        $splitted = preg_split('/~/', $t->threadId);
        foreach ($splitted as $id) {
            if (!preg_match('/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i', $id)) {
                return false;
            };
        }

        return true;
    }

    private function decorateThreadsForUser($threads, $repo)
    {
        foreach ($threads as &$thread) {
            $operatoreId = preg_split('/~/', $thread->threadId)[1];
            $operatore = $repo->find($operatoreId);

            $thread->title = $operatore->getEnte()->getName().' ('.$operatore->getFullName().')';
        }

        return $threads;
    }

    private function decorateThreadsForOperatore($threads, $repo)
    {
        foreach ($threads as &$thread) {
            $userId = preg_split('/~/', $thread->threadId)[0];
            $cpsUser = $repo->find($userId);

            $thread->title = $cpsUser->getFullName();
        }

        return $threads;
    }

    private function decorateMessages($undecoratedResponse, User $user)
    {
        foreach ($undecoratedResponse as &$message) {
            $actualTimestamp = strlen((string) $message->timestamp) > 10 ? $message->timestamp / 1000 : $message->timestamp;
            $message->formattedDate = strftime("%e %b %Y %H:%M", $actualTimestamp);
            $message->isMine = false;
            if ($message->senderId == $user->getId()) {
                $message->isMine = true;
            }
        }
        return $undecoratedResponse;
    }

    private function decorateThreads($undecoratedResponse)
    {
        foreach ($undecoratedResponse as &$thread) {
            $thread->nomeThread = 'Servizio';
            $operatoriRepo = $this->getDoctrine()->getRepository('App:OperatoreUser');
            $operatoreId = preg_split('/~/', $thread->threadId)[1];
            $operatore = $operatoriRepo->find($operatoreId);
            if ($operatore) {
                $thread->nomeThread = $operatore->getEnte()->getName() . ' (' . $operatore->getNome() . ' ' . $operatore->getCognome() . ')';
            }
        }
        return $undecoratedResponse;
    }
}
