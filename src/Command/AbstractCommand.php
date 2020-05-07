<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Services\InstanceService;
use Symfony\Component\Console\Command\Command;

abstract class AbstractCommand extends Command
{
    /**
     * @var UserRepository
     */
    protected $userManager;

    /**
     * @var EntityManagerInterface
     */
    protected $em;

    /**
     * @var InstanceService
     */
    protected $instanceService;

    public function __construct(EntityManagerInterface $manager, InstanceService $instanceService, UserRepository $userManager)
    {
        $this->em = $manager;
        $this->instanceService = $instanceService;
        $this->userManager = $this->em->getRepository(User::class);

        parent::__construct();
    }
}
