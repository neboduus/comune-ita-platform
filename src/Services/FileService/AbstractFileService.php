<?php

namespace App\Services\FileService;

use App\Utils\StringUtils;
use Aws\S3\S3Client;
use League\Flysystem\FileExistsException;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\FilesystemInterface;
use Psr\Http\Message\RequestInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;

abstract class AbstractFileService
{
  const PRESIGNED_GET_EXPIRE_STRING = '+30 minutes';
  const PRESIGNED_PUT_EXPIRE_STRING = '+120 minutes';

  /**
   * @var FilesystemInterface
   */
  protected $fileSystem;

  /**
   * @var S3Client
   */
  private $s3Client;

  /**
   * @var string
   */
  private $s3Bucket;

  /**
   * @var string
   */
  private $uploadDestination;

  /**
   * @param FilesystemInterface $fileSystem
   * @param S3Client $s3Client
   * @param string $s3Bucket
   * @param string $uploadDestination
   */
  public function __construct(
    FilesystemInterface $fileSystem,
    S3Client $s3Client,
    string $s3Bucket,
    string $uploadDestination
  ) {
    $this->fileSystem = $fileSystem;
    $this->s3Client = $s3Client;
    $this->s3Bucket = $s3Bucket;
    $this->uploadDestination = $uploadDestination;
  }

  /**
   * @param string $path
   * @return false|string
   * @throws FileNotFoundException
   */
  public function getContent(string $path) {
    return $this->fileSystem->read($path);
  }

  /**
   * @param string $path
   * @return false|resource
   * @throws FileNotFoundException
   */
  public function getStream(string $path) {
    return $this->fileSystem->readStream($path);
  }

  /**
   * @param string $path
   * @return array|string|string[]
   * @throws FileNotFoundException
   */
  public function getHash(string $path) {
    $metadata = $this->fileSystem->getMetadata($path);
    if (isset($metadata['etag'])) {
      return str_replace('"', '', $metadata['etag']);
    } else {
      return '-';
    }
  }

  /**
   * @param string $path
   * @return false|string
   * @throws FileNotFoundException
   */
  public function getMimeType(string $path) {
    return $this->fileSystem->getMimetype($path);
  }

  /**
   * @param string $path
   * @return bool
   */
  public function fileExists(string $path): bool
  {
    return $this->fileSystem->has($path);
  }

  /**
   * @return bool
   */
  protected function isAllowedPresignedRequest(): bool
  {
    return !empty($this->s3Bucket) && $this->uploadDestination === 's3_filesystem';
  }

  /**
   * @param string $path
   * @param string $contents
   * @return bool
   * @throws FileExistsException
   */
  public function write(string $path, string $contents): bool
  {
    return $this->fileSystem->write($path, $contents);
  }

  /**
   * @param string $path
   * @return bool
   * @throws FileNotFoundException
   */
  public function delete(string $path): bool
  {
    return $this->fileSystem->delete($path);
  }

  /**
   * @param string $path
   * @param string $mimeType
   * @return RequestInterface
   */
  public function createPresignedPostRequest(string $path, string $mimeType): RequestInterface
  {
    $command = $this->s3Client->getCommand('PutObject', [
      'Bucket' => $this->s3Bucket,
      'Key' => $path,
      'ContentType' => $mimeType
    ]);
    return $this->s3Client->createPresignedRequest($command, self::PRESIGNED_PUT_EXPIRE_STRING)->withMethod('PUT');
  }

  /**
   * @param string $absolutePath
   * @param string $path
   * @param string $dispositionFilename
   * @return RequestInterface
   * @throws FileNotFoundException
   */
  public function createPresignedGetRequest(string $absolutePath, string $path, string $dispositionFilename): RequestInterface
  {
    $responseHeaderBag = new ResponseHeaderBag();
    $disposition = $responseHeaderBag->makeDisposition(
      ResponseHeaderBag::DISPOSITION_ATTACHMENT,
      $dispositionFilename
    );

    $command = $this->s3Client->getCommand('GetObject', [
      'Bucket' => $this->s3Bucket,
      'Key' => $absolutePath,
      'ResponseContentType' => $this->getMimeType($path),
      'ResponseContentDisposition' => $disposition,
    ]);
    return $this->s3Client->createPresignedRequest($command, self::PRESIGNED_GET_EXPIRE_STRING);
  }

  /**
   * @param $absolutePath
   * @param $path
   * @param $dispositionFilename
   * @return RedirectResponse
   * @throws FileNotFoundException
   */
  protected function createPresignedRequest($absolutePath, $path, $dispositionFilename): RedirectResponse
  {
    $request = $this->createPresignedGetRequest(
      $absolutePath,
      $path,
      $dispositionFilename
    );
    return new RedirectResponse((string)$request->getUri());
  }

  /**
   * @param $path
   * @param $dispositionFilename
   * @return Response
   */
  protected function createBinaryResponse($path, $dispositionFilename)
  {
    try {
      $fileService = $this;
      $response = new StreamedResponse(function () use ($path, $fileService) {
        $outputStream = fopen('php://output', 'wb');
        $fileStream = $fileService->getStream($path);
        stream_copy_to_stream($fileStream, $outputStream);
      });
      // Set file Content-Type

      $mimeType = $this->getMimetype($path);
      $response->headers->set('Content-Type', $mimeType);

      // Create the disposition of the file
      $filename = $dispositionFilename;
      $disposition = $response->headers->makeDisposition(
        ResponseHeaderBag::DISPOSITION_ATTACHMENT,
        $filename
      );

      $response->headers->set('Content-Disposition', $disposition);
      return $response;

    } catch (FileNotFoundException $exception) {
      return new Response('File not found!', Response::HTTP_NOT_FOUND);
    }
  }

  /**
   * @param $originalName
   * @param $filename
   * @return array|string|string[]|null
   */
  protected function getDispositionFilename($filename, $originalName=null)
  {
    $dispositionFilename = StringUtils::clean(mb_convert_encoding($originalName ?? $filename, "ASCII", "auto"));
    $filenameParts = explode('.', $filename);

    $systemFilename = $filename;
    $systemFilenameParts = explode('.', $systemFilename);
    if (end($filenameParts) != end($systemFilenameParts)) {
      $dispositionFilename .=  '.' . end($systemFilenameParts);
    }

    return $dispositionFilename;
  }

  /**
   * @param $content
   * @param $mimeType
   * @param $dispositionFilename
   * @return Response
   */
  protected function createBinaryResponseFromContent($content, $mimeType, $dispositionFilename): Response
  {
    $response = new Response($content);
    $response->headers->set('Content-Type', $mimeType);
    $response->headers->set('Content-Disposition', $dispositionFilename);

    return $response;
  }

}
