<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    private $encoder;

    public function __construct(ManagerRegistry $registry, UserPasswordEncoderInterface $encoder)
    {
        $this->encoder = $encoder;
        parent::__construct($registry, $this->getUserClass());
    }

    protected function getUserClass()
    {
        return User::class;
    }

    /**
     * @param UserInterface $user
     * @param string $newEncodedPassword
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function upgradePassword(UserInterface $user, string $newEncodedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }
        if ($user->getPlainPassword() !== null) {
            $user->setPassword($this->encoder->encodePassword($user, $user->getPlainPassword()));

            $this->_em->persist($user);
            $this->_em->flush();
        }
    }

    /**
     * @param User $user
     * @param bool $andFlush
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function updateUser(User $user, $andFlush = true)
    {
        $this->updateCanonicalFields($user);

        if ($user->getPlainPassword() !== null) {
            $hashedPassword = $this->encoder->encodePassword($user, $user->getPlainPassword());
            $user->setPassword($hashedPassword);
        }

        $this->_em->persist($user);
        if ($andFlush) {
            $this->_em->flush();
        }
    }

    private function updateCanonicalFields(User $user)
    {
        $user->setUsernameCanonical(self::canonicalize($user->getUsername()));
        $user->setEmailCanonical(self::canonicalize($user->getEmail()));
    }

    /**
     * @param $string
     * @return string|null
     */
    public static function canonicalize($string)
    {
        if (null === $string) {
            return null;
        }

        $encoding = mb_detect_encoding($string);
        $result = $encoding
            ? mb_convert_case($string, MB_CASE_LOWER, $encoding)
            : mb_convert_case($string, MB_CASE_LOWER);

        return $result;
    }


    public function findUserBy(array $criteria)
    {
        return $this->findOneBy($criteria);
    }


    public function findUsers()
    {
        return $this->findAll();
    }

    public function findUserByEmail($email)
    {
        return $this->findUserBy(array('emailCanonical' => self::canonicalize($email)));
    }

    public function findUserByUsername($username)
    {
        return $this->findUserBy(array('usernameCanonical' => self::canonicalize($username)));
    }


    public function findUserByUsernameOrEmail($usernameOrEmail)
    {
        if (preg_match('/^.+\@\S+\.\S+$/', $usernameOrEmail)) {
            $user = $this->findUserByEmail($usernameOrEmail);
            if (null !== $user) {
                return $user;
            }
        }

        return $this->findUserByUsername($usernameOrEmail);
    }


    public function findUserByConfirmationToken($token)
    {
        return $this->findUserBy(array('confirmationToken' => $token));
    }
}
