<?php

namespace App\Controller\General;

use App\Security\AbstractAuthenticator;
use App\Security\CasAuthenticator;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use App\Security\DedaLoginAuthenticator;
use App\Security\DedaLogin\DedaLoginClient;
use Exception;

class SecurityController extends Controller
{
  /**
   * @var DedaLoginClient
   */
  private $dedaLoginClient;
  /**
   * @var SessionInterface
   */
  private $session;

  public function __construct(DedaLoginClient $dedaLoginClient, SessionInterface $session)
  {
    $this->dedaLoginClient = $dedaLoginClient;
    $this->session = $session;
  }

  /**
   * @Route("/login", name="login")
   * @param Request $request
   * @return RedirectResponse
   */
  public function loginAction(Request $request)
  {

    $parameters = [];
    $format = $request->query->get('format', false);
    if ($format) {
      $parameters['format'] = $format;
    }

    // Redirect in base a configurazione di istanza
    return $this->redirectToRoute($this->getAuthRedirect(), $parameters);
  }

  /**
   * @Route("/auth/spid/metadata", name="metadata")
   * @param Request $request
   * @return Response
   */
  public function metadataAction(Request $request)
  {
    if ($this->getParameter('login_route') === DedaLoginAuthenticator::LOGIN_ROUTE) {
      return new Response($this->dedaLoginClient->getMetadata(), 200, ['Content-Type' => 'text/xml']);
    }

    throw new NotFoundHttpException("Current authentication handler does not handle metadata");
  }

  /**
   * @Route("/auth/spid/acs", name="acs")
   * @param Request $request
   * @return Response
   */
  public function acsAction(Request $request)
  {
    if ($this->getParameter('login_route') === DedaLoginAuthenticator::LOGIN_ROUTE) {
      if ($samlResponse = $request->get('SAMLResponse')) {
        $assertionResponseData = $this->dedaLoginClient->checkAssertion($samlResponse);
        $userData = $this->dedaLoginClient->createUserDataFromAssertion($assertionResponseData);
        $this->session->set('DedaLoginUserData', $userData);
        return $this->redirectToRoute("login_deda");
      }
    }

    throw new NotFoundHttpException("Current authentication handler does not handle attribute consumer service");
  }

  /**
   * @Route("/auth/login-pat", name="login_pat")
   */
  public function loginPatAction()
  {
    throw new UnauthorizedHttpException("Something went wrong in authenticator");
  }

  /**
   * @Route("/auth/login-open", name="login_open")
   * @param Request $request
   * @return RedirectResponse
   */
  public function loginOpenAction(Request $request)
  {
    if ($request->query->has('_abort')){
      return $this->redirectToRoute('home');
    }
    throw new UnauthorizedHttpException("Something went wrong in authenticator");
  }

  /**
   * @Route("/auth/login-deda", name="login_deda")
   * @param Request $request
   * @return Response
   */
  public function loginDedaAction(Request $request)
  {
    if ($this->getParameter('login_route') !== DedaLoginAuthenticator::LOGIN_ROUTE) {
      throw new UnauthorizedHttpException("User can not login with login_deda");
    }

    if ($request->query->has('idp')){
      return new RedirectResponse($this->dedaLoginClient->getAuthRequest($request->query->get('idp')));
    }
    return $this->render('@App/Default/loginDeda.html.twig');
  }

  /**
   * @Route("/auth/login-cas", name="login_cas")
   */
  public function loginCasAction(Request $request)
  {
    if ($request->get(CasAuthenticator::QUERY_TICKET_PARAMETER)) {
      throw new UnauthorizedHttpException("Something went wrong in authenticator");
    }
    return new RedirectResponse($this->getParameter('cas_login_url').'?service='.urlencode($request->getUri()));
  }

  /**
   * @Route("/auth/login-success", name="login_success")
   * @param Request $request
   * @return Response
   */
  public function loginSuccess(Request $request)
  {
    return $this->render(
      '@App/Default/loginSuccess.html.twig',
      ['user' => $this->getUser()]
    );
  }

  /**
   * @Route("/logout", name="user_logout")
   * @throws Exception
   * @see LogoutSuccessHandler
   */
  public function logout()
  {
    throw new Exception('This method can be blank - it will be intercepted by the logout key on your firewall');
  }

  private function getAuthRedirect()
  {
    if ($this->getParameter('login_route') == AbstractAuthenticator::LOGIN_TYPE_NONE) {
      return 'home';
    }

    return $this->getParameter('login_route');
  }
}
