<?php

namespace AppBundle\Form\Base;


use AppBundle\Entity\Allegato;
use AppBundle\Entity\Pratica;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ChooseAllegatoType extends AbstractType
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var \Doctrine\Common\Persistence\ObjectRepository|EntityRepository
     */
    protected $repository;

    protected $validator;

    /**
     * ChooseAllegatoType constructor.
     *
     * @param EntityManager $entityManager
     * @param ValidatorInterface $validator
     */
    public function __construct(EntityManager $entityManager, ValidatorInterface $validator)
    {
        $this->entityManager = $entityManager;
        $this->repository = $this->entityManager->getRepository(Allegato::class);
        $this->validator = $validator;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('add', FileType::class, [
            'mapped' => false,
            'label' => false,
            'required' => false,
        ]);

        $builder->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'onPreSubmit'));
        $builder->addEventListener(FormEvents::PRE_SET_DATA, array($this, 'onPreSetData'));
    }


    /**
     * FormEvents::PRE_SET_DATA $listener
     *
     * @param FormEvent $event
     */
    public function onPreSetData(FormEvent $event)
    {
        $this->addChoise($event->getForm());
    }

    /**
     * FormEvents::PRE_SUBMIT $listener
     *
     * @param FormEvent $event
     */
    public function onPreSubmit(FormEvent $event)
    {
        $options = $event->getForm()->getConfig()->getOptions();

        /** @var Pratica $pratica */
        $pratica = $options['pratica'];
        $fileDescription = $options['fileDescription'];

        $data = $event->getData();

        $fileUpload = $data['add'] ?? null;
        if (isset( $data['choose'] ) && $data['choose'] != '') {
            $fileChoices = (array)$data['choose'];
        } else {
            $fileChoices = array();
        }

        if ($fileUpload instanceof UploadedFile) {

            $uploadResult = $this->handleUploadedFile($fileUpload, $pratica, $fileDescription);
            if ($uploadResult instanceof ConstraintViolationListInterface) {
                foreach ($uploadResult as $violation) {
                    $event->getForm()->addError(new FormError($violation->getMessage()));
                }
            } else {
                $fileChoices = [$uploadResult->getId()];
                $data['choose'] = null;
                $data['add'] = null;
            }
        }

        if (!empty( $fileChoices )) {
            $this->addFirstChoiceToPratica($fileChoices, $pratica);
            $this->removeOtherChoicesFromPratica($fileChoices, $pratica, $fileDescription);
        }

        if ($options['required'] && empty( $fileChoices )) {
            $event->getForm()->addError(new FormError('Il campo file è richiesto'));
        }

        $data['choose'] = isset( $fileChoices[0] ) ? $fileChoices[0] : null;

        $event->setData($data);

        $this->removeChoise($event->getForm());
        $this->addChoise($event->getForm());

    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(array('fileDescription', 'pratica'));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'choose_allegato';
    }

    /**
     * @param Pratica $pratica
     * @param $fileDescription
     *
     * @return Allegato[]
     */
    private function getCurrentAllegati(Pratica $pratica, $fileDescription)
    {
        $user = $pratica->getUser();
        $queryBuilder = $this->repository->createQueryBuilder('a');
        $queryBuilder->setCacheable(false);

        return $queryBuilder
            ->where('a.owner = :user AND a.description = :fileDescription')
            ->andWhere($queryBuilder->expr()->isInstanceOf('a', Allegato::class))
            ->andWhere(':praticaId MEMBER OF a.pratiche')
            ->setParameter('user', $user)
            ->setParameter('praticaId', $pratica->getId())
            ->setParameter('fileDescription', $fileDescription)
            ->orderBy('a.updatedAt', 'DESC')
            ->getQuery()->execute();
    }

    /**
     * @param Pratica $pratica
     * @param $fileDescription
     *
     * @return Allegato[]
     */
    private function getAllAllegati(Pratica $pratica, $fileDescription)
    {
        $user = $pratica->getUser();
        $queryBuilder = $this->repository->createQueryBuilder('a');

        return $queryBuilder
            ->where('a.owner = :user AND a.description = :fileDescription')
            ->andWhere($queryBuilder->expr()->isInstanceOf('a', Allegato::class))
            ->setParameter('user', $user)
            ->setParameter('fileDescription', $fileDescription)
            ->orderBy('a.updatedAt', 'DESC')
            ->getQuery()->execute();
    }

    /**
     * @param FormInterface $form
     */
    private function addChoise(FormInterface $form)
    {
        $options = $form->getConfig()->getOptions();

        /** @var Pratica $pratica */
        $pratica = $options['pratica'];

        $fileDescription = $options['fileDescription'];

        $fileChoices = $this->getCurrentAllegati($pratica, $fileDescription);
        $allAllegati = $this->getAllAllegati($pratica, $fileDescription);
        $form->add('choose', EntityType::class, [
            'class' => Allegato::class,
            'choices' => $allAllegati,
            'choice_label' => 'name',
            'mapped' => false,
            'expanded' => true,
            'multiple' => false,
            'required' => false,
            'data' => count($fileChoices) > 0 ? $fileChoices[0] : null,
            'label' => false,
            'placeholder' => 'Carica un nuovo file..'
        ]);
    }

    /**
     * @param FormInterface $form
     */
    private function removeChoise(FormInterface $form)
    {
        $form->remove('choose');
    }

    /**
     * @param UploadedFile $fileUpload
     * @param Pratica $pratica
     * @param $fileDescription
     *
     * @return Allegato|ConstraintViolationListInterface
     */
    private function handleUploadedFile(UploadedFile $fileUpload, Pratica $pratica, $fileDescription)
    {
        $newAllegato = new Allegato();
        $newAllegato->setFile($fileUpload);
        $newAllegato->setDescription($fileDescription);
        $newAllegato->setOwner($pratica->getUser());
        $violations = $this->validator->validate($newAllegato);

        if ($violations->count() > 0) {
            return $violations;
        } else {
            $this->entityManager->persist($newAllegato);
            $this->entityManager->flush();

            return $newAllegato;
        }
    }

    /**
     * @param array $fileChoices
     * @param Pratica $pratica
     */
    private function addFirstChoiceToPratica(array &$fileChoices, Pratica $pratica)
    {
        foreach ($fileChoices as $key => $fileChoose) {
            $allegato = $this->repository->findOneById($fileChoose);
            if ($allegato instanceof Allegato) {
                $pratica->addAllegato($allegato);
                break;
            } else {
                unset( $fileChoices[$key] );
            }
        }
    }

    /**
     * @param array $fileChoices
     * @param Pratica $pratica
     * @param $fileDescription
     */
    private function removeOtherChoicesFromPratica(array &$fileChoices, Pratica $pratica, $fileDescription)
    {
        if (!empty( $fileChoices )) {
            $currentAllegati = $this->getCurrentAllegati($pratica, $fileDescription);
            foreach ($currentAllegati as $praticaAllegato) {
                if (!in_array((string)$praticaAllegato->getId(), $fileChoices)) {
                    $pratica->removeAllegato($praticaAllegato);
                    if ($praticaAllegato->getPratiche()->isEmpty()) {
                        $this->entityManager->remove($praticaAllegato);
                        $this->entityManager->flush();
                    }
                }
            }

            $this->entityManager->persist($pratica);
            $this->entityManager->flush();
        }
    }

}
