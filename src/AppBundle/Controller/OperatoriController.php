<?php
namespace AppBundle\Controller;

use AppBundle\Entity\OperatoreUser;
use AppBundle\Entity\Pratica;
use AppBundle\Form\AzioniOperatore\NumeroFascicoloPraticaType;
use AppBundle\Form\AzioniOperatore\NumeroProtocolloPraticaType;
use AppBundle\Logging\LogConstants;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sluggable\Fixture\Handler\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Test\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class OperatoriController
 * @Route("/operatori")
 */
class OperatoriController extends Controller
{
    /**
     * @Route("/",name="operatori_index")
     * @Template()
     * @return array
     */
    public function indexAction()
    {
        $praticheRepo = $this->getDoctrine()->getRepository('AppBundle:Pratica');
        $praticheMie = $praticheRepo->findBy(
            [
                'operatore' => $this->getUser(),
                'ente' => $this->getUser()->getEnte(),
                'status' => [
                    Pratica::STATUS_PENDING,
                    Pratica::STATUS_SUBMITTED,
                    Pratica::STATUS_REGISTERED,
                ],
            ]
        );

        $praticheLibere = $praticheRepo->findBy(
            [
                'operatore' => null,
                'ente' => $this->getUser()->getEnte(),
                'status' => [
                    Pratica::STATUS_PENDING,
                    Pratica::STATUS_SUBMITTED,
                    Pratica::STATUS_REGISTERED,
                ],
            ]
        );

        $praticheConcluse = $praticheRepo->findBy(
            [
                'operatore' => $this->getUser(),
                'ente' => $this->getUser()->getEnte(),
                'status' => [
                    Pratica::STATUS_COMPLETE,
                    Pratica::STATUS_CANCELLED,
                ]
            ]
        );
        return array(
            'pratiche_mie'  => $praticheMie,
            'pratiche_libere'  => $praticheLibere,
            'pratiche_concluse'  => $praticheConcluse,
        );
    }

    /**
     * @Route("/{pratica}/autoassign",name="operatori_autoassing_pratica")
     */
    public function autoAssignPraticaAction(Pratica $pratica)
    {
        if ($pratica->getOperatore() !== null){
            throw new BadRequestHttpException("Pratica {$pratica->getId()} already assigned to {$pratica->getOperatore()->getFullName()}");
        }

        $pratica->setOperatore($this->getUser());
        $pratica->setStatus(Pratica::STATUS_PENDING);
        $this->getDoctrine()->getManager()->flush();

        $this->get('logger')->info(
            LogConstants::PRATICA_ASSIGNED,
            [
                'pratica' => $pratica->getId(),
                'user' => $pratica->getUser()->getId(),
            ]
        );

        return $this->redirectToRoute('operatori_show_pratica', ['pratica' => $pratica]);
    }

    /**
     * @Route("/{pratica}/detail",name="operatori_show_pratica")
     * @Template()
     * @return array
     */
    public function showPraticaAction(Pratica $pratica, Request $request)
    {
        $this->checkUserCanAccessPratica($this->getUser(), $pratica);

        $form = $this->setupCommentForm()->handleRequest($request);

        if ($form->isSubmitted()) {
            $commento = $form->getData();
            $pratica->addCommento($commento);
            $this->getDoctrine()->getManager()->flush();

            $this->get('logger')->info(
                LogConstants::PRATICA_COMMENTED,
                [
                    'pratica' => $pratica->getId(),
                    'user' => $pratica->getUser()->getId(),
                ]
            );

            return $this->redirectToRoute('operatori_show_pratica', ['pratica' => $pratica]);
        }

        return [
            'form' => $form->createView(),
            'pratica' => $pratica
        ];
    }

