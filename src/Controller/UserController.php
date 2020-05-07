<?php

namespace App\Controller;

use App\Entity\CPSUser;
use App\Logging\LogConstants;
use App\Services\RemoteContentProviderServiceInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as Controller;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class UserController
 * @package App\Controller
 * @Route("/user")
 */
class UserController extends Controller
{
    /**
     * @Route("/", name="user_dashboard")
     * @param EntityManagerInterface $entityManager
     * @return Response
     * @throws \Doctrine\DBAL\DBALException
     */
    public function index(EntityManagerInterface $entityManager)
    {
        $user = $this->getUser();

        $serviziRepository = $this->getDoctrine()->getRepository('App:Servizio');
        $servizi = $serviziRepository->findBy([], [], 3);

        $praticheRepo = $this->getDoctrine()->getRepository('App:Pratica');
        $pratiche = $praticheRepo->findBy(
            ['user' => $user],
            ['creationTime' => 'DESC'],
            3
        );

        $threads = [];

        $documents = [];
        $documentRepo = $entityManager->getRepository('App:Document');

        $sql = 'SELECT document.id from document where document.last_read_at is null and ((readers_allowed)::jsonb @> \'"' . $user->getCodiceFiscale() . '"\' or document.owner_id = \'' . $user->getId() . '\')';
        $stmt = $entityManager->getConnection()->prepare($sql);
        $stmt->execute();
        $documentsIds = $stmt->fetchAll();

        foreach ($documentsIds as $id) {
            $documents[] = $documentRepo->find($id);
        }

        return $this->render('User/index.html.twig', [
            'user' => $user,
            'servizi' => $servizi,
            'pratiche' => $pratiche,
            'threads' => $threads,
            'documents' => $documents
        ]);
    }

