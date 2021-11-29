<?php


namespace AppBundle\Services\Manager;


use AppBundle\Entity\Categoria;
use Doctrine\ORM\EntityManagerInterface;

class CategoryManager
{
  /**
   * @var EntityManagerInterface
   */
  private $entityManager;


  /**
   * CategoryManager constructor.
   * @param EntityManagerInterface $entityManager
   */
  public function __construct(EntityManagerInterface $entityManager)
  {
    $this->entityManager = $entityManager;
  }

  /**
   * @param $id
   * @return Categoria|null
   */
  public function get($id)
  {
    $category = $this->entityManager->getRepository('AppBundle:Categoria')->find($id);
    if ($category instanceof Categoria) {
      return $category;
    }
    return null;
  }

  public function getCategoryTree($parent = null, $spacing = '', $result = [])
  {
    $items = $this->entityManager->getRepository('AppBundle:Categoria')->findBy(['parent' => $parent], ['name' => 'asc']);

    if (count($items) > 0) {
      /** @var Categoria $i */
      foreach ($items as $i) {
        $result[$i->getId()] = [
          'id' => $i->getId(),
          'name' => $i->getName(),
          'description' => $i->getDescription(),
          'spaced_name' => $spacing . ' ' . $i->getName(),
          'related_services' => $i->getServices()->count(),
          'related_services_group' => $i->getServicesGroup()->count(),
          'object' => $i
        ];
        $result = $this->getCategoryTree($i->getId(), $spacing . '-', $result);
      }
    }
    return $result;
  }
}