    /**
     * @Route("/{pratica}/approva",name="operatori_approva_pratica")
     */
    public function approvaPraticaAction(Pratica $pratica, Request $request)
    {
        $this->checkUserCanAccessPratica($this->getUser(), $pratica);
        $pratica->setStatus(Pratica::STATUS_COMPLETE);
        $this->getDoctrine()->getManager()->flush();

        $this->get('logger')->info(
            LogConstants::PRATICA_APPROVED,
            [
                'pratica' => $pratica->getId(),
                'user' => $pratica->getUser()->getId(),
            ]
        );

        return $this->redirectToRoute('operatori_show_pratica', ['pratica' => $pratica]);
    }

    /**
     * @Route("/{pratica}/rifiuta",name="operatori_rifiuta_pratica")
     */
    public function rifiutaPraticaAction(Pratica $pratica, Request $request)
    {
        $this->checkUserCanAccessPratica($this->getUser(), $pratica);
        $pratica->setStatus(Pratica::STATUS_CANCELLED);
        $this->getDoctrine()->getManager()->flush();

        $this->get('logger')->info(
            LogConstants::PRATICA_CANCELLED,
            [
                'pratica' => $pratica->getId(),
                'user' => $pratica->getUser()->getId(),
            ]
        );

        return $this->redirectToRoute('operatori_show_pratica', ['pratica' => $pratica]);
    }

    private function checkUserCanAccessPratica(OperatoreUser $user, Pratica $pratica)
    {
        $operatore = $pratica->getOperatore();
        if (!$operatore  instanceof OperatoreUser || $operatore->getId() !== $user->getId()){
            throw new UnauthorizedHttpException("User can not read pratica {$pratica->getId()}");
        }
    }

    /**
     * @return FormInterface
     */
    private function setupCommentForm()
    {
        $translator = $this->get('translator');
        $data = array();
        $formBuilder = $this->createFormBuilder($data)
            ->add('text', TextareaType::class, [
                'label' => false,
                'required' => true,
                'attr' => [
                    'rows' => '5',
                    'class' => 'form-control input-inline datepicker',
                ],
            ])
            ->add('createdAt', HiddenType::class,[
                'data' => time(),
            ])
            ->add('creator', HiddenType::class, [
                'data' => $this->getUser()->getFullName(),
            ])
            ->add('save', SubmitType::class, [
                'label' => $translator->trans('aggiungi_commento'),
                'attr' => [
                    'class' => 'btn btn-success',
                ],
            ]);
        $form = $formBuilder->getForm();

        return $form;
    }

    /**
     * @Route("/{pratica}/numero_di_fascicolo",name="operatori_set_numero_fascicolo_a_pratica")
     * @Template()
     * @return array
     */
    public function addNumeroDiFascicoloToPraticaAction(Request $request, Pratica $pratica)
    {
        $form = $this->createForm(NumeroFascicoloPraticaType::class, $pratica);
        $form->add('submit', SubmitType::class, array(
            'label' => $this->get('translator')->trans('salva'),
        ));
        $form->handleRequest($request);

        if ($form->isValid()) {
            $this->get('logger')->info(sprintf(LogConstants::PRATICA_FASCICOLO_ASSEGNATO, $pratica->getId(), $pratica->getNumeroFascicolo()));

            $this->getDoctrine()->getManager()->flush();
        }

        return array('form' => $form->createView());
    }

    /**
     * @Route("/{pratica}/numero_di_protocollo",name="operatori_set_numero_protocollo_a_pratica")
     * @Template()
     * @return array
     */
    public function addNumeroDiProtocolloToPraticaAction(Request $request, Pratica $pratica)
    {
        $form = $this->createForm(NumeroProtocolloPraticaType::class, $pratica);
        $form->add('submit', SubmitType::class, array(
            'label' => $this->get('translator')->trans('salva'),
        ));
        $form->handleRequest($request);

        if ($form->isValid()) {
            $this->get('logger')->info(sprintf(LogConstants::PRATICA_PROTOCOLLO_ASSEGNATO, $pratica->getId(), $pratica->getNumeroProtocollo()));

            $this->getDoctrine()->getManager()->flush();
        }

        return array('form' => $form->createView());
    }
}