    /**
     * @Route("/profile", name="user_profile")
     * @Template()
     * @param Request $request
     * @param LoggerInterface $logger
     * @param TranslatorInterface $translator
     * @return RedirectResponse|Response
     */
    public function profile(Request $request, LoggerInterface $logger, TranslatorInterface $translator)
    {
        /** @var CPSUser $user */
        $user = $this->getUser();
        $form = $this->setupSdcUserForm($user)->handleRequest($request);

        if ($form->isSubmitted()) {
            $data = $form->getData();
            $this->storeSdcUserData($user, $data, $logger);

            $redirectRoute = $request->query->has('r') ? $request->query->get('r') : 'user_profile';
            $redirectRouteParams = $request->query->has('p') ? unserialize($request->query->get('p')) : array();
            $redirectRouteQuery = $request->query->has('p') ? unserialize($request->query->get('q')) : array();

            return $this->redirectToRoute($redirectRoute, array_merge($redirectRouteParams, $redirectRouteQuery));
        } else {
            if ($request->query->has('r')) {
                $this->addFlash(
                    'warning',
                    $translator->trans('completa_profilo')
                );
            }
        }

        return $this->render('User/profile.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }

    /**
     * @Route("/logout", name="user_logout")
     * @Template()
     * @return RedirectResponse
     */
    public function logout()
    {
        return new RedirectResponse('/Shibboleth.sso/Logout');
    }

    private function setupSdcUserForm(CPSUser $user)
    {
        $compiledCellulareData = $user->getCellulare();
        $compiledEmailData = $user->getEmail();

        $compiledIndirizzoResidenza = $user->getIndirizzoResidenza();
        $compiledCapResidenza = $user->getCapResidenza();
        $compiledCittaResidenza = $user->getCittaResidenza();
        $compiledProvinciaResidenza = $user->getProvinciaResidenza();
        $compiledStatoResidenza = $user->getStatoResidenza();

        $compiledIndirizzoDomicilio = $user->getIndirizzoDomicilio();
        $compiledCapDomicilio = $user->getCapDomicilio();
        $compiledCittaDomicilio = $user->getCittaDomicilio();
        $compiledProvinciaDomicilio = $user->getProvinciaDomicilio();
        $compiledStatoDomicilio = $user->getStatoDomicilio();

        $formBuilder = $this->createFormBuilder(null, ['attr' => ['id' => 'edit_user_profile']])
            ->add(
                'email_contatto',
                EmailType::class,
                ['label' => false, 'data' => $compiledEmailData, 'required' => false]
            )
            ->add(
                'cellulare_contatto',
                TextType::class,
                ['label' => false, 'data' => $compiledCellulareData, 'required' => false]
            )
            ->add(
                'data_nascita',
                DateType::class,
                ['widget' => 'single_text', 'label' => false, 'data' => $user->getDataNascita(), 'required' => true]
            )
            ->add(
                'luogo_nascita',
                TextType::class,
                ['label' => false, 'data' => $user->getLuogoNascita(), 'required' => true]
            )
            ->add(
                'sdc_indirizzo_residenza',
                TextType::class,
                ['label' => false, 'data' => $compiledIndirizzoResidenza, 'required' => true]
            )
            ->add(
                'sdc_cap_residenza',
                TextType::class,
                ['label' => false, 'data' => $compiledCapResidenza, 'required' => true]
            )
            ->add(
                'sdc_citta_residenza',
                TextType::class,
                ['label' => false, 'data' => $compiledCittaResidenza, 'required' => true]
            )
            /*->add('sdc_provincia_residenza', TextType::class,
              ['label' => false, 'data' => $compiledProvinciaResidenza, 'required' => true]
            )*/
            ->add('sdc_provincia_residenza', ChoiceType::class, [
                'label' => false,
                'data' => $compiledProvinciaResidenza,
                'choices' => CPSUser::getProvinces(),
                'required' => true
            ])
            ->add(
                'sdc_stato_residenza',
                TextType::class,
                ['label' => false, 'data' => $compiledStatoResidenza, 'required' => true]
            )
            ->add(
                'sdc_indirizzo_domicilio',
                TextType::class,
                ['label' => false, 'data' => $compiledIndirizzoDomicilio, 'required' => false]
            )
            ->add(
                'sdc_cap_domicilio',
                TextType::class,
                ['label' => false, 'data' => $compiledCapDomicilio, 'required' => false]
            )
            ->add(
                'sdc_citta_domicilio',
                TextType::class,
                ['label' => false, 'data' => $compiledCittaDomicilio, 'required' => false]
            )
            /*->add('sdc_provincia_domicilio', TextType::class,
              ['label' => false, 'data' => $compiledProvinciaDomicilio, 'required' => false]
            )*/
            ->add('sdc_provincia_domicilio', ChoiceType::class, [
                'label' => false,
                'data' => $compiledProvinciaDomicilio,
                'choices' => CPSUser::getProvinces(),
                'required' => true
            ])
            ->add(
                'sdc_stato_domicilio',
                TextType::class,
                ['label' => false, 'data' => $compiledStatoDomicilio, 'required' => false]
            )
            ->add(
                'save',
                SubmitType::class,
                ['label' => 'user.profile.salva_informazioni_profilo']
            );
        $form = $formBuilder->getForm();

        return $form;
    }

    private function storeSdcUserData(CPSUser $user, array $data, LoggerInterface $logger)
    {
        $manager = $this->getDoctrine()->getManager();
        $user
            ->setEmailContatto($data['email_contatto'])
            ->setCellulareContatto($data['cellulare_contatto'])
            ->setDataNascita($data['data_nascita'])
            ->setLuogoNascita($data['luogo_nascita'])
            ->setSdcIndirizzoResidenza($data['sdc_indirizzo_residenza'])
            ->setSdcCapResidenza($data['sdc_cap_residenza'])
            ->setSdcCittaResidenza($data['sdc_citta_residenza'])
            ->setSdcProvinciaResidenza($data['sdc_provincia_residenza'])
            ->setSdcStatoResidenza($data['sdc_stato_residenza'])
            ->setSdcIndirizzoDomicilio($data['sdc_indirizzo_domicilio'])
            ->setSdcCapDomicilio($data['sdc_cap_domicilio'])
            ->setSdcCittaDomicilio($data['sdc_citta_domicilio'])
            ->setSdcProvinciaDomicilio($data['sdc_provincia_domicilio'])
            ->setSdcStatoDomicilio($data['sdc_stato_domicilio']);

        $manager->persist($user);

        try {
            $manager->flush();
            $logger->info(LogConstants::USER_HAS_CHANGED_CONTACTS_INFO, ['userid' => $user->getId()]);
        } catch (\Exception $e) {
            $logger->error($e->getMessage());
        }
    }

    /**
     * @Route("/latest_news", name="user_latest_news")
     * @param RemoteContentProviderServiceInterface $newsProvider
     * @return JsonResponse
     */
    public function latestNews(RemoteContentProviderServiceInterface $newsProvider)
    {
        $enti = $this->getEntiFromCurrentUser();
        $data = $newsProvider->getLatestNews($enti);

        $response = new JsonResponse($data);
        $response->setMaxAge(3600);
        $response->setSharedMaxAge(3600);

        return $response;
    }

    private function getEntiFromCurrentUser()
    {
        $entityManager = $this->getDoctrine()->getManager();
        $entiPerUser = $entityManager->createQueryBuilder()
            ->select('IDENTITY(p.ente)')->distinct()
            ->from('App:Pratica', 'p')
            ->where('p.user = :user')
            ->setParameter('user', $this->getUser())
            ->getQuery()
            ->getResult();

        $repository = $entityManager->getRepository('App:Ente');
        if (count($entiPerUser) > 0) {
            $entiPerUser = array_reduce($entiPerUser, 'array_merge', array());
            $enti = $repository->findBy(['id' => $entiPerUser]);
        } else {
            $enti = $repository->findAll();
        }

        return $enti;
    }

    /**
     * @Route("/latest_deadlines", name="user_latest_deadlines")
     * @param RemoteContentProviderServiceInterface $newsProvider
     * @return JsonResponse
     */
    public function latestDeadlines(RemoteContentProviderServiceInterface $newsProvider)
    {
        $newsProvider = $this->get('ocsdc.remote_content_provider');
        $enti = $this->getEntiFromCurrentUser();
        $data = $newsProvider->getLatestDeadlines($enti);

        $response = new JsonResponse($data);
        $response->setMaxAge(3600);
        $response->setSharedMaxAge(3600);

        return $response;
    }
}
