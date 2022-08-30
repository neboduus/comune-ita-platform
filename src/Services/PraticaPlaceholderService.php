<?php

namespace App\Services;

use App\Entity\FormIO;
use App\Entity\Pratica;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;

class PraticaPlaceholderService
{
  /**
   * @var RouterInterface
   */
  private $router;

  /**
   * @var TranslatorInterface
   */
  private $translator;
  /**
   * @var EntityManagerInterface
   */
  private $em;
  /**
   * @var LoggerInterface
   */
  private $logger;

  /**
   * PraticaPlaceholderService constructor.
   * @param EntityManagerInterface $entityManager
   * @param RouterInterface $router
   * @param TranslatorInterface $translator
   * @param LoggerInterface $logger
   */
  public function __construct(EntityManagerInterface $entityManager, RouterInterface $router, TranslatorInterface $translator, LoggerInterface $logger) {
    $this->em = $entityManager;
    $this->router = $router;
    $this->translator = $translator;
    $this->logger = $logger;
  }

  /**
   * @param Pratica $pratica
   * @return array
   */
  public function getPlaceholders(Pratica $pratica)
  {
    // Todo: get from default locale
    $locale = $pratica->getLocale() ?? 'it';
    $service = $pratica->getServizio();
    $service->setTranslatableLocale($locale);
    try {
      $this->em->refresh($service);
    } catch (ORMException $e) {
      $this->logger->error($e->getMessage() . ' --- ' . $e->getTraceAsString());
    }

    $submissionTime = $pratica->getSubmissionTime() ? (new \DateTime())->setTimestamp(
      $pratica->getSubmissionTime()
    ) : null;
    $protocolTime = $pratica->getProtocolTime() ? (new \DateTime())->setTimestamp($pratica->getProtocolTime()) : null;

    $placeholders = [
      '%id%' => $pratica->getId(),
      '%pratica_id%' => $pratica->getId(),
      '%servizio%' => $pratica->getServizio()->getName(),
      '%service_fullname%' => $pratica->getServizio()->getFullName(),
      '%gruppo%' => $pratica->getServizio()->getServiceGroup() ? $pratica->getServizio()->getServiceGroup()->getName() : "",
      '%categoria%' => $pratica->getServizio()->getTopics() ? $pratica->getServizio()->getTopics()->getName() : "",
      '%protocollo%' => $pratica->getNumeroProtocollo() ? $pratica->getNumeroProtocollo() : "",
      '%messaggio_personale%' => !empty(trim($pratica->getMotivazioneEsito())) ? $pratica->getMotivazioneEsito(
      ) : $this->translator->trans('messages.pratica.no_reason'),
      '%user_name%' => $pratica->getUser()->getFullName(),
      '%indirizzo%' => $this->router->generate('home', [], UrlGeneratorInterface::ABSOLUTE_URL),
      '%data_corrente%' => (new \DateTime())->format('d/m/Y'),
      '%data_acquisizione%' => $submissionTime ? $submissionTime->format('d/m/Y') : "",
      '%ora_acquisizione%' => $submissionTime ? $submissionTime->format('H:i:s') : "",
      '%data_protocollo%' => $protocolTime ? $protocolTime->format('d/m/Y') : "",
      '%ora_protocollo%' => $protocolTime ? $protocolTime->format('H:i:s') : "",
    ];

    $dataPlaceholders = [];
    // Recupero i placeholders per i dati inseriti dall'utente
    $submission = self::getFlattenedSubmission($pratica);
    foreach ($submission as $key => $value) {
      if (!is_array($value)) {
        $dataPlaceholders["%".$key."%"] = (!$value || $value == "") ? "" : $value;
      }
    }

    // Recupero i placeholders per i dati inseriti nel backoffice
    $backofficeSubmission = self::getBackofficeFlattenedSubmission($pratica);
    if ($backofficeSubmission) {
      foreach ($backofficeSubmission as $key => $value) {
        if (!is_array($value)) {
          $dataPlaceholders["%".$key."%"] = (!$value || $value == "") ? "" : $value;
        }
      }
    }

    return array_merge($placeholders, $dataPlaceholders);
  }

  /**
   * @param Pratica $pratica
   * @return array
   */
  public static function getFlattenedSubmission(Pratica $pratica) {
    $data = ($pratica instanceof FormIO) ? $pratica->getDematerializedForms() : [];

    if (!isset($data['flattened'])) {
      return $data;
    }

    $decoratedData = $data['flattened'];
    $submission = array();

    foreach (array_keys($decoratedData) as $path) {
      $parts = explode('.', trim($path, '.'));
      $key = null;
      foreach ($parts as $part) {
        // Salto data
        if ($part === 'data') {
          continue;
        }
        $key = join(".", array_filter(array($key, $part)));
      }
      $submission[$key] = $decoratedData[$path];
    }
    return $submission;
  }

  /**
   * @param Pratica $pratica
   * @return array|mixed
   */
  public static function getBackofficeFlattenedSubmission(Pratica $pratica) {
    $data = ($pratica instanceof FormIO) ? $pratica->getBackofficeFormData() : [];

    if (!isset($data['flattened'])) {
      return $data;
    }

    $decoratedData = $data['flattened'];
    $submission = array();

    foreach (array_keys($decoratedData) as $path) {
      $parts = explode('.', trim($path, '.'));
      $key = null;
      foreach ($parts as $part) {
        // Salto data
        if ($part === 'data') {
          continue;
        }
        $key = join(".", array_filter(array($key, $part)));
      }
      $submission[$key] = $decoratedData[$path];
    }
    return $submission;
  }
}
