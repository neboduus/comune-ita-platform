<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class AttestazioneAnagrafica
 * @ORM\Entity
 */
class AttestazioneAnagrafica extends Pratica
{
    /**
     * @var boolean
     * @ORM\Column(name="allegato_operatore_richiesto", type="boolean")
     */
    private $allegatoOperatoreRichiesto;

    public function __construct()
    {
        parent::__construct();
        $this->type = self::TYPE_ATTESTAZIONE_ANAGRAFICA;
        $this->allegatoOperatoreRichiesto = true;
    }

    /**
     * @return boolean
     */
    public function isAllegatoOperatoreRichiesto(): bool
    {
        return $this->allegatoOperatoreRichiesto;
    }
}
