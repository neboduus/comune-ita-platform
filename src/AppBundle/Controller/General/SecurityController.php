<?php

namespace AppBundle\Controller\General;

use AppBundle\Security\AbstractAuthenticator;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Exception;

class SecurityController extends Controller
{
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
