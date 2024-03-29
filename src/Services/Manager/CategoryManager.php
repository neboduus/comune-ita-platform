<?php


namespace App\Services\Manager;


use App\Entity\Categoria;
use Doctrine\ORM\EntityManagerInterface;
use phpDocumentor\Reflection\Types\True_;

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
    $category = $this->entityManager->getRepository('App\Entity\Categoria')->find($id);
    if ($category instanceof Categoria) {
      return $category;
    }
    return null;
  }

  public function getCategoryTree($parent = null, $spacing = '', $result = [])
  {
    $items = $this->entityManager->getRepository('App\Entity\Categoria')->findBy(['parent' => $parent], ['name' => 'asc']);

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

  /**
   * @param Categoria $category
   * @param array $result
   * @return array|mixed
   */
  public function getParents(Categoria $category, $result = [])
  {
    if ($category->getParent()) {
      $result[$category->getId()] = [
        'id' => $category->getId(),
        'name' => $category->getName(),
        'description' => $category->getDescription(),
        'object' => $category
      ];
      $result = $this->getCategoryTree($category, $result);
    }

    return $result;
  }



  public function hasRecursiveRelations(Categoria $category)
  {
    if ($category->getVisibleService()->count() > 0 || $category->getVisibleServicesGroup()->count() > 0) {
      return true;
    }

    foreach ($category->getChildren() as $item) {
      if ($this->hasRecursiveRelations($item)) {
        return true;
      }
    }

    return false;
  }
}
