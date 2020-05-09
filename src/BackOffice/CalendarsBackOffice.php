<?php

namespace App\BackOffice;

use App\Entity\Calendar;
use App\Entity\DematerializedFormPratica;
use App\Entity\Meeting;
use App\Entity\Pratica;
use App\Services\InstanceService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class CalendarsBackOffice implements BackOfficeInterface
{
    const NAME = 'Prenotazione appuntamenti';

    const PATH = 'operatori_calendars_index';

    private $em;

    private $is;

    /**
     * @var TranslatorInterface $translator
     */
    private $translator;

    private $required_fields = [
        'applicant_meeting' => array(
            "applicant.data.completename.data.name",
            "applicant.data.completename.data.surname",
            "user_message",
            "calendar"
        )
    ];

    public function __construct(TranslatorInterface $translator, EntityManagerInterface $em, InstanceService $is)
    {
        $this->translator = $translator;
        $this->em = $em;
        $this->is = $is;
    }

    public function getName()
    {
        return self::NAME;
    }

    public function getPath()
    {
        return self::PATH;
    }

    public function getRequiredFields()
    {
        return $this->required_fields;
    }

    /**
     * @param Pratica|DematerializedFormPratica $data
     * @return Meeting|array
     */
    public function execute($data)
    {
        $meetingData = $data->getDematerializedForms();
        unset($meetingData['flattened']['submit']);
        $meetingData = $meetingData['flattened'];
        ksort($meetingData);
        $requiredFields = $this->getRequiredFields();

        // Check among all possible integrations which one to use
        $integrationType = null;
        foreach ($requiredFields as $type => $fields) {
            sort($fields);
            if (!$integrationType && array_values(array_intersect(array_keys($meetingData), array_values($fields))) == array_values($fields)) {
                // Integration type found: no previous integration found
                $integrationType = $type;
            }
        }
        if (!$integrationType) {
            return ['error' => $this->translator->trans('backoffice.integration.fields_error')];
        }

        // Check contacts. At least one among tel, cell, email is required
        if (!$meetingData['applicant.data.email_address'] &&
            !$meetingData['applicant.data.phone_number']) {
            return ['error' => $this->translator->trans('backoffice.integration.calendars.missing_contacts')];
        }
        preg_match_all("/\(([^\)]*)\)/", $meetingData['calendar'], $matches);

        $repo = $this->em->getRepository('App:Calendar');
        /** @var Calendar $calendar */
        $calendar = $repo->findOneBy(['id' => $matches[1]]);
        if (!$calendar) {
            return ['error' => $this->translator->trans('backoffice.integration.calendars.calendar_error', ['calendar_id' => $matches[1]])];
        }
        // Get start-end datetime
        $tmp = explode('@', $meetingData['calendar']);
        $date = trim($tmp[0]);
        $time = $tmp[1];
        $time = trim(explode('(', $time)[0]);
        $tmp = explode('-', $time);
        $startTime = trim($tmp[0]);
        $endTime = trim($tmp[1]);
        $start = \DateTime::createFromFormat('d/m/Y:H:i', $date . ':' . $startTime);
        $end = \DateTime::createFromFormat('d/m/Y:H:i', $date . ':' . $endTime);

        try {
            $meeting = new Meeting();
            $meeting->setCalendar($calendar);
            $meeting->setEmail($meetingData['applicant.data.email_address']);
            $meeting->setName($data->getUser()->getFullName());
            $meeting->setPhoneNumber($meetingData['applicant.data.phone_number']);
            $meeting->setFiscalCode($meetingData['applicant.data.fiscal_code.data.fiscal_code']);
            $meeting->setUser($data->getUser());
            $meeting->setUserMessage($meetingData['user_message']);
            $meeting->setFromTime($start);
            $meeting->setToTime($end);

            $this->em->persist($meeting);
            $this->em->flush();

            return $meeting;
        } catch (\Exception $exception) {
            return ['error' => $this->translator->trans('backoffice.integration.calendars.save_meeting_error')];
        }
    }

    public function checkRequiredFields($schema)
    {
        $errors = [];
        foreach ($this->getRequiredFields() as $key => $requiredFields) {
            foreach ($requiredFields as $field) {
                if (!array_key_exists($field . '.label', $schema)) {
                    $errors[$key][] = $this->translator->trans('backoffice.integration.missing_field', ['field' => $field]);
                }
            }
            if (!array_key_exists($key, $errors)) {
                return null;
            }
        }
        return $errors;
    }
}
