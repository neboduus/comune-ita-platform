<?php

namespace AppBundle\Controller;

use AppBundle\Entity\CPSUser;
use AppBundle\Entity\Pratica;
use AppBundle\Entity\Servizio;
use AppBundle\Logging\LogConstants;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * Class UserController
 * @package AppBundle\Controller
 * @Route("/user")
 */
class UserController extends Controller
{
    /**
     * @Route("/", name="user_dashboard")
     * @Template()
     * @param Request $request
     * @return array
     */
    public function indexAction(Request $request)
    {
        $user = $this->getUser();

        $serviziRepository = $this->getDoctrine()->getRepository('AppBundle:Servizio');
        $servizi = $serviziRepository->findBy([], [], 4);

        $praticheRepo = $this->getDoctrine()->getRepository('AppBundle:Pratica');
        $pratiche = $praticheRepo->findBy(
            ['user' => $user],
            ['creationTime' => 'ASC'],
            3
        );

        return array(
            'user'     => $user,
            'servizi' => $servizi,
            'pratiche' => $pratiche
        );
    }

    /**
     * @Route("/profile", name="user_profile")
     * @Template()
     * @param Request $request
     *
     * @return array
     */
    public function profileAction(Request $request)
    {
        /** @var CPSUser $user */
        $user = $this->getUser();
        $form = $this->setupContactsForm($user)->handleRequest($request);

        if ($form->isSubmitted()) {
            $data = $form->getData();
            $this->storeContactsData($user, $data, $this->get('logger'));
            return $this->redirectToRoute('user_profile');
        }

        return [
            'form' => $form->createView(),
            'user' => $user,
        ];
    }

    private function storeContactsData(CPSUser $user, array $data, LoggerInterface $logger)
    {
        $manager = $this->getDoctrine()->getManager();
        $user->setEmailContatto($data['email_contatto'])
             ->setCellulareContatto($data['cellulare_contatto']);
        $manager->persist($user);
        try {
            $manager->flush();
            $logger->info(LogConstants::USER_HAS_CHANGED_CONTACTS_INFO, ['userid' => $user->getId()]);
        } catch (\Exception $e) {
            $logger->error($e->getMessage());
        }
    }

    private function setupContactsForm(CPSUser $user)
    {
        $formBuilder = $this->createFormBuilder()
            ->add('email_contatto', EmailType::class,
                ['label' => false, 'data' => $user->getEmailContatto()]
            )
            ->add('cellulare_contatto', TextType::class,
                ['label' => false, 'data' => $user->getCellulareContatto()]
            )
            ->add('save', SubmitType::class,
                ['label' => $this->get('translator')->trans('user.profile.salva_informazioni_contatto')]
            );
        $form = $formBuilder->getForm();

        return $form;
    }

}
