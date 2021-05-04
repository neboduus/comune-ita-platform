<?php


namespace AppBundle\Services\Manager;


use AppBundle\BackOffice\CalendarsBackOffice;
use AppBundle\BackOffice\SubcriptionsBackOffice;
use AppBundle\BackOffice\SubcriptionPaymentsBackOffice;


class BackofficeManager
{

  const backoffices = [
    CalendarsBackOffice::IDENTIFIER => CalendarsBackOffice::class,
    SubcriptionsBackOffice::IDENTIFIER => SubcriptionsBackOffice::class,
    SubcriptionPaymentsBackOffice::IDENTIFIER => SubcriptionPaymentsBackOffice::class
  ];


  /**
   * @param string $identifier backoffice identifier
   * @return string|null returns backoffice class name if identifier matches backoffice identifier, else null
   */
  public static function getBackofficeClassByIdentifier(string $identifier)
  {
    foreach (self::backoffices as $backOfficeIdentifier => $className) {
      if (strtolower($backOfficeIdentifier) == $identifier) {
        return $className;
      }
    }
    return null;
  }

}
