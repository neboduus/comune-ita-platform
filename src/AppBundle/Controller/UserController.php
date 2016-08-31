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
        $praticheRepo = $this->getDoctrine()->getRepository('AppBundle:Pratica');

        $praticheDraft = $praticheRepo->findBy(
            [
                'user' => $user,
                'status' => Pratica::STATUS_DRAFT
            ],
            [
                'creationTime' => 'ASC'
            ]
        );

        $pratichePending = $praticheRepo->findBy(
            [
                'user' => $user,
                'status' => [
                    Pratica::STATUS_PENDING,
                    Pratica::STATUS_SUBMITTED,
                    Pratica::STATUS_REGISTERED
                ]
            ],
            [
                'creationTime' => 'ASC'
            ]
        );

        $praticheCompleted = $praticheRepo->findBy(
            [
                'user' => $user,
                'status' => Pratica::STATUS_COMPLETE
            ],
            [
                'creationTime' => 'ASC'
            ]
        );

        $praticheCancelled = $praticheRepo->findBy(
            [
                'user' => $user,
                'status' => Pratica::STATUS_CANCELLED
            ],
            [
                'creationTime' => 'ASC'
            ]
        );
        return array(
            'pratiche' => array(
                'draft'      => $praticheDraft,
                'pending'    => $pratichePending,
                'completed'  => $praticheCompleted,
                'cancelled'  => $praticheCancelled
            )
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
        $compiledCellulareData = $user->getCellulareContatto() != null ? $user->getCellulareContatto() : $user->getCellulare();
        $compiledEmailData = $user->getEmailContatto() != null ? $user->getEmailContatto() : $user->getEmail(); 
        $formBuilder = $this->createFormBuilder()
            ->add('email_contatto', EmailType::class,
                ['label' => false, 'data' => $compiledEmailData, 'required' => false]
            )
            ->add('cellulare_contatto', TextType::class,
                ['label' => false, 'data' => $compiledCellulareData, 'required' => false]
            )
            ->add('save', SubmitType::class,
                ['label' => $this->get('translator')->trans('user.profile.salva_informazioni_contatto')]
            );
        $form = $formBuilder->getForm();

        return $form;
    }

}
