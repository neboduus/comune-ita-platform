<?php

namespace App\Command;

use App\Repository\UserRepository;
use App\DataFixtures\Tenant\LoadDataFixtures;
use App\Entity\AdminUser;
use App\Entity\Ente;
use App\Entity\PaymentGateway;
use App\Model\Gateway;
use App\Services\InstanceService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Cache\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ConfigureInstanceCommand extends BaseCommand
{
    /**
     * @var SymfonyStyle
     */
    private $symfonyStyle;

    private $manager;

    private $loader;

    private $userManager;

    private $instanceService;

    public function __construct(EntityManagerInterface $manager, LoadDataFixtures $loadDataFixtures, UserRepository $userManager, InstanceService $instanceService)
    {
        $this->manager = $manager;
        $this->loader = $loadDataFixtures;
        $this->userManager = $userManager;
        $this->instanceService = $instanceService;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('ocsdc:configure-instance')
            ->setDescription("Configurazione iniziale di un'istanza");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->symfonyStyle = new SymfonyStyle($input, $output);

        $manager = $this->manager;
        $loader = $this->loader;
        $loader->loadPaymentGateways($manager);

        $repo = $manager->getRepository('App:Ente');
        $ente = $repo->findOneBy(['slug' => $this->instanceService->getSlug()]);
        $isEnte = $ente instanceof Ente;

        $this->symfonyStyle->title("Inserisci i dati per la configurazione dell'istanza");
        $suggestion = $isEnte ? $ente->getName() : $this->instanceService->getTenant()->getName();
        $name = $this->symfonyStyle->ask("Inserisci il nome dell'ente: ", $suggestion);

        $suggestion = $isEnte ? $ente->getCodiceMeccanografico() : $this->instanceService->getTenant()->getCodiceMeccanografico();
        $codeMec = $this->symfonyStyle->ask("Inserisci il codice Meccanografico: ", $suggestion);

        $suggestion = $isEnte ? $ente->getCodiceAmministrativo() : '';
        $codeAdm = $this->symfonyStyle->ask("Inserisci il codice Amministrativo: ", $suggestion);

        $suggestion = $isEnte ? $ente->getSiteUrl() : '';
        $url = $this->symfonyStyle->ask("Inserisci url del sito comunale: ", $suggestion);

        if (!$isEnte) {
            $ente = new Ente();
        }
        $ente
            ->setName($name)
            ->setCodiceMeccanografico($codeMec)
            ->setCodiceAmministrativo($codeAdm)
            ->setSiteUrl($url);

        /** @var PaymentGateway $bollo */
        $bollo = $manager->getRepository('App:PaymentGateway')->findOneBy(['identifier' => 'bollo']);
        $gateway = new Gateway();
        $gateway->setIdentifier($bollo->getIdentifier());
        $gateway->setParameters(['identifier' => $bollo->getIdentifier(), 'parameters' => null]);

        $ente->setGateways(['bollo' => $gateway]);

        $manager->persist($ente);
        $manager->flush();

        $loader->loadCategories($manager);
        $loader->loadServizi($manager);
        $loader->loadTerminiUtilizzo($manager);

        if ($ente) {
            $this->createAdmin();
        }

        $this->symfonyStyle->success("Istanza configurata con successo");

        return 0;
    }

    private function createAdmin()
    {
        $this->symfonyStyle->title("Inserisci i dati per la creazione di un admin");
        $nome = $this->symfonyStyle->ask("Inserisci il nome: ", 'Opencontent');
        $cognome = $this->symfonyStyle->ask('Inserisci il cognome: ', 'Scarl');
        $email = $this->symfonyStyle->ask('Inserisci l\'indirizzo email: ', 'support@opencontent.it');
        $username = $this->symfonyStyle->ask('Inserisci lo username: ', 'admin');
        $password = $this->symfonyStyle->ask('Inserisci la password: ', '');

        $user = (new AdminUser())
            ->setUsername($username)
            ->setPlainPassword($password)
            ->setEmail($email)
            ->setNome($nome)
            ->setCognome($cognome)
            ->setEnabled(true);

        try {
            $this->userManager->updateUser($user);
            $this->symfonyStyle->text('Generato nuovo admin ' . $user->getFullName());
        } catch (\Exception $e) {
            $this->symfonyStyle->text('Errore: ' . $e->getMessage());
        }
    }
}
