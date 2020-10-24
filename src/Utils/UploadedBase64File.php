<?php

namespace AppBundle\Utils;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploadedBase64File extends UploadedFile
{

  public function __construct(string $base64Content, $mimeType)
  {
    preg_match('/data:([^;]*);base64,(.*)/', $base64Content, $matches);
    if (isset($matches[1])) {
      $mimeType = $matches[1];
    }

    // $filePath = tempnam('php://memory', '_upload_');
    $filePath = tempnam(sys_get_temp_dir(), 'UploadedFile');

    $file = fopen($filePath, "w");
    stream_filter_append($file, 'convert.base64-decode');
    fwrite($file, $base64Content);
    $meta_data = stream_get_meta_data($file);
    $path = $meta_data['uri'];
    $fileName = basename($path);
    fclose($file);

    parent::__construct($path, $fileName, $mimeType, null, null, true);
  }

}
