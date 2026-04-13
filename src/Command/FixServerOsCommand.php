<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\OperatingSystemVersion;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FixServerOsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('app:fix-server-os');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $servers = $this->em->getRepository(\App\Entity\Server::class)->findAll();
        $updated = 0;

        foreach ($servers as $server) {
            $os = $server->getOs();
            $osVersion = $server->getOsVersion();

            if ($os && $osVersion && !$server->getOperatingSystemVersion()) {
                $osVersionEntity = $this->em->getRepository(OperatingSystemVersion::class)->findOneBy([
                    'version' => $osVersion,
                ]);

                if ($osVersionEntity) {
                    $server->setOperatingSystemVersion($osVersionEntity);
                    $updated++;
                }
            }
        }

        $this->em->flush();
        $output->writeln("Updated $updated servers");

        return Command::SUCCESS;
    }
}