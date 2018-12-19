<?php


namespace AppBundle\Services;


use AppBundle\Entity\Pratica;
use AppBundle\Payment\Gateway\MyPay;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\RouterInterface;

class MyPayService
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * MyPayService constructor.
     * @param Client $client
     */
    public function __construct(Client $client, RouterInterface $router, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->router = $router;
        $this->logger = $logger;
    }

    public function getSanitizedPaymentData(Pratica $pratica) :array
    {
        $data = $pratica->getPaymentData();
        if(!isset($data[MyPay::PAYMENT_ATTEMPTS])) {
            $data[MyPay::PAYMENT_ATTEMPTS] = [];
        }
        if(!isset($data[MyPay::IMPORTO])) {
            $data[MyPay::IMPORTO] = $this->calculateImporto($pratica);
        }
        if(!isset($data[MyPay::OVERALL_OUTCOME])) {
            $data[MyPay::OVERALL_OUTCOME] = MyPay::ESITO_UNSET;
        }
        if(!isset($data[MyPay::LATEST_ATTEMPT_ID])) {
            $data[MyPay::LATEST_ATTEMPT_ID] = null;
        }

        return $data;
    }

    /**
     * @param Pratica $pratica
     * @return string
     */
    public function checkPaymentForPratica(Pratica $pratica): array
    {
        $data = $pratica->getPaymentData();

        if($data[MyPay::OVERALL_OUTCOME] === MyPay::ESITO_ESEGUITO) {
            return $data[MyPay::PAYMENT_ATTEMPTS][$data[MyPay::LATEST_ATTEMPT_ID]][MyPay::OUTCOME_RESPONSE];
        }

        $lastAttemptId = $this->getLastAttemptId($data);

        if(!$lastAttemptId) {
            return [];
        }

        $requestBody = $this->createChiediPagatiRequestBody($pratica, $lastAttemptId);

        $body = $this->client->post('/chiediPagati',['body' => json_encode($requestBody)])->getBody()->getContents();
        $decoded = json_decode($body, true);

        unset($requestBody['password']);
        unset($requestBody['codIpaEnte']);

        $data[MyPay::PAYMENT_ATTEMPTS][$lastAttemptId][MyPay::OUTCOME_REQUEST] = $requestBody;
        $data[MyPay::PAYMENT_ATTEMPTS][$lastAttemptId][MyPay::OUTCOME_RESPONSE] = $decoded;
        $data[MyPay::OVERALL_OUTCOME] = $this->checkLastPaymentOutcome($data);
        $data[MyPay::LATEST_ATTEMPT_ID] = $lastAttemptId;
        $pratica->setPaymentData($data);
        return $decoded;
    }

    /**
     * @param Pratica $pratica
     * @return array
     * @throws \Exception
     */
    public function createPaymentRequestForPratica(Pratica $pratica): array
    {
        $lastPending = $this->checkPendingPayment($pratica);
        $data = $pratica->getPaymentData();

        if($data[MyPay::OVERALL_OUTCOME] === 0) {
            return [];
        }

        if($lastPending) {
            return $data[MyPay::PAYMENT_ATTEMPTS][$lastPending][MyPay::START_RESPONSE];
        }

        $requestBody = $this->createInviaDovutiRequestBody($pratica);

        $body = $this->client->post('/inviaDovuti',['body' => json_encode($requestBody)])->getBody()->getContents();
        $decoded = json_decode($body, true);

        unset($requestBody['password']);
        unset($requestBody['codIpaEnte']);

        if($decoded['status'] === 'KO') {
            $this->logger->error('MyPay wrapper error response when creating a payment Request', ['request' => $requestBody, 'response' => $decoded]);
            throw new \Exception("Unable to create a payment request.");
        }

        $data[MyPay::PAYMENT_ATTEMPTS][$requestBody['identificativoUnivocoDovuto']][MyPay::START_REQUEST] = $requestBody;
        $data[MyPay::PAYMENT_ATTEMPTS][$requestBody['identificativoUnivocoDovuto']][MyPay::START_RESPONSE] = $decoded;
        $data[MyPay::PAYMENT_ATTEMPTS][$requestBody['identificativoUnivocoDovuto']][MyPay::OUTCOME_REQUEST] = null;
        $data[MyPay::PAYMENT_ATTEMPTS][$requestBody['identificativoUnivocoDovuto']][MyPay::OUTCOME_RESPONSE] = null;
        $data[MyPay::OVERALL_OUTCOME] = MyPay::ESITO_PENDING;

        $pratica->setPaymentData($data);
        return $decoded;
    }

    /**
     * @param Pratica $pratica
     * @return mixed
     */
    public function getUrlForCurrentPayment(Pratica $pratica)
    {
        $lastAttemptId = $this->checkPendingPayment($pratica);
        $data = $pratica->getPaymentData()[MyPay::PAYMENT_ATTEMPTS][$lastAttemptId];
        return $data[MyPay::START_RESPONSE]['json']['url'];
    }

    /**
     * @param Pratica $pratica
     * @return string
     */
    public function renderResumeUrlForPaymentCheck(Pratica $pratica): string
    {
        $currentPraticaResumeEditUrl = $this->router->generate('pratiche_compila', [
            'pratica' => $pratica->getId(),
            'instance' => $pratica->getInstanceId(),
            'step' => $pratica->getLastCompiledStep() + 1
        ], RouterInterface::ABSOLUTE_URL);
        return $currentPraticaResumeEditUrl;
    }

    /**
     * @param Pratica $pratica
     * @return array
     */
    private function createInviaDovutiRequestBody(Pratica $pratica)
    {
        $data = $pratica->getPaymentData();

        if(!array_key_exists(MyPay::IMPORTO, $data)) {
            throw new \InvalidArgumentException('Missing '.MyPay::IMPORTO.' key');
        }
        if(!array_key_exists(MyPay::PAYMENT_ATTEMPTS, $data)) {
            throw new \InvalidArgumentException('Missing '.MyPay::PAYMENT_ATTEMPTS.' key');
        }

        $currentPraticaResumeEditUrl = $this->renderResumeUrlForPaymentCheck($pratica);
        $currentPraticaResumeEditUrl = str_replace('&', '&amp;', $currentPraticaResumeEditUrl);

        return [
            "enteSILInviaRispostaPagamentoUrl" => $currentPraticaResumeEditUrl,
            "importoSingoloVersamento" => intval($data['importo'],10)/100,
            "identificativoUnivocoDovuto" => $this->calculateIUDFromPratica($pratica),
            "tipoIdentificativoUnivoco" => "F", //persona fisica
            "codiceIdentificativoUnivoco" => $pratica->getRichiedenteCodiceFiscale() ?? 'LBRMRC73A04L781X',
            "anagraficaPagatore" => $pratica->getRichiedenteNome().' '.$pratica->getRichiedenteCognome(),
            "causaleVersamento" => "SDC - ".$pratica->getServizio()->getName(), //coming from the servizio class
            "datiSpecificiRiscossione" => $pratica->getServizio()->getPaymentParameters()['datiSpecificiRiscossione'], //coming from the servizio class
            "codIpaEnte" => $pratica->getServizio()->getPaymentParameters()['codIpaEnte'], //coming from config
            "password" => $pratica->getServizio()->getPaymentParameters()['password'] //comiing from config
        ];
    }

    /**
     * @param Pratica $pratica
     * @return array
     */
    private function createChiediPagatiRequestBody(Pratica $pratica, $lastAttemptId)
    {
        $lastAttempt = $pratica->getPaymentData()[MyPay::PAYMENT_ATTEMPTS][$lastAttemptId];

        $idSession = $lastAttempt[MyPay::START_RESPONSE]['json']['idSession'];

        return [
            "codIpaEnte" => $pratica->getServizio()->getPaymentParameters()['codIpaEnte'],
            "password" => $pratica->getServizio()->getPaymentParameters()['password'],
            "idSession" => $idSession
        ];
    }

    private function calculateIUDFromPratica(Pratica $pratica)
    {
        $data = $pratica->getPaymentData();

        $i = 1;
        while (true) {
            $IUD = str_replace('-', '', $pratica->getID()) . str_pad($i,3,0, STR_PAD_LEFT);
            if (!array_key_exists($IUD, $data[MyPay::PAYMENT_ATTEMPTS])) {
                return $IUD;
            }
            $i++;
        }
    }

    private function checkLastPaymentOutcome($data): int {
        $lastAttemptId = $this->getLastAttemptId($data);
        $lastAttempt = $data[MyPay::PAYMENT_ATTEMPTS][$lastAttemptId];

        if(!isset($lastAttempt[MyPay::OUTCOME_RESPONSE]) || $lastAttempt[MyPay::OUTCOME_RESPONSE] === null) {
            return MyPay::ESITO_PENDING;
        }

        if($lastAttempt[MyPay::OUTCOME_RESPONSE]['status'] === 'OK') {
            /** No fault code, returning codiceEsitoPagamento  */
            preg_match("/<codiceEsitoPagamento>(\d)<\/codiceEsitoPagamento>/",$lastAttempt[MyPay::OUTCOME_RESPONSE]['json']['pagati'], $matches);
            if(count($matches) < 2) {
                throw new \InvalidArgumentException('Tag codiceEsitoPagamento not found IUD: '.$lastAttemptId);
            }
            return $matches[1];
        }


        if($lastAttempt[MyPay::OUTCOME_RESPONSE]['status'] === 'KO') {
            if (isset($lastAttempt[MyPay::OUTCOME_RESPONSE]['json']['faultCode'])) {
                if(
                    $lastAttempt[MyPay::OUTCOME_RESPONSE]['json']['faultCode'] === MyPay::PAA_PAGAMENTO_IN_CORSO ||
                    $lastAttempt[MyPay::OUTCOME_RESPONSE]['json']['faultCode'] === MyPay::PAA_PAGAMENTO_NON_INIZIATO
                ) {

                    return MyPay::ESITO_PENDING;
                }
            }
        }

        return MyPay::ESITO_NON_ESEGUITO;
    }

    private function checkPendingPayment(Pratica $pratica) : ?string
    {
        $data = $pratica->getPaymentData();
        $lastAttemptId = $this->getLastAttemptId($data);

        if(!$lastAttemptId) {
            return null;
        }

        $lastAttempt = $data[MyPay::PAYMENT_ATTEMPTS][$lastAttemptId];

        if(!isset($lastAttempt[MyPay::OUTCOME_RESPONSE]) || $lastAttempt[MyPay::OUTCOME_RESPONSE] == null) {
            /**
             * Still haven't checked, it's surely pending
             */
            return $lastAttemptId;
        }

        /**
         * We checked. Possible outcomes are
         * status OK
         *   -> payment is not pending and went well
         * status KO
         *   -> payment may be pending (the two specific error codes) <- we only care about this one in this context
         *   -> payment may be completed unsuccesfully
         */
        if($lastAttempt[MyPay::OUTCOME_RESPONSE]['status'] === 'KO') {
            if (isset($lastAttempt[MyPay::OUTCOME_RESPONSE]['json']['faultCode'])) {
                if(
                    $lastAttempt[MyPay::OUTCOME_RESPONSE]['json']['faultCode'] === MyPay::PAA_PAGAMENTO_IN_CORSO ||
                    $lastAttempt[MyPay::OUTCOME_RESPONSE]['json']['faultCode'] === MyPay::PAA_PAGAMENTO_NON_INIZIATO
                ) {

                    return $lastAttemptId;
                }
            }
        }

        return null;
    }

    /**
     * @param Pratica $pratica
     * @return string|null
     */
    private function getLastAttemptId($data): ?string
    {
        ksort($data[MyPay::PAYMENT_ATTEMPTS]);
        end($data[MyPay::PAYMENT_ATTEMPTS]);
        $lastAttemptId = key($data[MyPay::PAYMENT_ATTEMPTS]);
        return $lastAttemptId;
    }

    /**
     * @param Pratica $pratica
     * @return int
     */
    private function calculateImporto(Pratica $pratica): int
    {
        return $pratica->getServizio()->getPaymentParameters()['importo'] ?? 1;
    }

}
