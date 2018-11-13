<?php

namespace AppBundle\Mapper\Giscom;

use AppBundle\Mapper\HashableInterface;
use AppBundle\Mapper\Giscom\SciaPraticaEdilizia\ModuloDomanda;
use AppBundle\Mapper\Giscom\SciaPraticaEdilizia\ElencoAllegatiAllaDomanda;
use AppBundle\Mapper\Giscom\SciaPraticaEdilizia\ElencoAllegatiTecnici;
use AppBundle\Mapper\Giscom\SciaPraticaEdilizia\ElencoSoggettiAventiTitolo;
use AppBundle\Mapper\Giscom\SciaPraticaEdilizia\Vincoli;
use AppBundle\Mapper\Giscom\SciaPraticaEdilizia\ElencoProvvedimenti;

class SciaPraticaEdilizia implements HashableInterface
{
    const MANUTENZIONE_STRAORDINARIA = 'manutenzione_straordinaria';
    const RISTRUTTURAZIONE_EDILIZIA = 'ristrutturazione_edilizia';
    const RESTAURO_E_RISANAMENTO_CONSERVATIVO = 'restauro_e_risanamento_conservativo';
    const RISTRUTTURAZIONE_URBANISTICA = 'ristrutturazione_urbanistica';
    /**
     * @var string Uuid
     */
    private $id;

    /**
     * @var string
     */
    private $tipo = 'SCIA';

    /**
     * @var string
     */
    private $cfPresentante;

    /**
     * @var string
     */
    private $tipoIntervento;

    /**
     * @var ModuloDomanda
     */
    private $moduloDomanda;

    /**
     * @var ElencoAllegatiAllaDomanda
     */
    private $elencoAllegatiAllaDomanda;

    /**
     * @var ElencoSoggettiAventiTitolo
     */
    private $elencoSoggettiAventiTitolo;

    /**
     * @var ElencoAllegatiTecnici
     */
    private $elencoAllegatiTecnici;

    /**
     * @var Vincoli
     */
    private $vincoli;

    /**
     * @var ElencoProvvedimenti
     */
    private $elencoProvvedimenti;

    /**
     * @var string
     */
    private $numeroDiFascicolo;

    /**
     * @var string
     */
    private $protocolloPrincipale;

    /**
     * @var []
     */
    private $protocolliAllegati;

    public function __construct(array $data = null)
    {
        $data = $data ?? [];
        $this->moduloDomanda = new ModuloDomanda($data['moduloDomanda'] ?? null);
        $this->elencoAllegatiAllaDomanda = new ElencoAllegatiAllaDomanda($data['elencoAllegatiAllaDomanda'] ?? null);
        $this->elencoSoggettiAventiTitolo = new ElencoSoggettiAventiTitolo($data['elencoSoggettiAventiTitolo'] ?? []);
        $this->elencoAllegatiTecnici = new ElencoAllegatiTecnici($data['elencoAllegatiTecnici'] ?? []);
        $this->vincoli = new Vincoli($data['vincoli'] ?? []);
        $this->tipoIntervento = $data['tipoIntervento'] ?? null;
        $this->protocolliAllegati = [];
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
     * @param string $tipo
     *
     * @return $this
     */
    public function setTipo($tipo)
    {
        $this->tipo = $tipo;

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
        return [
            self::MANUTENZIONE_STRAORDINARIA,
            self::RISTRUTTURAZIONE_EDILIZIA,
            self::RESTAURO_E_RISANAMENTO_CONSERVATIVO,
            self::RISTRUTTURAZIONE_URBANISTICA
        ];
    }

    public function toHash()
    {
        $objectArray = [];
        foreach($this as $key => $value) {
            if ($value instanceof HashableInterface){
                $objectArray[$key] = $value->toHash();
            }else{
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
        if ($this->getModuloDomanda()->hasContent()){
            $idList[] = $this->getModuloDomanda()->getId();
        }

        return $idList;
    }

    /**
     * @return string
     */
    public function getNumeroDiFascicolo(): string
    {
        return $this->numeroDiFascicolo;
    }

    /**
     * @param string $numeroDiFascicolo
     */
    public function setNumeroDiFascicolo(string $numeroDiFascicolo)
    {
        $this->numeroDiFascicolo = $numeroDiFascicolo;
    }

    /**
     * @return string
     */
    public function getProtocolloPrincipale(): string
    {
        return $this->protocolloPrincipale;
    }

    /**
     * @param string $protocolloPrincipale
     */
    public function setProtocolloPrincipale(string $protocolloPrincipale)
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
}
