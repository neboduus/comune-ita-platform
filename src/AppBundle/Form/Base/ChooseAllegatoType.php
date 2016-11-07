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
        $this->addChoice($event->getForm());
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
        $purgeFiles = $options['purge_files'];

        $data = $event->getData();

        $fileUpload = $data['add'] ?? null;
        if (isset( $data['choose'] ) && $data['choose'] != '') {
            $fileChoices = (array)$data['choose'];
        } else {
            $fileChoices = array();
        }

        $hasNewFile = false;

        if ($fileUpload instanceof UploadedFile) {

            $uploadResult = $this->handleUploadedFile($fileUpload, $pratica, $fileDescription);
            if ($uploadResult instanceof ConstraintViolationListInterface) {
                foreach ($uploadResult as $violation) {
                    $event->getForm()->addError(new FormError($violation->getMessage()));
                }
            } else {
                $hasNewFile = $uploadResult->getId();
                $newFileList = [$hasNewFile];
                $this->addChoiceListToPratica($newFileList, $pratica, $fileDescription, $purgeFiles);
            }
        } elseif (!empty( $fileChoices )) {
            $this->addChoiceListToPratica($fileChoices, $pratica, $fileDescription, $purgeFiles);
        }

        if ($options['required']){
            if ($hasNewFile) {
                $event->getForm()->addError(new FormError('Il file è stato caricato correttamente'));
                $data['choose'] = $hasNewFile;
                $event->setData($data);
            }elseif(empty( $fileChoices )) {
                $event->getForm()->addError(new FormError('Il campo file è richiesto'));
            }
        }

        $this->removeChoice($event->getForm());
        $this->addChoice($event->getForm());

    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'purge_files' => false,
        ))->setRequired(array(
            'fileDescription',
            'pratica'
        ));
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
    private function addChoice(FormInterface $form)
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
            'required' => $options['required'] && count($fileChoices) > 0,
            'data' => count($fileChoices) > 0 ? $fileChoices[0] : null,
            'label' => false,
            'placeholder' => 'Carica un nuovo file..'
        ]);
    }

    /**
     * @param FormInterface $form
     */
    private function removeChoice(FormInterface $form)
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
     * @param $fileDescription
     * @param bool $purgeFiles
     */
    private function addChoiceListToPratica(
        array &$fileChoices,
        Pratica $pratica,
        $fileDescription,
        $purgeFiles = false
    ) {
        foreach ($fileChoices as $key => $fileChoose) {
            $allegato = $this->repository->findOneById($fileChoose);
            if ($allegato instanceof Allegato) {
                $pratica->addAllegato($allegato);
                break;
            }
        }

        if (!empty( $fileChoices )) {
            $currentAllegati = $this->getCurrentAllegati($pratica, $fileDescription);
            foreach ($currentAllegati as $praticaAllegato) {
                if (!in_array((string)$praticaAllegato->getId(), $fileChoices)) {
                    $pratica->removeAllegato($praticaAllegato);
                    if ($purgeFiles && $praticaAllegato->getPratiche()->isEmpty()) {
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
