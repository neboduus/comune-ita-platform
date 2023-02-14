<?php

namespace App\Utils;

use App\Entity\Pratica;
use App\Utils\StringUtils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\RedirectResponse;

class iCalUtils
{
  /**
   * iCal Calendar Event Object
   * @var string
   */
  private $iCal = "BEGIN:VCALENDAR\nVERSION:2.0\nPRODID:-//Opencity//NONSGML Event Calendar//EN\nMETHOD:PUBLISH\nBEGIN:VEVENT\nDTSTART:%s\nDTEND:%s\nLOCATION:%s\nTRANSP:OPAQUE\nSEQUENCE:0\nUID:%s\nDTSTAMP:%s\nSUMMARY:%s\nDESCRIPTION:%s\nPRIORITY:1\nCLASS:PUBLIC\nBEGIN:VALARM\nTRIGGER:-PT10080M\nACTION:DISPLAY\nDESCRIPTION:Reminder\nEND:VALARM\nEND:VEVENT\nEND:VCALENDAR\n";

  /**
   * Google Calendar Event Url
   * @var string
   */
  private $googleCalendarEvent = "http://www.google.com/calendar/render?action=TEMPLATE&text=%s&dates=%s/%s&details=%s&location=%s&trp=false&sprop=&sprop=name:";

  /**
   * Outlook Calendar Event Url
   * @var string
   */
  private $outlookCalendarEvent = "https://outlook.office.com/calendar/0/deeplink/compose?subject=%s&body=%s&startdt=%s&enddt=%s&location=%s&path=/calendar/action/compose&rru=addevent";

  /**
   * Summary of pratica
   * @var Pratica
   */
  private $pratica;

  /**
   * Summary of __construct
   * @param Pratica $pratica
   */
  public function __construct(Pratica $pratica)
  {
    $this->pratica = $pratica;
  }

  /**
   * Get the meeting event given the requested type
   *
   * @param string|null $type
   * @return Response
   */
  public function getMeetingEvent(?string $type): Response
  {
    if (!$this->pratica->getMeetings()->count() > 0) {
      return new Response('error', Response::HTTP_NOT_FOUND);
    }

    $start = $this->pratica->getMeetings()->getValues()[0]->getFromTime();
    $end = $this->pratica->getMeetings()->getValues()[0]->getToTime();
    $name = $this->pratica->getServizio()->getName();
    $description = $this->pratica->getMeetings()->getValues()[0]->getUserMessage();
    $location = $this->pratica->getMeetings()->getValues()[0]->getCalendar()->getLocation();

    switch ($type) {
      case 'outlook':
        return $this->getOutlookCalendarEvent($start, $end, $location, $name, $description);
      case 'google':
        return $this->getGoogleCalendarEvent($start, $end, $location, $name, $description);
      case 'ical':
        return $this->getICal($start, $end, $location, $name, $description);
    }

    return new Response('error', Response::HTTP_BAD_REQUEST);
  }

  /**
   * Get iCalendar file
   * @param $start
   * @param $end
   * @param $location
   * @param $name
   * @param $description
   * @return Response
   */
  private function getICal($start, $end, $location, $name, $description): Response
  {
    $filename = StringUtils::clean(mb_convert_encoding($name, "ASCII", "auto")).'.ics';
    $this->iCal = sprintf(
      $this->iCal,
      $start->format('Ymd\THis'),
      $end->format('Ymd\THis'),
      strip_tags($location),
      uniqid(),
      date('Ymd\THis'),
      $name,
      $description
    );

    $response = new Response($this->iCal);
    $contentDisposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename);
    $response->headers->set('Content-Type', 'text/calendar');
    $response->headers->set('Content-Disposition', $contentDisposition);

    return $response;
  }

  /**
   * Get Google Calendar Event url
   * @param $start
   * @param $end
   * @param $location
   * @param $name
   * @param $description
   * @return RedirectResponse
   */
  private function getGoogleCalendarEvent($start, $end, $location, $name, $description): RedirectResponse
  {
    $this->googleCalendarEvent = sprintf(
      $this->googleCalendarEvent,
      $name,
      $start->format('Ymd\THis'),
      $end->format('Ymd\THis'),
      $description,
      strip_tags(trim($location))
    );

    return new RedirectResponse($this->googleCalendarEvent);
  }

  /**
   * Get Outlook Calendar Event url
   * @param $start
   * @param $end
   * @param $location
   * @param $name
   * @param $description
   * @return RedirectResponse
   */
  private function getOutlookCalendarEvent($start, $end, $location, $name, $description): RedirectResponse
  {
    $this->outlookCalendarEvent = sprintf(
      $this->outlookCalendarEvent,
      $name,
      $description,
      $start->format('Y-m-d\TH:i:s'),
      $end->format('Y-m-d\TH:i:s'),
      strip_tags(trim($location))
    );

    return new RedirectResponse($this->outlookCalendarEvent);
  }
}
