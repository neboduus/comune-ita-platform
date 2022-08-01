<?php


namespace App\Controller\Rest;


use App\Entity\User;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\Request;

class SessionAuthAPIController extends AbstractFOSRestController
{
  /**
   * @var JWTTokenManagerInterface
   */
  private $JWTTokenManager;

  /**
   * SessionAuthAPIController constructor.
   * @param JWTTokenManagerInterface $JWTTokenManager
   */
  public function __construct(JWTTokenManagerInterface $JWTTokenManager)
  {
    $this->JWTTokenManager = $JWTTokenManager;
  }


  /**
   * Retreive a session auth token
   * @Rest\Get("/session-auth", name="user_session_auth_token_get")
   *
   * @SWG\Response(
   *     response=200,
   *     description="Retreive an Auth Token"
   * )
   *
   * @SWG\Response(
   *     response=404,
   *     description="Application not found"
   * )
   * @SWG\Tag(name="SessionAuth")
   *
   * @return View
   */
  public function getSessionAuthToken()
  {

    $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
    $user = $this->getUser();
    if ($user instanceof User) {
      return $this->view(['token' => $this->JWTTokenManager->create($user)]);
    }

    return $this->view(['error' => 'You are not logged in']);
  }
}
