<?php

namespace AppBundle\Services;

use AppBundle\Entity\Allegato;
use AppBundle\Entity\AllegatoOperatore;
use AppBundle\Entity\GiscomPratica;
use AppBundle\Entity\RichiestaIntegrazione;
use AppBundle\Entity\RispostaOperatore;
use AppBundle\Mapper\Giscom\File;
use AppBundle\Mapper\Giscom\FileCollection;
use AppBundle\Mapper\Giscom\SciaPraticaEdilizia as MappedPraticaEdilizia;
use Doctrine\ORM\EntityManagerInterface;

class GiscomAPIMapperService
{
    /**
     * @var EntityManagerInterface $em
     */
    private $em;

    /**
     * @var \Doctrine\Common\Persistence\ObjectRepository
     */
    private $repository;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
        $this->repository = $this->em->getRepository(Allegato::class);
    }

    /**
     * @param GiscomPratica $pratica
     * @param bool $prepareFiles
     *
     * @return array
     */
    public function map(GiscomPratica $pratica, $prepareFiles = true, $prepareProtocolli = true)
    {
        $mappedPratica = new MappedPraticaEdilizia($pratica->getDematerializedForms());
        $mappedPratica->setId($pratica->getId());
        $mappedPratica->setCfPresentante($pratica->getUser()->getCodiceFiscale());

        $meta = array();
        // Recupero i file collegati alle richieste di integrazione
        $this->prepareIntegrationRequests($pratica, $meta);

        // Recupero gli allegati degli operatori
        $this->prepareOperatorAnswer($pratica, $meta);

        // Set meta documents
        $mappedPratica->setMeta($meta);

        if ($prepareFiles) {
            $this->prepareAllegati($mappedPratica);
        }

        if ($prepareProtocolli) {
            $mappedPratica->setNumeroDiFascicolo($pratica->getNumeroFascicolo());
            $mappedPratica->setProtocolloPrincipale($pratica->getNumeroProtocollo());
            $numeriProtocolloAllegati = [];
            foreach ($pratica->getNumeriProtocollo() as $v) {
                $numeriProtocolloAllegati[$v->id] = $v->protocollo;
            }
            $mappedPratica->setProtocolliAllegati($numeriProtocolloAllegati);
        }

        $hash = $mappedPratica->toHash();

        return $hash;
    }

    private function prepareAllegati(MappedPraticaEdilizia $mappedPratica)
    {
        $this->prepareFile($mappedPratica->getModuloDomanda());

        foreach ($mappedPratica->getElencoAllegatiAllaDomanda() as $key => $value) {
            if ($value instanceof FileCollection) {
                $this->prepareFileCollection($value);
            }
        }

        $this->prepareFileCollection($mappedPratica->getElencoSoggettiAventiTitolo());

        foreach ($mappedPratica->getElencoAllegatiTecnici() as $key => $value) {
            if ($value instanceof FileCollection) {
                $this->prepareFileCollection($value);
            }
        }

        foreach ($mappedPratica->getVincoli() as $key => $value) {
            if ($value instanceof FileCollection) {
                $this->prepareFileCollection($value);
            }
        }

        foreach ($mappedPratica->getMeta() as $key => $value) {
            if ($value instanceof FileCollection) {
                $this->prepareFileCollection($value);
            }
        }

    }

    private function prepareFileCollection(FileCollection $collection)
    {
        /** @var File $file */
        foreach ($collection as $file) {
            $this->prepareFile($file);
        }
    }

    private function prepareFile(File $file)
    {
        if ($file->hasContent()) {
            $realFile = $this->repository->find($file->getId());
            if ($realFile instanceof Allegato && $realFile->getFile() instanceof \Symfony\Component\HttpFoundation\File\File) {
                $file->setContent(base64_encode(file_get_contents($realFile->getFile()->getPathname())));
            }
        }
    }

    /**
     * @param GiscomPratica $pratica
     * @param $meta
     */
    private function prepareIntegrationRequests(GiscomPratica $pratica, &$meta)
    {
        // Recupero i file collegati alle richieste di integrazione
        $richiesteIntegrazione = $pratica->getRichiesteIntegrazione();
        $richieste = array();
        if (count($richiesteIntegrazione) > 0 ) {
            /** @var RichiestaIntegrazione $richiesta */
            foreach ($richiesteIntegrazione as $richiesta) {
                $temp = array(
                    'id'      => $richiesta->getId(),
                    'name'    => $richiesta->getFilename(),
                    'type'    => !empty($richiesta->getType()) ? $richiesta->getType() : RichiestaIntegrazione::TYPE_DEFAULT,
                    'content' => null
                );
                $richieste []= $temp;
            }
            $meta['DOC_RIC'] = $richieste;
        }
    }

    /**
     * @param GiscomPratica $pratica
     * @param $meta
     */
    private function prepareOperatorAnswer(GiscomPratica $pratica, &$meta)
    {
        if ($pratica->getEsito() != null ) {
            $rispostaOperatore = $pratica->getRispostaOperatore();
            $risposta = array();

            if ( $rispostaOperatore ) {

                $temp = array(
                    'id'      => $rispostaOperatore->getId(),
                    'name'    => $rispostaOperatore->getFilename(),
                    'type'    => !empty($rispostaOperatore->getType()) ? $rispostaOperatore->getType() : RispostaOperatore::TYPE_DEFAULT,
                    'content' => null
                );
                $risposta []= $temp;
                if ($pratica->getEsito()) {
                    $meta['DOC_ACC'] = $risposta;
                } else {
                    $meta['DOC_RIG'] = $risposta;
                }
            }
        }
    }

}
