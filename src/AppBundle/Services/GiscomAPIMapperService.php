<?php

namespace AppBundle\Services;

use AppBundle\Entity\Allegato;
use AppBundle\Entity\AllegatoOperatore;
use AppBundle\Entity\GiscomPratica;
use AppBundle\Entity\Integrazione;
use AppBundle\Entity\Pratica;
use AppBundle\Entity\RichiestaIntegrazione;
use AppBundle\Entity\RispostaIntegrazione;
use AppBundle\Entity\RispostaIntegrazioneRepository;
use AppBundle\Entity\RispostaOperatore;
use AppBundle\Mapper\Giscom\File;
use AppBundle\Mapper\Giscom\FileCollection;
use AppBundle\Mapper\Giscom\SciaPraticaEdilizia as MappedPraticaEdilizia;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class GiscomAPIMapperService
{
  /**
   * @var EntityManagerInterface $em
   */
  private $em;

  /**
   * @var UrlGeneratorInterface
   */
  private $router;

  /**
   * @var \Doctrine\Common\Persistence\ObjectRepository
   */
  private $repository;

  /** @var RispostaIntegrazioneRepository */
  private $integrationAnswerRepository;

  /** @var Pratica */
  private $pratica;

  public function __construct(EntityManagerInterface $em, UrlGeneratorInterface $router)
  {
    $this->em = $em;
    $this->router = $router;
    $this->repository = $this->em->getRepository(Allegato::class);
    $this->integrationAnswerRepository = $this->em->getRepository(RispostaIntegrazione::class);
  }

  /**
   * @param GiscomPratica $pratica
   * @param bool $prepareFiles
   *
   * @return array
   */
  public function map(GiscomPratica $pratica, $prepareFiles = true, $prepareProtocolli = true)
  {

    $this->pratica = $pratica;

    $mappedPratica = new MappedPraticaEdilizia($pratica->getDematerializedForms());
    $mappedPratica->setId($pratica->getId());
    $mappedPratica->setStato($pratica->getStatus());
    $mappedPratica->setCfPresentante($pratica->getUser()->getCodiceFiscale());

    $mappedPratica->setModuloCompilato($this->prepareModuloCompilato($pratica));

    $meta = array();
    // Recupero i file collegati alle richieste di integrazione
    $this->prepareIntegrationRequests($pratica, $meta);

    // Recupero i file collegati alle risposte di integrazione
    $this->prepareIntegrationAnswers($pratica, $meta);

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

      // Recupero il protocollo
      if ($realFile instanceof Integrazione && $realFile->getType() != null) {
        /** @var RispostaIntegrazione $integrationAnswer */
        $integrationAnswer = $this->integrationAnswerRepository->findByIntegrationRequest($realFile->getIdRichiestaIntegrazione())[0];
        if ($integrationAnswer instanceof RispostaIntegrazione && $integrationAnswer->getNumeroProtocollo()) {
          $file->setProtocollo($integrationAnswer->getNumeroProtocollo());
        }
      } elseif ($realFile instanceof Allegato && $realFile->getType() != null) {
        if ($realFile->getNumeroProtocollo()) {
          $file->setProtocollo($realFile->getNumeroProtocollo());
        } else {
          $file->setProtocollo($this->pratica->getNumeroProtocollo());
        }
      }

      // Recupero il contenuto del file
      if ($realFile instanceof Allegato && $realFile->getFile() instanceof \Symfony\Component\HttpFoundation\File\File) {

        // Set endpoint url to recover file content
        //$file->setContent(base64_encode(file_get_contents($realFile->getFile()->getPathname())));
        $file->setContent($this->router->generate('giscom_api_attachment', ['attachment' => $file->getId()], UrlGeneratorInterface::ABSOLUTE_URL));
      }
    }
  }

  /**
   * @param GiscomPratica $pratica
   * @param $meta
   */
  private function prepareModuloCompilato(GiscomPratica $pratica)
  {
    // Recupero i file collegati alle richieste di integrazione
    /** @var ArrayCollection $moduli */
    $moduli = $pratica->getModuliCompilati();
    if (count($moduli) > 0) {
      /** @var Allegato $m */
      // fixme: ci possono essere più moduli compilati per pratica?
      /*foreach ($moduli as $m) {
        $modulo = new MappedPraticaEdilizia\ModuloCompilato();
        $modulo->setId($m->getId());
        $modulo->setName($m->getFilename());
        $modulo->setType($m->getType());
        $modulo->setProtocollo($m->getNumeroProtocollo());
        $modulo->setContent(null);
        $result[]=$modulo;
      }*/
      $m = $moduli->first();
      $modulo = new MappedPraticaEdilizia\ModuloCompilato();
      $modulo->setId($m->getId());
      $modulo->setName($m->getFilename());
      $modulo->setType($m->getType());
      // Il numero di protocollo del modulo compilato coincide con quello della pratica
      $modulo->setProtocollo($pratica->getNumeroProtocollo());
      // Set endpoint url to recover file content
      //$modulo->setContent(base64_encode(file_get_contents($m->getFile()->getPathname())));
      $modulo->setContent($this->router->generate('giscom_api_attachment', ['attachment' => $m->getId()], UrlGeneratorInterface::ABSOLUTE_URL));
      return $modulo;
    }
    return null;
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
    if (count($richiesteIntegrazione) > 0) {
      /** @var RichiestaIntegrazione $richiesta */
      foreach ($richiesteIntegrazione as $richiesta) {
        $temp = array(
          'id' => $richiesta->getId(),
          'name' => $richiesta->getFilename(),
          'type' => !empty($richiesta->getType()) ? $richiesta->getType() : RichiestaIntegrazione::TYPE_DEFAULT,
          'protocollo' => $richiesta->getNumeroProtocollo(),
          'content' => null
        );
        $richieste [] = $temp;
      }
      $meta['DOC_RIC'] = $richieste;
    }
  }

  /**
   * @param GiscomPratica $pratica
   * @param $meta
   */
  private function prepareIntegrationAnswers(GiscomPratica $pratica, &$meta)
  {

    // Recupero i file collegati alle richieste di integrazione
    $richiesteIntegrazione = $pratica->getRichiesteIntegrazione();

    /** @var RispostaIntegrazioneRepository $integrationAnswerRepo */
    $integrationAnswerRepo = $this->em->getRepository('AppBundle:RispostaIntegrazione');

    $risposte = array();
    if (count($richiesteIntegrazione) > 0) {
      /** @var RichiestaIntegrazione $richiesta */
      foreach ($richiesteIntegrazione as $richiesta) {

        /** @var RispostaIntegrazione $integrationAnswer */
        $integrationAnswerCollection = $integrationAnswerRepo->findByIntegrationRequest($richiesta->getId());

        if (count($integrationAnswerCollection) > 0) {
          $integrationAnswer = [0];
          if ($integrationAnswer instanceof RispostaIntegrazione) {
            $temp = array(
              'id' => $integrationAnswer->getId(),
              'name' => $integrationAnswer->getFilename(),
              'type' => !empty($integrationAnswer->getType()) ? $integrationAnswer->getType() : RispostaIntegrazione::TYPE_DEFAULT,
              'protocollo' => $richiesta->getNumeroProtocollo(),
              'content' => null
            );
            $risposte [] = $temp;
          }
        }
      }
      $meta['MODULO_COMPILATO'] = $risposte;
    }
  }

  /**
   * @param GiscomPratica $pratica
   * @param $meta
   */
  private function prepareOperatorAnswer(GiscomPratica $pratica, &$meta)
  {
    if ($pratica->getEsito() != null) {
      /** @var RispostaOperatore $rispostaOperatore */
      $rispostaOperatore = $pratica->getRispostaOperatore();
      $risposta = array();

      if ($rispostaOperatore) {

        $temp = array(
          'id' => $rispostaOperatore->getId(),
          'name' => $rispostaOperatore->getFilename(),
          'type' => !empty($rispostaOperatore->getType()) ? $rispostaOperatore->getType() : RispostaOperatore::TYPE_DEFAULT,
          'protocollo' => $rispostaOperatore->getNumeroProtocollo(),
          'content' => null
        );
        $risposta [] = $temp;
        if ($pratica->getEsito()) {
          $meta['DOC_ACC'] = $risposta;
        } else {
          $meta['DOC_RIG'] = $risposta;
        }
      }
    }
  }

}
