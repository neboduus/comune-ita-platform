<?php


namespace App\Dto;

use App\Entity\ServiceGroup;
use JMS\Serializer\SerializerBuilder;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class ServiceGroupDto extends AbstractDto
{
  /**
   * @var RouterInterface
   */
  private $router;

  private $baseUrl;

  private $version;


  /**
   * @param RouterInterface $router
   */
  public function __construct(RouterInterface $router)
  {
    $this->router = $router;
  }


  /**
   * @return RouterInterface
   */
  public function getRouter(): RouterInterface
  {
    return $this->router;
  }

  /**
   * @param RouterInterface $router
   */
  public function setRouter(RouterInterface $router): void
  {
    $this->router = $router;
  }

  /**
   * @return mixed
   */
  public function getBaseUrl()
  {
    return $this->baseUrl;
  }

  /**
   * @param mixed $baseUrl
   */
  public function setBaseUrl($baseUrl): void
  {
    $this->baseUrl = $baseUrl;
  }

  /**
   * @return int
   */
  public function getVersion(): int
  {
    return $this->version;
  }

  /**
   * @param int $version
   */
  public function setVersion(int $version): void
  {
    $this->version = $version;
  }


  /**
   * @param ServiceGroup $serviceGroup
   * @param int $version
   * @return array
   */
  public function fromEntity(ServiceGroup $serviceGroup, int $version = 1): array
  {
    // Fixme: troviamo un modo migliore
    $attachmentEndpointUrl = $this->router->generate('service_group_api_get', ['id' => $serviceGroup->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

    $this->baseUrl = $attachmentEndpointUrl;
    $this->version = $version;

    $serializer = SerializerBuilder::create()->build();

    $dto = $serializer->toArray($serviceGroup);

    $dto["conditions_attachments"] = $this->preparePublicFiles($serviceGroup->getConditionsAttachments(), $attachmentEndpointUrl);
    $dto["costs_attachments"] = $this->preparePublicFiles($serviceGroup->getCostsAttachments(), $attachmentEndpointUrl);

    return $dto;
  }

}
