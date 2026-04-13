<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Server;
use App\Entity\Service;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:generate-test-data',
    description: 'Generate test users, services and 20 test servers',
)]
final class GenerateTestDataCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->section('Creating test users...');

        $user1 = new User();
        $user1->setEmail('user1@test.com');
        $user1->setRoles(['ROLE_USER']);
        $user1->setPassword($this->passwordHasher->hashPassword($user1, 'password123'));
        $user1->setStatus('enabled');
        $this->entityManager->persist($user1);

        $user2 = new User();
        $user2->setEmail('user2@test.com');
        $user2->setRoles(['ROLE_SERVICE_ADMIN']);
        $user2->setPassword($this->passwordHasher->hashPassword($user2, 'password123'));
        $user2->setStatus('enabled');
        $this->entityManager->persist($user2);

        $user3 = new User();
        $user3->setEmail('admin@test.com');
        $user3->setRoles(['ROLE_ADMIN']);
        $user3->setPassword($this->passwordHasher->hashPassword($user3, 'password123'));
        $user3->setStatus('enabled');
        $this->entityManager->persist($user3);

        $io->text('Created 3 users: user1@test.com, user2@test.com, admin@test.com (password: password123)');

        $io->section('Creating services...');

        $servicesData = [
            'nginx' => 'Web Server',
            'apache' => 'Web Server',
            'mysql' => 'Database',
            'postgresql' => 'Database',
            'mongodb' => 'Database',
            'redis' => 'Cache',
            'memcached' => 'Cache',
            'php-fpm' => 'PHP Runtime',
            'nodejs' => 'Node Runtime',
            'docker' => 'Container',
            'kubernetes' => 'Orchestration',
            'prometheus' => 'Monitoring',
            'grafana' => 'Monitoring',
            'elasticsearch' => 'Search',
            'rabbitmq' => 'Message Queue',
        ];

        $services = [];
        foreach ($servicesData as $name => $description) {
            $service = new Service();
            $service->setName($name);
            $this->entityManager->persist($service);
            $services[] = $service;
            $io->text("- $name");
        }

        $io->section('Creating 20 test servers...');

        $serversData = [
            ['name' => 'web-prod-01', 'os' => 'Ubuntu', 'version' => '22.04', 'provider' => 'AWS', 'site' => 'Production', 'cpu' => '8 cores', 'ram' => '16GB', 'hd' => '500GB SSD'],
            ['name' => 'web-prod-02', 'os' => 'Ubuntu', 'version' => '22.04', 'provider' => 'AWS', 'site' => 'Production', 'cpu' => '8 cores', 'ram' => '16GB', 'hd' => '500GB SSD'],
            ['name' => 'db-prod-01', 'os' => 'Ubuntu', 'version' => '22.04', 'provider' => 'AWS', 'site' => 'Production', 'cpu' => '16 cores', 'ram' => '32GB', 'hd' => '1TB SSD'],
            ['name' => 'db-prod-02', 'os' => 'Ubuntu', 'version' => '22.04', 'provider' => 'AWS', 'site' => 'Production', 'cpu' => '16 cores', 'ram' => '32GB', 'hd' => '1TB SSD'],
            ['name' => 'cache-prod-01', 'os' => 'Ubuntu', 'version' => '22.04', 'provider' => 'AWS', 'site' => 'Production', 'cpu' => '4 cores', 'ram' => '8GB', 'hd' => '100GB SSD'],
            ['name' => 'web-staging-01', 'os' => 'Ubuntu', 'version' => '22.04', 'provider' => 'Azure', 'site' => 'Staging', 'cpu' => '4 cores', 'ram' => '8GB', 'hd' => '200GB SSD'],
            ['name' => 'db-staging-01', 'os' => 'Ubuntu', 'version' => '22.04', 'provider' => 'Azure', 'site' => 'Staging', 'cpu' => '8 cores', 'ram' => '16GB', 'hd' => '500GB SSD'],
            ['name' => 'monitoring-01', 'os' => 'Ubuntu', 'version' => '22.04', 'provider' => 'DigitalOcean', 'site' => 'Production', 'cpu' => '4 cores', 'ram' => '8GB', 'hd' => '100GB SSD'],
            ['name' => 'backup-01', 'os' => 'Ubuntu', 'version' => '22.04', 'provider' => 'DigitalOcean', 'site' => 'Production', 'cpu' => '4 cores', 'ram' => '4GB', 'hd' => '2TB HDD'],
            ['name' => 'vpn-01', 'os' => 'Ubuntu', 'version' => '22.04', 'provider' => 'Vultr', 'site' => 'Production', 'cpu' => '2 cores', 'ram' => '2GB', 'hd' => '50GB SSD'],
            ['name' => 'mail-01', 'os' => 'Ubuntu', 'version' => '22.04', 'provider' => 'Hetzner', 'site' => 'Production', 'cpu' => '4 cores', 'ram' => '8GB', 'hd' => '200GB SSD'],
            ['name' => 'api-prod-01', 'os' => 'Ubuntu', 'version' => '22.04', 'provider' => 'AWS', 'site' => 'Production', 'cpu' => '8 cores', 'ram' => '16GB', 'hd' => '500GB SSD'],
            ['name' => 'api-prod-02', 'os' => 'Ubuntu', 'version' => '22.04', 'provider' => 'AWS', 'site' => 'Production', 'cpu' => '8 cores', 'ram' => '16GB', 'hd' => '500GB SSD'],
            ['name' => 'ci-runner-01', 'os' => 'Ubuntu', 'version' => '22.04', 'provider' => 'Azure', 'site' => 'Production', 'cpu' => '8 cores', 'ram' => '16GB', 'hd' => '500GB SSD'],
            ['name' => 'dev-server-01', 'os' => 'Ubuntu', 'version' => '22.04', 'provider' => 'DigitalOcean', 'site' => 'Development', 'cpu' => '4 cores', 'ram' => '8GB', 'hd' => '100GB SSD'],
            ['name' => 'dev-server-02', 'os' => 'Ubuntu', 'version' => '22.04', 'provider' => 'DigitalOcean', 'site' => 'Development', 'cpu' => '4 cores', 'ram' => '8GB', 'hd' => '100GB SSD'],
            ['name' => 'windows-ad-01', 'os' => 'Windows Server', 'version' => '2022', 'provider' => 'Azure', 'site' => 'Production', 'cpu' => '4 cores', 'ram' => '16GB', 'hd' => '500GB SSD'],
            ['name' => 'centos-app-01', 'os' => 'CentOS', 'version' => '8', 'provider' => 'Vultr', 'site' => 'Production', 'cpu' => '4 cores', 'ram' => '8GB', 'hd' => '200GB SSD'],
            ['name' => 'rocky-db-01', 'os' => 'Rocky Linux', 'version' => '9', 'provider' => 'Hetzner', 'site' => 'Production', 'cpu' => '8 cores', 'ram' => '16GB', 'hd' => '500GB SSD'],
            ['name' => 'debian-proxy-01', 'os' => 'Debian', 'version' => '12', 'provider' => 'AWS', 'site' => 'Production', 'cpu' => '2 cores', 'ram' => '4GB', 'hd' => '50GB SSD'],
        ];

        $statuses = ['active', 'active', 'active', 'inactive', 'maintenance'];

        foreach ($serversData as $index => $data) {
            $server = new Server();
            $server->setName($data['name']);
            $server->setOs($data['os']);
            $server->setOsVersion($data['version']);
            $server->setManagementIp("192.168.1." . ($index + 10));
            $server->setSshUser('admin');
            $server->setSshPassword('SecurePass' . ($index + 1) . '!');
            $server->setCpu($data['cpu']);
            $server->setRam($data['ram']);
            $server->setHd($data['hd']);
            $server->setProvider($data['provider']);
            $server->setSite($data['site']);
            $server->setDescription("{$data['site']} {$data['os']} server hosted on {$data['provider']}");
            $server->setStatus($statuses[array_rand($statuses)]);

            $randomServices = array_slice($services, 0, rand(2, 5));
            foreach ($randomServices as $service) {
                $server->addService($service);
            }

            $this->entityManager->persist($server);
            $io->text("- {$data['name']} ({$data['os']} {$data['version']})");
        }

        $this->entityManager->flush();

        $io->success('Test data generated successfully!');
        $io->table(
            ['User', 'Password', 'Role'],
            [
                ['user1@test.com', 'password123', 'ROLE_USER'],
                ['user2@test.com', 'password123', 'ROLE_SERVICE_ADMIN'],
                ['admin@test.com', 'password123', 'ROLE_ADMIN'],
            ]
        );

        return Command::SUCCESS;
    }
}
