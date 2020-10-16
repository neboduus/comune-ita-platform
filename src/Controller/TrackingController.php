<?php

namespace App\Controller;

use App\Http\TransparentPixelResponse;
use Doctrine\ORM\ORMException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\Routing\Annotation\Route;


/**
 * Class TrackingController
 */
class TrackingController extends Controller
{
  /**
   * @Route("track-message.gif", name="track_message")
   * @param Request $request
   * @return TransparentPixelResponse
   */
  public function trackMessageEmailAction(Request $request)
  {
    $id = $request->query->get('id');
    if ($id) {
      $em = $this->get('doctrine.orm.entity_manager');
      $message = $em->getRepository('App:Message')->find($id);
      if ($message and !$message->getReadAt()) {
        $message->setReadAt(time());
        try {
          $em->flush();
          $em->persist($message);
        } catch (ORMException $exception) {
          $this->get('logger')->error($exception->getMessage() . ' on set message read time');
        }
      }
    }

    return new TransparentPixelResponse();
  }
}
