<?php

namespace AppBundle\Dto;

use AppBundle\Entity\Allegato;
use \DateTime;

abstract class AbstractDto
{

  /**
   * @param $schema
   * @param $field
   * @return bool
   */
  public function isUploadField($schema, $field): bool
  {
    return (isset($schema[$field . '.type']) && ($schema[$field . '.type'] == 'file' || $schema[$field . '.type'] == 'sdcfile'));
  }

  /**
   * @param $collection
   * @param string $baseUrl
   * @param int $version
   * @return array
   */
  public function prepareFileCollection($collection, string $baseUrl = '', int $version = 1): array
  {
    $files = [];
    if ($collection == null) {
      return $files;
    }
    /** @var Allegato $c */
    foreach ($collection as $c) {
      $files[] = self::prepareFile($c, $baseUrl, $version);
    }
    return $files;
  }

  public function prepareFormioFile($files, string $baseUrl = '', int $version = 1): array
  {
    $result = [];
    foreach ($files as $f) {
      $id = $f['data']['id'];
      $temp['id'] = $id;
      $temp['name'] = $f['name'];
      $temp['url'] = $baseUrl . '/attachments/' . $id . '?version=' . $version;
      $temp['originalName'] = $f['originalName'];
      $temp['description'] = isset($f['fileType']) ? $f['fileType'] : Allegato::DEFAULT_DESCRIPTION;
      $temp['protocol_required'] = $f['protocol_required'] ?? true;
      $result[] = $temp;
    }
    return $result;
  }

  /**
   * @param Allegato $file
   * @param string $baseUrl
   * @param int $version
   * @return array
   */
  public function prepareFile(Allegato $file, string $baseUrl = '', int $version = 1): array
  {

    $filename = $file->getName();
    $filenameParts = explode('.', $filename);
    $systemFilename = $file->getFilename();
    $systemFilenameParts = explode('.', $systemFilename);
    if (end($filenameParts) != end($systemFilenameParts)) {
      $filename .=  '.' . end($systemFilenameParts);
    }

    $temp['id'] = $file->getId();
    $temp['name'] = $filename;
    $temp['url'] = $baseUrl . '/attachments/' . $file->getId() . '?version=' . $version;
    $temp['originalName'] = $file->getFilename();
    $temp['description'] = $file->getDescription() ?? Allegato::DEFAULT_DESCRIPTION;
    $temp['created_at'] = $file->getCreatedAt();
    $temp['protocol_required'] = $file->isProtocolRequired();

    return $temp;
  }

  /**
   * @param $keyField
   * @return bool
   */
  public function isDateField($keyField): bool
  {
    $parts = explode('.', $keyField);
    if (end($parts) === 'natoAIl') {
      return true;
    }
    return false;
  }

  /**
   * @param $value
   * @return string
   */
  public function prepareDateField($value): string
  {
    $date = str_replace('/', '-', $value);
    try {
      $parsedDate = new DateTime($date);
      return $parsedDate->format(DateTime::W3C);
    } catch (\Exception $e) {
      return '';
    }
  }
}
