<?php

namespace AppBundle\Services;

use AppBundle\Entity\CPSUser;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * Class TermsAcceptanceCheckerService
 */
class TermsAcceptanceCheckerService
{
  /**
   * @var RegistryInterface
   */
  private $doctrine;

  /**
   * TermsAcceptanceCheckerService constructor.
   * @param RegistryInterface $doctrine
   */
  public function __construct(RegistryInterface $doctrine)
  {
    $this->doctrine = $doctrine;
  }


  /**
   * @param CPSUser $user
   * @return bool
   */
  public function checkIfUserHasAcceptedMandatoryTerms(CPSUser $user)
  {
    $acceptedTerms = $user->getAcceptedTerms();

    $repo = $this->doctrine->getRepository('AppBundle:TerminiUtilizzo');
    $mandatoryTerms = $repo->findByMandatory(true);

    foreach ($mandatoryTerms as $k => $term) {
      if (isset($acceptedTerms[$term->getId() . '']) &&
        $acceptedTerms[$term->getId() . '']['timestamp'] >= $term->getLatestRevisionTime()) {
        unset($mandatoryTerms[$k]);
      }
    }

    return count($mandatoryTerms) == 0;
  }
}
