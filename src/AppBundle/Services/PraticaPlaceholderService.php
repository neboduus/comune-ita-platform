<?php

namespace AppBundle\Services;

use AppBundle\Entity\FormIO;
use AppBundle\Entity\Pratica;
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
   * PraticaPlaceholderService constructor.
   * @param RouterInterface $router
   * @param TranslatorInterface $translator
   */
  public function __construct(RouterInterface $router, TranslatorInterface $translator) {
    $this->router = $router;
    $this->translator = $translator;
  }

  /**
   * @param Pratica $pratica
   * @return array
   */
  public function getPlaceholders(Pratica $pratica)
  {
    $submissionTime = $pratica->getSubmissionTime() ? (new \DateTime())->setTimestamp(
      $pratica->getSubmissionTime()
    ) : null;
    $protocolTime = $pratica->getProtocolTime() ? (new \DateTime())->setTimestamp($pratica->getProtocolTime()) : null;

    $placeholders = [
      '%id%' => $pratica->getId(),
      '%pratica_id%' => $pratica->getId(),
      '%servizio%' => $pratica->getServizio()->getName(),
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
