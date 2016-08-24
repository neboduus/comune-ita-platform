<?php

namespace AppBundle\Logging;

/**
 * Class Constants
 * @package AppBundle\Logging
 */
class LogConstants
{
    const USER_HAS_TO_ACCEPT_TERMS = "user still hasn't accepted platform terms.";
    const USER_HAS_ACCEPTED_TERMS = "User has accepted the terms of service";

    const CPS_USER_CREATED = "A new {type} user has been created";
    const CPS_USER_CREATED_WITH_BOGUS_DATA = "User data has partially bogus data";

    const PRATICA_CREATED = "A new {type} pratica has been created";
    const PRATICA_UPDATED = "A new {id} pratica has been updated";

    const PRATICA_COMPILING_STEP = "User {user} view step {step} of pratica #{pratica}";
    const PRATICA_FASCICOLO_ASSEGNATO = "Pratica %s : Assigned Numero Fascicolo %s";
    const PRATICA_PROTOCOLLO_ASSEGNATO = "Pratica %s : Assigned Numero Protocollo %s";
    const ALLEGATO_DOWNLOAD_PERMESSO_CPSUSER = "CPS User {user} downloaded attachment {originalFilename} belonging to pratica {pratica}";
    const ALLEGATO_DOWNLOAD_PERMESSO_OPERATORE = "Operatore {user} downloaded attachment {originalFilename} belonging to pratica {pratica}";
    const ALLEGATO_DOWNLOAD_NEGATO = "Denied download of Attachment {originalFilename} belonging to pratica {pratica}";
}
