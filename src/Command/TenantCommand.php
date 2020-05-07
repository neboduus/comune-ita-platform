<?php

namespace App\Command;

use App\Multitenancy\Entity\Main\Tenant;
use Doctrine\Common\Inflector\Inflector;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Serializer\SerializerInterface;

class TenantCommand extends Command
{
    private $doctrine;

    private $serializer;

    public function __construct(ManagerRegistry $doctrine, SerializerInterface $serializer)
    {
        $this->doctrine = $doctrine;
        $this->serializer = $serializer;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('tenants')
            ->setDescription('List available tenants stored in main db')
            ->addOption('add', '', InputOption::VALUE_NONE, 'Add instance (interactive)')
            ->addOption('create-db', '', InputOption::VALUE_REQUIRED, 'Create db for selected instance')
            ->addOption('remove', '', InputOption::VALUE_REQUIRED, 'Remove instance by id (interactive)');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $fields = $this->doctrine->getManagerForClass(Tenant::class)->getClassMetadata(Tenant::class)->getFieldNames();
        $symfonyStyle = new SymfonyStyle($input, $output);

        if ($input->getOption('create-db')) {

            /** @var Tenant $tenant */
            $tenant = $this->doctrine->getRepository(Tenant::class)->findOneBy(['slug' => $input->getOption('create-db')]);
            if ($tenant instanceof Tenant) {
                $this->createDB($tenant->getDbName(), $output);
            }

        } elseif ($input->getOption('add')) {

            /** @var Tenant $tenant */
            $tenant = new Tenant();
            foreach ($fields as $field) {
                if ($field !== 'id') {
                    $suggestion = '';
                    $setter = 'set' . Inflector::classify($field);
                    if (method_exists($tenant, $setter)) {
                        $tenant->{$setter}($symfonyStyle->ask("$field?", $suggestion));
                    }
                }
            }
            $this->doctrine->getManager('main')->persist($tenant);
            $this->doctrine->getManager('main')->flush();
            $output->writeln("Tenant {$tenant->getName()} created");

            if ('y' === $symfonyStyle->ask("Create db {$tenant->getDbName()} in default db server?", 'y/n')) {
                $this->createDB($tenant->getDbName(), $output);
            }

        } elseif ($deleteId = $input->getOption('remove')) {
            /** @var Tenant $tenant */
            $tenant = $this->doctrine->getRepository(Tenant::class)->findOneBy(['id' => $deleteId]);
            if ('y' === $symfonyStyle->ask("Remove {$tenant->getName()} from main db?", 'y/n')) {

                $dropDB = false;
                if ('y' === $symfonyStyle->ask("Drop db {$tenant->getDbName()} in default db server?", 'y/n')) {
                    if ('y' === $symfonyStyle->ask("Really drop db {$tenant->getDbName()} in default db server???", 'y/n')) {
                        $dropDB = true;
                    }
                }

                $dbName = $tenant->getDbName();
                $this->doctrine->getManager('main')->remove($tenant);
                $this->doctrine->getManager('main')->flush();

                if ($dropDB) {
                    $this->dropDB($dbName, $output);
                }
            }
        } else {

            /** @var Tenant[] $tenants */
            $tenants = $this->doctrine->getRepository(Tenant::class)->findAll();

            if ($input->getOption('verbose')) {
                $table = new Table($output);

                $table->setHeaders($fields);
                $rows = [];
                foreach ($tenants as $tenant) {
                    $rows[] = array_values(
                        json_decode(
                            $this->serializer->serialize($tenant, 'json'), true
                        )
                    );
                }
                $table->setRows($rows);
                $table->render();
            } else {
                foreach ($tenants as $tenant) {
                    $output->writeln($tenant->getSlug());
                }
            }
        }

        return 0;
    }

    private function createDB($name, OutputInterface $output)
    {
        $connection = $this->doctrine->getConnection('main');
        try {
            $connection->getSchemaManager()->createDatabase($name);
            $output->writeln(sprintf('<info>Created database <comment>%s</comment> for connection named <comment>%s</comment></info>', $name, 'tenant'));

            return 0;

        } catch (\Exception $e) {
            $output->writeln(sprintf('<error>Could not create database <comment>%s</comment> for connection named <comment>%s</comment></error>', $name, 'tenant'));
            $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));

            return 1;
        }
    }

    private function dropDB($name, OutputInterface $output)
    {
        $connection = $this->doctrine->getConnection('main');
        try {
            $connection->getSchemaManager()->dropDatabase($name);
            $output->writeln(sprintf('<info>Dropped database <comment>%s</comment> for connection named <comment>%s</comment></info>', $name, 'tenant'));

            return 0;

        } catch (\Exception $e) {
            $output->writeln(sprintf('<error>Could not drop database <comment>%s</comment> for connection named <comment>%s</comment></error>', $name, 'tenant'));
            $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));

            return 1;
        }
    }
}
