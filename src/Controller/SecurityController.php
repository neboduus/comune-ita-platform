<?php


namespace App\Controller;


use App\Entity\User;
use App\Form\Security\NewPasswordType;
use App\Form\Security\PasswordRequestType;
use App\Services\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

/**
 * Class SecurityController
 * @Route("/operatori")
 */
class SecurityController extends AbstractController
{
  use TargetPathTrait;

  private $mailer;
  private $params;

  public function __construct(MailerService $mailer, ParameterBagInterface $params)
  {
    $this->mailer = $mailer;
    $this->params = $params;
  }

  /**
   * @Route("/login", name="security_login")
   * @param Request $request
   * @param Security $security
   * @param AuthenticationUtils $helper
   *
   * @return Response
   */
  public function login(Request $request, Security $security, AuthenticationUtils $helper): Response
  {
    // if user is already logged in, don't display the login page again
    if ($security->isGranted('ROLE_ADMIN')) {
      return $this->redirectToRoute('admin_index');
    }
    if($security->isGranted('ROLE_OPERATORE')){
      return $this->redirectToRoute('operatori_index');
    }

    // this statement solves an edge-case: if you change the locale in the login
    // page, after a successful login you are redirected to a page in the previous
    // locale. This code regenerates the referrer URL whenever the login page is
    // browsed, to ensure that its locale is always the current one.
    $this->saveTargetPath($request->getSession(), 'main', $this->generateUrl('login'));

    return $this->render(
      'Security/login.html.twig',
      [
        'last_username' => $helper->getLastUsername(),
        'error' => $helper->getLastAuthenticationError(),
      ]
    );
  }

  /**
   * @Route("/reset-password", name="reset_password", methods={"GET", "POST"})
   * @param Request $request
   * @param EntityManagerInterface $entityManager
   * @return RedirectResponse|Response
   * @throws Exception
   */
  public function resetPassword(Request $request, EntityManagerInterface $entityManager)
  {
    $form = $this->createForm(PasswordRequestType::class);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      $username = $form->get('username')->getData();
      $user = $entityManager->getRepository(User::class)->findOneBy(['username' => $username]);

      if (!$user instanceof User) {
        $this->addFlash('danger', "Utente non trovato...");

        return $this->redirectToRoute('reset_password');
      }

      if (!$user->isEnabled()) {
        $this->addFlash('danger', "Utente non attivo...");

        return $this->redirectToRoute('reset_password');
      }

      $token = bin2hex(random_bytes(32));
      $user->setConfirmationToken($token);
      $entityManager->flush();

      //$this->mailer->sendResetPasswordMessage($user);
      $this->addFlash('info', "Controlla la tua casella e-mail per il link di conferma!");

      return $this->redirectToRoute('security_login');
    }

    return $this->render('Security/reset-password.html.twig', ['form' => $form->createView()]);
  }

  /**
   * @Route("/reset-password/confirm/{token}", name="reset_password_confirm", methods={"GET", "POST"})
   *
   * @param Request $request
   * @param string $token
   * @param EntityManagerInterface $entityManager
   * @param UserPasswordEncoderInterface $encoder
   * @param TokenStorageInterface $tokenStorage
   * @param SessionInterface $session
   *
   * @return RedirectResponse|Response
   */
  public function resetPasswordCheck(Request $request, string $token, EntityManagerInterface $entityManager, UserPasswordEncoderInterface $encoder, TokenStorageInterface $tokenStorage, SessionInterface $session)
  {
    $user = $entityManager->getRepository(User::class)->findOneBy(['passwordResetToken' => $token]);

    if (!$token || !$user instanceof User) {
      $this->addFlash('danger', "Utente non trovato...");

      return $this->redirectToRoute('reset_password');
    }

    $form = $this->createForm(NewPasswordType::class);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      $plainPassword = $form->get('plainPassword')->getData();
      $password = $encoder->encodePassword($user, $plainPassword);
      $user->setPassword($password);
      $user->setConfirmationToken(null);
      $entityManager->flush();

      $token = new UsernamePasswordToken($user, $password, 'main');
      $tokenStorage->setToken($token);
      $session->set('_security_main', serialize($token));

      $this->addFlash('success', "La nuova password è stata impostata. Esegui il login!");

      return $this->redirectToRoute('Security_login');
    }

    return $this->render(
      'security/reset-password-confirm.html.twig',
      ['form' => $form->createView()]
    );

  }

  /**
   * @Route("/profile", name="security_profile")
   * @param Request $request
   * @return Response
   */
  public function profile(Request $request)
  {
    echo 'aaaa';
    exit;
  }

  /**
   * @Route("/feedback", name="security_feedback")
   * @param Request $request
   * @return Response
   */
  public function feedback(Request $request)
  {
    $status = $request->query->get('status', null);
    $msg = $request->query->get('msg', null);

    return $this->render(
      'Security/feedback.html.twig',
      [
        'status' => $status,
        'msg' => $msg,
      ]
    );
  }

  /**
   * This is the route the user can use to logout.
   *
   * But, this will never be executed. Symfony will intercept this first
   * and handle the logout automatically. See logout in config/packages/security.yaml
   *
   * @Route("/logout", name="security_logout")
   * @throws Exception
   */
  public function logout(): void
  {
    throw new Exception('This should never be reached!');
  }
}
