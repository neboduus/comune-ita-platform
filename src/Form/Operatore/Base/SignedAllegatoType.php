<?php

namespace App\Form\Operatore\Base;

use App\Entity\Allegato;
use App\Entity\Pratica;
use App\Form\Base\ChooseAllegatoType;
use App\Services\P7MSignatureCheckService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SignedAllegatoType extends ChooseAllegatoType
{
    /**
     * @var P7MSignatureCheckService
     */
    protected $p7mCheckerService;

    /**
     * ChooseAllegatoType constructor.
     *
     * @param EntityManager $entityManager
     * @param ValidatorInterface $validator
     * @param P7MSignatureCheckService $service
     */
    public function __construct(EntityManagerInterface $entityManager, ValidatorInterface $validator, P7MSignatureCheckService $service)
    {
        parent::__construct($entityManager, $validator);
        $this->p7mCheckerService = $service;
    }

    /**
     * @param UploadedFile $fileUpload
     * @param Pratica $pratica
     * @param $fileDescription
     * @param $class
     * @return Allegato|ConstraintViolationListInterface
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    protected function handleUploadedFile(UploadedFile $fileUpload, Pratica $pratica, $fileDescription, $class)
    {
        /** @var Allegato $newAllegato */
        $newAllegato = new $class();
        $newAllegato->setFile($fileUpload);
        $newAllegato->setDescription($fileDescription);
        $newAllegato->setOwner($pratica->getUser());
        //$violations = $this->validator->validate($newAllegato);
        $violations = $this->validator->validate(
            $this->p7mCheckerService->check($fileUpload->getPathname()),
            new IsTrue(['message' => 'Il file non è p7m'])
        );

        if ($violations->count() > 0) {
            return $violations;
        } elseif (!$this->p7mCheckerService->check($fileUpload->getPathname())) {
            $violations = $this->validator->validate(
                $this->p7mCheckerService->check($fileUpload->getPathname()),
                new IsTrue(['message' => 'Il file non è p7m'])
            );
            return $violations;
        } else {
            $this->entityManager->persist($newAllegato);
            $this->entityManager->flush();

            return $newAllegato;
        }
    }

    /**
     * @param Pratica $pratica
     * @param $fileDescription
     *
     * @return Allegato[]
     */
    protected function getAllAllegati(Pratica $pratica, $fileDescription, $class)
    {
        $user = $pratica->getUser();
        $queryBuilder = $this->entityManager->getRepository($class)->createQueryBuilder('a');

        return $queryBuilder
            ->where('a.owner = :user AND a.description = :fileDescription')
            ->andWhere($queryBuilder->expr()->isInstanceOf('a', $class))
            ->andWhere($queryBuilder->expr()->isNull('a.numeroProtocollo'))
            ->setParameter('user', $user)
            ->setParameter('fileDescription', $fileDescription)
            ->orderBy('a.updatedAt', 'DESC')
            ->getQuery()->execute();
    }
}
