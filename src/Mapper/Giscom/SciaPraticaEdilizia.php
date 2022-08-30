<?php

namespace App\Mapper\Giscom;

use App\Entity\Pratica;
use App\Mapper\Giscom\SciaPraticaEdilizia\Meta;
use App\Mapper\Giscom\SciaPraticaEdilizia\ModuloCompilato;
use App\Mapper\HashableInterface;
use App\Mapper\Giscom\SciaPraticaEdilizia\ModuloDomanda;
use App\Mapper\Giscom\SciaPraticaEdilizia\ElencoAllegatiAllaDomanda;
use App\Mapper\Giscom\SciaPraticaEdilizia\ElencoAllegatiTecnici;
use App\Mapper\Giscom\SciaPraticaEdilizia\ElencoSoggettiAventiTitolo;
use App\Mapper\Giscom\SciaPraticaEdilizia\Vincoli;
use App\Mapper\Giscom\SciaPraticaEdilizia\ElencoProvvedimenti;

class SciaPraticaEdilizia implements HashableInterface
{
  const MANUTENZIONE_STRAORDINARIA = 'manutenzione_straordinaria';
  const RISTRUTTURAZIONE_EDILIZIA = 'ristrutturazione_edilizia';
  const RESTAURO_E_RISANAMENTO_CONSERVATIVO = 'restauro_e_risanamento_conservativo';
  const RISTRUTTURAZIONE_URBANISTICA = 'ristrutturazione_urbanistica';
  const ALTRO_TIPO = 'altro_tipo';
  /**
   * @var string Uuid
   */
  protected $id;

  /**
   * @var string
   */
  protected $tipo = 'SCIA';

  /**
   * @var int
   */
  protected $stato;

  /**
   * @var string
   */
  protected $cfPresentante;

  /**
   * @var string
   */
  protected $tipoIntervento;

  /**
   * @var ModuloCompilato
   */
  protected $moduloCompilato;

  /**
   * @var ModuloDomanda
   */
  protected $moduloDomanda;

  /**
   * @var ElencoAllegatiAllaDomanda
   */
  protected $elencoAllegatiAllaDomanda;

  /**
   * @var ElencoSoggettiAventiTitolo
   */
  protected $elencoSoggettiAventiTitolo;

  /**
   * @var ElencoAllegatiTecnici
   */
  protected $elencoAllegatiTecnici;

  /**
   * @var Vincoli
   */
  protected $vincoli;

  /**
   * @var ElencoProvvedimenti
   */
  protected $elencoProvvedimenti;

  /**
   * @var string
   */
  protected $numeroDiFascicolo;

  /**
   * @var string
   */
  protected $protocolloPrincipale;

  /**
   * @var []
   */
  protected $protocolliAllegati;

  /**
   * @var []
   */
  protected $meta;

  public function __construct(array $data = [])
  {
    $this->tipo = $data['tipo'] ?? Pratica::TYPE_SCIA_PRATICA_EDILIZIA;
    $this->moduloCompilato = new ModuloCompilato($data['moduloCompilato'] ?? null);
    $this->moduloDomanda = new ModuloDomanda($data['moduloDomanda'] ?? null);
    $this->elencoAllegatiAllaDomanda = new ElencoAllegatiAllaDomanda($data['elencoAllegatiAllaDomanda'] ?? null, $this->tipo);
    $this->elencoSoggettiAventiTitolo = new ElencoSoggettiAventiTitolo($data['elencoSoggettiAventiTitolo'] ?? null);
    $this->elencoAllegatiTecnici = new ElencoAllegatiTecnici($data['elencoAllegatiTecnici'] ?? null, $this->tipo);
    $this->vincoli = new Vincoli($data['vincoli'] ?? null, $this->tipo);
    $this->tipoIntervento = $data['tipoIntervento'] ?? null;
    $this->protocolliAllegati = [];
    $this->meta = [];
  }

  /**
   * @return string Uuid
   */
  public function getId()
  {
    return $this->id;
  }

  /**
   * @param string $id Uuid
   *
   * @return $this
   */
  public function setId($id)
  {
    $this->id = $id;

    return $this;
  }

  /**
   * @return string
   */
  public function getTipo()
  {
    return $this->tipo;
  }

  /**
   * @return int stato
   */
  public function getStato()
  {
    return $this->stato;
  }

  /**
   * @param string $ Uuid
   *
   * @return $this
   */
  public function setStato($stato)
  {
    $this->stato = $stato;

    return $this;
  }

  /**
   * @return string
   */
  public function getCfPresentante()
  {
    return $this->cfPresentante;
  }

  /**
   * @param string $cfPresentante
   *
   * @return $this
   */
  public function setCfPresentante($cfPresentante)
  {
    $this->cfPresentante = $cfPresentante;

    return $this;
  }

  /**
   * @return ModuloCompilato
   */
  public function getModuloCompilato()
  {
    return $this->moduloCompilato;
  }

  /**
   * @param ModuloCompilato $moduloCompilato
   */
  public function setModuloCompilato(File $moduloCompilato)
  {
    $this->moduloCompilato = $moduloCompilato;

    return $this;
  }

  /**
   * @return File
   */
  public function getModuloDomanda()
  {
    return $this->moduloDomanda;
  }

  /**
   * @param File $moduloDomanda
   *
   * @return $this
   */
  public function setModuloDomanda(File $moduloDomanda)
  {
    $this->moduloDomanda = $moduloDomanda;

    return $this;
  }

  /**
   * @return string
   */
  public function getTipoIntervento()
  {
    return $this->tipoIntervento;
  }

