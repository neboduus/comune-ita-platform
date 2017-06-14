<?php

namespace AppBundle\Services;

use AppBundle\Entity\Allegato;
use AppBundle\Entity\GiscomPratica;
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

        $allegatoB = $hash['elencoUlterioriAllegatiTecnici']['allegatoB'];
        if (is_array($allegatoB) && isset($allegatoB[0])) {
            $hash['allegatoB'] = $allegatoB[0];
        } else {
            $hash['allegatoB'] = null;
        }
        unset($hash['elencoUlterioriAllegatiTecnici']['allegatoB']);

        $hash['elencoAllegatiTecnici'] = array_merge(
            $hash['elencoAllegatiTecnici'],
            $hash['elencoUlterioriAllegatiTecnici']
        );
        unset($hash['elencoUlterioriAllegatiTecnici']);

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

        foreach ($mappedPratica->getElencoUlterioriAllegatiTecnici() as $key => $value) {
            if ($value instanceof FileCollection) {
                $this->prepareFileCollection($value);
            }
        }

        foreach ($mappedPratica->getElencoProvvedimenti() as $key => $value) {
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

}
