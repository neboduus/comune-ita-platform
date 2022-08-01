<?php

namespace Tests\Entity;

use App\Entity\CPSUser;
use PHPUnit\Framework\TestCase;

/** @covers \App\Entity\CPSUser */
class CPSUserTest extends TestCase
{
  public function testCPSUserCreate()
  {

    $user = new CPSUser();
    $user->setNome('Mario');
    $user->setCognome('Rossi');
    $user->setEmailContatto('mario@rossi.it');
    $user->setCellulareContatto('333123456');
    $user->setCodiceFiscale('RSSMRA80A01H501U');

    $user->setDataNascita(new \DateTime('1980-01-01'));
    $user->setLuogoNascita('Roma');
    $user->setCodiceNascita('00100');
    $user->setProvinciaNascita('RM');
    $user->setStatoNascita('Italia');
    $user->setSesso('M');

    $user->setX509certificateIssuerdn('X509certificateIssuerdn');
    $user->setX509certificateSubjectdn('X509certificateSubjectdn');
    $user->setX509certificateBase64('X509certificateBase64');
    $user->setSpidCode('SpidCode');
    $user->setShibSessionId('ShibSessionId');
    $user->setShibSessionIndex('ShibSessionIndex');
    $user->setShibAuthenticationIstant('ShibAuthenticationIstant');

    $user->setCpsTelefono('06123456');
    $user->setCpsCellulare('333123456');
    $user->setCpsEmail('mario@rossi.it');
    $user->setCpsEmailPersonale('mario@rossi.it');
    $user->setCpsTitolo('Dott');

    $user->setCpsIndirizzoDomicilio('Via roma');
    $user->setCpsCapDomicilio('00100');
    $user->setCpsCittaDomicilio('Roma');
    $user->setCpsProvinciaDomicilio('RM');
    $user->setCpsStatoDomicilio('Italia');

    $user->setCpsIndirizzoResidenza('Via roma');
    $user->setCpsCapResidenza('00100');
    $user->setCpsCittaResidenza('Roma');
    $user->setCpsProvinciaResidenza('RM');
    $user->setCpsStatoResidenza('Italia');

    $user->setSdcIndirizzoDomicilio('Via roma');
    $user->setSdcCapDomicilio('00100');
    $user->setSdcCittaDomicilio('Roma');
    $user->setSdcProvinciaDomicilio('RM');
    $user->setSdcStatoDomicilio('Italia');

    $user->setSdcIndirizzoResidenza('Via roma');
    $user->setSdcCapResidenza('00100');
    $user->setSdcCittaResidenza('Roma');
    $user->setSdcProvinciaResidenza('RM');
    $user->setSdcStatoResidenza('Italia');

    //$user->setIdCard(json_encode(['card']));

    $this->assertEquals(CPSUser::USER_TYPE_CPS, $user->getType());
    $this->assertEquals('Mario', $user->getNome());
    $this->assertEquals('Rossi', $user->getCognome());
    $this->assertEquals('Mario Rossi', $user->getFullName());
    $this->assertEquals('mario@rossi.it', $user->getEmailContatto());
    $this->assertEquals('333123456', $user->getCellulareContatto());
    $this->assertEquals('RSSMRA80A01H501U', $user->getCodiceFiscale());

    $this->assertEquals('1980-01-01', $user->getDataNascita()->format('Y-m-d'));
    $this->assertEquals('Roma', $user->getLuogoNascita());
    $this->assertEquals('00100', $user->getCodiceNascita());
    $this->assertEquals('RM', $user->getProvinciaNascita());
    $this->assertEquals('Italia', $user->getStatoNascita());
    $this->assertEquals('M', $user->getSesso());

    $this->assertEquals('X509certificateIssuerdn', $user->getX509certificateIssuerdn());
    $this->assertEquals('X509certificateSubjectdn', $user->getX509certificateSubjectdn());
    $this->assertEquals('X509certificateBase64', $user->getX509certificateBase64());
    $this->assertEquals('SpidCode', $user->getSpidCode());
    $this->assertEquals('ShibSessionId', $user->getShibSessionId());
    $this->assertEquals('ShibSessionIndex', $user->getShibSessionIndex());
    $this->assertEquals('ShibAuthenticationIstant', $user->getShibAuthenticationIstant());

    $this->assertEquals('06123456', $user->getCpsTelefono());
    $this->assertEquals('333123456', $user->getCpsCellulare());
    $this->assertEquals('mario@rossi.it', $user->getCpsEmail());
    $this->assertEquals('mario@rossi.it', $user->getCpsEmailPersonale());
    $this->assertEquals('Dott', $user->getCpsTitolo());

    $this->assertEquals('Via roma', $user->getCpsIndirizzoDomicilio());
    $this->assertEquals('00100', $user->getCpsCapDomicilio());
    $this->assertEquals('Roma', $user->getCpsCittaDomicilio());
    $this->assertEquals('RM', $user->getCpsProvinciaDomicilio());
    $this->assertEquals('Italia', $user->getCpsStatoDomicilio());

    $this->assertEquals('Via roma', $user->getCpsIndirizzoResidenza());
    $this->assertEquals('00100', $user->getCpsCapResidenza());
    $this->assertEquals('Roma', $user->getCpsCittaResidenza());
    $this->assertEquals('RM', $user->getCpsProvinciaResidenza());
    $this->assertEquals('Italia', $user->getCpsStatoResidenza());

    $this->assertEquals('Via roma', $user->getSdcIndirizzoDomicilio());
    $this->assertEquals('00100', $user->getSdcCapDomicilio());
    $this->assertEquals('Roma', $user->getSdcCittaDomicilio());
    $this->assertEquals('RM', $user->getSdcProvinciaDomicilio());
    $this->assertEquals('Italia', $user->getSdcStatoDomicilio());

    $this->assertEquals('Via roma', $user->getSdcIndirizzoResidenza());
    $this->assertEquals('00100', $user->getSdcCapResidenza());
    $this->assertEquals('Roma', $user->getSdcCittaResidenza());
    $this->assertEquals('RM', $user->getSdcProvinciaResidenza());
    $this->assertEquals('Italia', $user->getSdcStatoResidenza());

    //$this->assertEquals(json_encode(['card']), $user->getIdCard());

  }
}
