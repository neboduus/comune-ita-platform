<?php

namespace AppBundle\Controller\Rest;


use FOS\RestBundle\Controller\AbstractFOSRestController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Class VersionControllerInterface
 */
abstract class AbstractApiController extends AbstractFOSRestController
{
  protected $supportedApiVersion;

  protected $defaultVersion;

  public function __construct($defaultApiVersion, $supportedApiVersion)
  {
    $this->defaultVersion = $defaultApiVersion;
    $this->supportedApiVersion = $supportedApiVersion;
  }
  protected function checkRequestedVersion(Request $request) {
    $version = intval($request->get('version', $this->defaultVersion));
    if (!in_array($version, $this->supportedApiVersion)) {
      throw  new BadRequestHttpException("Requested API version is no longer available");
    }
  }
}