  /**
   * @param string $tipoIntervento
   *
   * @return $this
   */
  public function setTipoIntervento($tipoIntervento)
  {
    $this->tipoIntervento = $tipoIntervento;

    return $this;
  }

  /**
   * @return ElencoAllegatiAllaDomanda
   */
  public function getElencoAllegatiAllaDomanda()
  {
    return $this->elencoAllegatiAllaDomanda;
  }

  /**
   * @param string $name
   * @param FileCollection $value
   *
   * @return $this
   */
  public function setElencoAllegatiAllaDomanda($name, FileCollection $value)
  {
    $this->elencoAllegatiAllaDomanda->{$name} = $value;

    return $this;
  }

  /**
   * @return ElencoSoggettiAventiTitolo
   */
  public function getElencoSoggettiAventiTitolo()
  {
    return $this->elencoSoggettiAventiTitolo;
  }

  /**
   * @param ElencoSoggettiAventiTitolo $value
   *
   * @return $this
   */
  public function setElencoSoggettiAventiTitolo(ElencoSoggettiAventiTitolo $value)
  {
    $this->elencoSoggettiAventiTitolo = $value;

    return $this;
  }

  /**
   * @return ElencoAllegatiTecnici
   */
  public function getElencoAllegatiTecnici()
  {
    return $this->elencoAllegatiTecnici;
  }

  /**
   * @param string $name
   * @param FileCollection $value
   *
   * @return $this
   */
  public function setElencoAllegatoTecnici($name, FileCollection $value)
  {
    $this->elencoAllegatiTecnici->{$name} = $value;

    return $this;
  }

  /**
   * @return Vincoli
   */
  public function getVincoli()
  {
    return $this->vincoli;
  }

  /**
   * @param string $name
   * @param FileCollection $value
   *
   * @return $this
   */
  public function setVincoli($name, FileCollection $value)
  {
    $this->vincoli->{$name} = $value;

    return $this;
  }

  /**
   * @return ElencoProvvedimenti
   */
  public function getElencoProvvedimenti()
  {
    return $this->elencoProvvedimenti;
  }

  /**
   * @param string $name
   * @param FileCollection $value
   *
   * @return $this
   */
  public function setElencoProvvedimenti($name, FileCollection $value)
  {
    $this->elencoProvvedimenti->{$name} = $value;

    return $this;
  }

  public function getTipiIntervento()
  {
    switch ($this->tipo) {
      case \App\Entity\SciaPraticaEdilizia::TYPE_SCIA_PRATICA_EDILIZIA:
//            case \App\Entity\SciaPraticaEdilizia::TYPE_PERMESSO_DI_COSTRUIRE:
        return [
          self::MANUTENZIONE_STRAORDINARIA,
          self::RISTRUTTURAZIONE_EDILIZIA,
          self::RESTAURO_E_RISANAMENTO_CONSERVATIVO,
          self::RISTRUTTURAZIONE_URBANISTICA,
          self::ALTRO_TIPO
        ];
      default:
        return [
          'default'
        ];
    }
  }

  public function toHash()
  {
    $objectArray = [];
    foreach ($this as $key => $value) {
      if ($value instanceof HashableInterface) {
        $objectArray[$key] = $value->toHash();
      } else {
        $objectArray[$key] = $value;
      }
    }

    return $objectArray;
  }

  public function getAllegatiIdArray()
  {
    $idList = array_merge(
      $this->getElencoAllegatiAllaDomanda()->getAllegatiIdArray(),
      $this->getElencoSoggettiAventiTitolo()->toIdArray(),
      $this->getElencoAllegatiTecnici()->getAllegatiIdArray(),
      $this->getVincoli()->getAllegatiIdArray()
    );
    if ($this->getModuloDomanda()->hasContent()) {
      $idList[] = $this->getModuloDomanda()->getId();
    }

    return $idList;
  }

  /**
   * @return string
   */
  public function getNumeroDiFascicolo(): ?string
  {
    return $this->numeroDiFascicolo;
  }

  /**
   * @param $numeroDiFascicolo
   */
  public function setNumeroDiFascicolo( $numeroDiFascicolo )
  {
    $this->numeroDiFascicolo = $numeroDiFascicolo;
  }

  /**
   * @return string
   */
  public function getProtocolloPrincipale(): ?string
  {
    return $this->protocolloPrincipale;
  }

  /**
   * @param $protocolloPrincipale
   */
  public function setProtocolloPrincipale( $protocolloPrincipale )
  {
    $this->protocolloPrincipale = $protocolloPrincipale;
  }

  /**
   * @return mixed
   */
  public function getProtocolliAllegati()
  {
    return $this->protocolliAllegati;
  }

  /**
   * @param mixed $protocolliAllegati
   */
  public function setProtocolliAllegati($protocolliAllegati)
  {
    $this->protocolliAllegati = $protocolliAllegati;
  }

  /**
   * @return mixed
   */
  public function getMeta()
  {
    return $this->meta;
  }

  /**
   * @param mixed $meta
   */
  public function setMeta($meta)
  {
    if (is_array($meta)) {
      $this->meta = new Meta($meta ?? null);
    } else {
      $this->meta = $meta;
    }
  }

  /**
   * @param $key
   * @param $data
   */
  public function addMeta($key, $data)
  {
    $this->meta[$key] = $data;
  }


  /**
   * @return array
   */
  public function getAllowedProperties()
  {
    $properties = array();
    $properties = array_merge($properties, $this->elencoAllegatiAllaDomanda->getProperties());
    $properties = array_merge($properties, $this->elencoAllegatiTecnici->getProperties());
    $properties = array_merge($properties, $this->vincoli->getProperties());
    return $properties;
  }
}
