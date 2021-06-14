<?php

namespace AppBundle\Controller\Ui\Frontend;

use AppBundle\Http\TransparentPixelResponse;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\Routing\Annotation\Route;


/**
 * Class TrackingController
 */
class TrackingController extends Controller
{
  /** @var EntityManagerInterface */
  private $entityManager;

  /** @var LoggerInterface */
  private $logger;

  /**
   * TrackingController constructor.
   * @param EntityManagerInterface $entityManager
   * @param LoggerInterface $logger
   */
  public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)
  {
    $this->entityManager = $entityManager;
    $this->logger = $logger;
  }


  /**
   * @Route("track-message.gif", name="track_message")
   * @param Request $request
   * @return TransparentPixelResponse
   */
  public function trackMessageEmailAction(Request $request)
  {
    $id = $request->query->get('id');
    if ($id) {
      $message = $this->entityManager->getRepository('AppBundle:Message')->find($id);
      if ($message and !$message->getReadAt()) {
        $message->setReadAt(time());
        try {
          $this->entityManager->flush();
          $this->entityManager->persist($message);
        } catch (ORMException $exception) {
          $this->logger->error($exception->getMessage() . ' on set message read time');
        }
      }
    }

    return new TransparentPixelResponse();
  }
}
