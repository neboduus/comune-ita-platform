<?php


namespace App\Tests\Services;


use App\Entity\SciaPraticaEdilizia;
use App\Model\Gateway;
use App\Payment\Gateway\MyPay;
use App\Payment\Gateway\MyPayDTO;
use App\Services\MyPayService;
use GuzzleHttp\Psr7\Response;
use App\Tests\Base\AbstractAppTestCase;

class MyPayServiceTest extends AbstractAppTestCase
{


  public function testServiceExists()
  {
    $this->assertInstanceOf(MyPayService::class, static::$container->get(MyPayService::class));
  }

  public function testServiceCanSetupPaymentData()
  {
    $ente = $this->createEnti()[0];
    $erogatore = $this->createErogatoreWithEnti([$ente]);
    $fqcn = SciaPraticaEdilizia::class;
    $flow = 'ocsdc.form.flow.scia_pratica_edilizia';
    $servizio = $this->createServizioWithErogatore($erogatore, 'Scia', $fqcn, $flow);

    $servizio->setPaymentParameters($this->getPaymentParameters())->setPaymentRequired(true);

    $pratica = $this->createPratica($this->createCPSUser(), null, null, $erogatore, $servizio);

    $sut = static::$container->get(MyPayService::class);

    $data = $sut->getSanitizedPaymentData($pratica);
    $pratica->setPaymentData($data);

    /*$this->assertArrayHasKey(MyPay::PAYMENT_ATTEMPTS, $data);
    $this->assertArrayHasKey(MyPay::LATEST_ATTEMPT_ID, $data);
    $this->assertArrayHasKey(MyPay::OVERALL_OUTCOME, $data);*/
    $this->assertArrayHasKey('payment_amount', $data);
  }

  public function testThrowsIfNotAbleToCreatePaymentRequest()
  {
    $this->expectException(\Exception::class);

    $guzzleMock = $this->getMockGuzzleClient(
      [
        new Response(200, [], self::CREATE_PAYMENT_DATA_KO_RESPONSE),
      ]
    );
    $sut = new MyPayService($guzzleMock, static::$container->get('router'), static::$container->get('logger'));

    $ente = $this->createEnti()[0];
    $erogatore = $this->createErogatoreWithEnti([$ente]);
    $fqcn = SciaPraticaEdilizia::class;
    $flow = 'ocsdc.form.flow.scia_pratica_edilizia';
    $servizio = $this->createServizioWithErogatore($erogatore, 'Scia', $fqcn, $flow);

    $servizio->setPaymentParameters($this->getPaymentParameters())->setPaymentRequired(true);

    $pratica = $this->createPratica($this->createCPSUser(), null, null, $erogatore, $servizio);
    $pratica->setPaymentData($sut->getSanitizedPaymentData($pratica));

    $sut->createPaymentRequestForPratica($pratica);
  }

  private function getPaymentParameters()
  {
    $identifier = 'mypay';
    $parameters = [
      "datiSpecificiRiscossione" => "9/3300.1",
      "codIpaEnte" => "L_M781",
      "password" => "ABCDE",
      'identificativoTipoDovuto' => 'TIPO_DOVUTO',
    ];
    $gateway = new Gateway();
    $gateway->setIdentifier($identifier);
    $gateway->setParameters($parameters);
    $gateways[$identifier] = $gateway->jsonSerialize();

    $paymentParameters['total_amounts'] = '1.00';
    $paymentParameters['gateways'] = $gateways;

    return $paymentParameters;
  }

  const CREATE_PAYMENT_DATA_OK_RESPONSE = <<<HERE
{"status":"OK","remoteResponse":"<soap:Envelope xmlns:soap=\"http://schemas.xmlsoap.org/soap/envelope/\"><soap:Body><ns4:paaSILInviaDovutiRisposta xmlns:ns4=\"http://www.regione.veneto.it/pagamenti/ente/\" xmlns:ns3=\"http://www.regione.veneto.it/schemas/2012/Pagamenti/Ente/\" xmlns:ns2=\"http://www.regione.veneto.it/pagamenti/ente/ppthead\"><esito>OK</esito><idSession>f61f7d3d-9efc-4662-8ad6-f2e0f941367f</idSession><redirect>1</redirect><url>https://mpy-qual.infotn.it/pa/public/carrello/paaSILInviaRichiestaPagamento.html?idSession=f61f7d3d-9efc-4662-8ad6-f2e0f941367f</url></ns4:paaSILInviaDovutiRisposta></soap:Body></soap:Envelope>","json":{"esito":"OK","idSession":"f61f7d3d-9efc-4662-8ad6-f2e0f941367f","redirect":"1","url":"https://mpy-qual.infotn.it/pa/public/carrello/paaSILInviaRichiestaPagamento.html?idSession=f61f7d3d-9efc-4662-8ad6-f2e0f941367f"},"timing":{"start":[3205037,232410138],"end":[3205037,432887134],"timeTaken":0.2004769961349666}}
HERE;

  const CREATE_PAYMENT_DATA_KO_RESPONSE = '{"status":"KO","remoteResponse":"<soap:Envelope xmlns:soap=\"http://schemas.xmlsoap.org/soap/envelope/\"><soap:Body><ns4:paaSILInviaDovutiRisposta xmlns:ns4=\"http://www.regione.veneto.it/pagamenti/ente/\" xmlns:ns3=\"http://www.regione.veneto.it/schemas/2012/Pagamenti/Ente/\" xmlns:ns2=\"http://www.regione.veneto.it/pagamenti/ente/ppthead\"><fault><faultCode>PAA_CODICE_FISCALE_NON_VALIDO</faultCode><faultString>Codice fiscale non valido [RLDLCU77T05G224]</faultString><id>C_H612</id></fault><esito>KO</esito></ns4:paaSILInviaDovutiRisposta></soap:Body></soap:Envelope>","json":{"faultCode":"PAA_CODICE_FISCALE_NON_VALIDO","faultString":"Codice fiscale non valido [RLDLCU77T05G224]","id":"C_H612"},"timing":{"start":[3317122,742428236],"end":[3317123,435541000],"timeTaken":0.6931127640418708}}';
}
