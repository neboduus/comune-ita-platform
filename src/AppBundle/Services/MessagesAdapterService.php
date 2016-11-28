<?php
namespace AppBundle\Services;

use AppBundle\Entity\CPSUser;
use AppBundle\Entity\Ente;
use AppBundle\Entity\Servizio;
use AppBundle\Entity\User;
use FOS\UserBundle\Model\UserInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;

/**
 * Class MessagesAdapterService
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
     * @param Client          $client
     * @param LoggerInterface $logger
     */
    public function __construct(Client $client, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->logger = $logger;
    }

    /**
     * @param User $user
     * @return mixed|\Psr\Http\Message\ResponseInterface
     */
    public function getThreadsForUser(User $user)
    {
        try {
            $response = (string) $this->client->get('/user/'.$user->getId().'/threads')->getBody();

            return \GuzzleHttp\json_decode($response);
        } catch (RequestException $e) {
            $this->logger->error(self::REMOTE_ENDPOINT_UNAVAILABLE_EXCEPTION_MESSAGE);

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
        $message = [
            'senderId' => $sender->getId(),
            'content' => $content,
            'threadId' => $threadId,
        ];
        try {
        return \GuzzleHttp\json_decode((string) $this->client->put('/thread/'.$threadId, ['json' => $message])->getBody());
        } catch (RequestException $e) {
            $this->logger->error(self::REMOTE_ENDPOINT_UNAVAILABLE_EXCEPTION_MESSAGE);

            return null;
        }
    }

    /**
     * @param string $threadId
     * @return array
     */
    public function getMessagesForThread($threadId)
    {
        try {
        return \GuzzleHttp\json_decode((string) $this->client->get('/thread/'.$threadId)->getBody());
        } catch (RequestException $e) {
            $this->logger->error(self::REMOTE_ENDPOINT_UNAVAILABLE_EXCEPTION_MESSAGE);

            return null;
        }
    }

    /**
     * @param CPSUser  $user
     * @param Ente     $ente
     * @param Servizio $servizio
     * @return mixed
     */
    public function getThreadsForUserEnteAndService(CPSUser $user, Ente $ente, Servizio $servizio)
    {
        //find operatore
        $operatore = $this->getOperatoreForEnteAndServizio($ente, $servizio);
        if (!$operatore) {
            return null;
        }
        $threadId = $user->getId().'~'.$operatore->getId();
        try {
            $response = \GuzzleHttp\json_decode((string)$this->client->get('/thread/'.$threadId)->getBody());
            if (count($response) > 0 && $this->checkThreadIdIsCorrect($response[0])) {
                return $response;
            }
        } catch (RequestException $e) {
            $this->logger->error(self::REMOTE_ENDPOINT_UNAVAILABLE_EXCEPTION_MESSAGE);

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
        //find operatore
        $operatore = $this->getOperatoreForEnteAndServizio($ente, $servizio);
        $threadId = $user->getId().'~'.$operatore->getId();
        try {
            return \GuzzleHttp\json_decode((string)$this->client->put('/thread', ['json' => ['threadId' => $threadId, 'servizioId' => $servizio->getId()]])->getBody());
        } catch (RequestException $e) {
            $this->logger->error(self::REMOTE_ENDPOINT_UNAVAILABLE_EXCEPTION_MESSAGE);

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
}
