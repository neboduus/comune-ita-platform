<?php

namespace Tests\Helpers;

use App\Entity\Categoria;
use App\Entity\Ente;
use App\Entity\Erogatore;
use App\Entity\ServiceGroup;
use App\Entity\Servizio;

class EntitiesHelper
{

  public static function createEnte(): Ente
  {
    // Ente
    $ente = new Ente();
    $ente->setName('Comune di Bugliano');
    return $ente;

  }

  /**
   * @param Ente $ente
   * @return Erogatore
   */
  public static function createErogatore(Ente $ente): Erogatore
  {
    $erogatore = new Erogatore();
    $erogatore->setName('Erogatore di' . $ente->getName());
    $erogatore->addEnte($ente);

    return $erogatore;
  }

  /**
   * @return Categoria
   */
  public static function createCategoria(): Categoria
  {
    $category = new Categoria();
    $category->setName('Category' . uniqid());
    return $category;
  }

  /**
   * @param Ente $ente
   * @param Erogatore $erogatore
   * @return Servizio
   */
  public static function createFormIOService(Ente $ente, Erogatore $erogatore, Categoria $categoria): Servizio
  {

    $servizio = new Servizio();

    $servizio->setName('Servizio di test');
    $servizio->setPraticaFCQN('\App\Entity\FormIO');
    $servizio->setPraticaFlowServiceName('ocsdc.form.flow.formio');
    $servizio->setEnte($ente);
    $servizio->setTopics($categoria);
    $servizio->setProtocolRequired(false);

    $servizio->setDescription('Service lorem ipsum');
    $servizio->setShortDescription('Service lorem ipsum');
    $servizio->setHowto('Service lorem ipsum');
    $servizio->setWho('Service lorem ipsum');
    $servizio->setSpecialCases('Service lorem ipsum');
    $servizio->setMoreInfo('Service lorem ipsum');
    $servizio->setCompilationInfo('Service lorem ipsum');
    $servizio->setFinalIndications('Service lorem ipsum');
    $servizio->setCoverage(['a','b','c']);

    // Erogatore
    $servizio->activateForErogatore($erogatore);

    return $servizio;
  }


  public static function createServiceGroup(): ServiceGroup
  {

    $serviceGroup = new ServiceGroup();
    $serviceGroup->setName('Service Group');
    $serviceGroup->setTopics(self::createCategoria());

    $serviceGroup->setDescription('Service Group lorem ipsum');
    $serviceGroup->setHowto('Service Group lorem ipsum');
    $serviceGroup->setWho('Service Group lorem ipsum');
    $serviceGroup->setSpecialCases('Service Group lorem ipsum');
    $serviceGroup->setMoreInfo('Service Group lorem ipsum');
    $serviceGroup->setCoverage(['c','b','f']);

    return $serviceGroup;
  }

}
