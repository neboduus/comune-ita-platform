<?php

namespace App\Services;

use App\Entity\CPSUser;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Class TermsAcceptanceCheckerService
 */
class TermsAcceptanceCheckerService
{
  /**
   * @var ManagerRegistry
   */
  private $doctrine;

  /**
   * TermsAcceptanceCheckerService constructor.
   * @param ManagerRegistry $doctrine
   */
  public function __construct(ManagerRegistry $doctrine)
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

    $repo = $this->doctrine->getRepository('App:TerminiUtilizzo');
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
