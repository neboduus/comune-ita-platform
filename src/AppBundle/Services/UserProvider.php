<?php

namespace AppBundle\Services;

use AppBundle\Entity\User;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class UserProvider implements UserProviderInterface
{
    /**
     * @var EntityManager
     */
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function loadUserByUsername($username)
    {
        $user = $this->getPersistedUser($username);
        if (!$user) {
            $user = $this->createUser($username);
        }

        return $user;
    }

    protected function getPersistedUser($username)
    {
        $repo = $this->em->getRepository('AppBundle:User');
        try {
            $user = $repo->findOneBy(array('name' => $username));
        } catch (\Exception $e) {
            $user = null;
        }

        return $user;
    }

    protected function createUser($username)
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
        $this->em->flush();

        return $user;
    }

    public function refreshUser(UserInterface $user)
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(
                sprintf('Instances of "%s" are not supported.', get_class($user))
            );
        }

        return $this->loadUserByUsername($user->getUsername());
    }

    public function supportsClass($class)
    {
        return $class == '\AppBundle\Entity\User';
    }
}
