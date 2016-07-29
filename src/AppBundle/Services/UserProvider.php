<?php

namespace AppBundle\Services;

use AppBundle\Entity\User;
use AppBundle\Logging\LogConstants;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Class UserProvider
 * @package AppBundle\Services
 */
class UserProvider implements UserProviderInterface
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * UserProvider constructor.
     * @param EntityManager   $em
     * @param LoggerInterface $logger
     */
    public function __construct(EntityManager $em, LoggerInterface $logger)
    {
        $this->em = $em;
        $this->logger = $logger;
    }

    /**
     * @param string $username
     * @return User^
     */
    public function loadUserByUsername($username):User
    {
        $user = $this->getPersistedUser($username);
        if (!$user) {
            $user = $this->createUser($username);
        }

        return $user;
    }

    /**
     * @param UserInterface $user
     * @return User
     */
    public function refreshUser(UserInterface $user):User
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(
                sprintf('Instances of "%s" are not supported.', get_class($user))
            );
        }

        return $this->loadUserByUsername($user->getUsername());
    }

    /**
     * @param string $class
     * @return bool
     */
    public function supportsClass($class)
    {
        return $class == User::class;
    }

    private function getPersistedUser($username)
    {
        $repo = $this->em->getRepository('AppBundle:User');
        try {
            $user = $repo->findOneBy(array('name' => $username));
        } catch (\Exception $e) {
            $user = null;
        }

        return $user;
    }

    private function createUser($username):User
    {
        $user = new User();
        $user->setName($username)
            ->setUsername($username)
            ->addRole('ROLE_USER')
            ->setEmail($user->getId().'@'.User::FAKE_EMAIL_DOMAIN)
            ->setPlainPassword('pippo')
            ->addRole('ROLE_CPS_USER')
            ->setEnabled(true);
        $this->em->persist($user);

        $this->logger->info(
            LogConstants::CPS_USER_CREATED_WITH_BOGUS_DATA,
            [
                'user' => $user,
                'passed_username' => $username,
            ]
        );

        $this->em->flush();

        return $user;
    }

}
