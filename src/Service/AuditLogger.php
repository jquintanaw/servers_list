<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Server;
use App\Entity\ServerLog;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class AuditLogger
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
        private string $logDirectory = '/var/log/dashboard'
    ) {
    }

    public function log(
        Server $server,
        UserInterface $user,
        string $action,
        ?array $oldData = null,
        ?array $newData = null
    ): void {
        $details = $this->buildDetails($action, $oldData, $newData);

        $this->saveToDatabase($server, $user, $action, $details);
        $this->saveToFile($server, $user, $action, $details);
    }

    private function buildDetails(string $action, ?array $oldData, ?array $newData): string
    {
        $details = ['action' => $action];

        if ($oldData !== null) {
            $details['before'] = $this->sanitizeData($oldData);
        }

        if ($newData !== null) {
            $details['after'] = $this->sanitizeData($newData);
        }

        return json_encode($details, JSON_PRETTY_PRINT);
    }

    private function sanitizeData(array $data): array
    {
        $sensitiveFields = ['sshPassword', 'password'];

        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = '***HIDDEN***';
            }
        }

        return $data;
    }

    private function saveToDatabase(Server $server, UserInterface $user, string $action, string $details): void
    {
        $log = new ServerLog();
        $log->setServer($server);
        $log->setUser($user instanceof User ? $user : $this->getAnonymousUser());
        $log->setAction($action);
        $log->setDetails($details);

        $this->entityManager->persist($log);
        $this->entityManager->flush();
    }

    private function saveToFile(Server $server, UserInterface $user, string $action, string $details): void
    {
        $filename = sprintf(
            '%s/server_%d_%s_%s.log',
            $this->logDirectory,
            $server->getId(),
            $action,
            (new \DateTimeImmutable())->format('Y-m-d_His')
        );

        $content = sprintf(
            "[%s] Server: %s (ID: %d) | User: %s | Action: %s\n%s\n",
            (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            $server->getName(),
            $server->getId(),
            $user->getUserIdentifier(),
            $action,
            $details
        );

        $this->logger->info('Audit log', [
            'file' => $filename,
            'server_id' => $server->getId(),
            'action' => $action,
            'user' => $user->getUserIdentifier(),
        ]);

        if (!is_dir($this->logDirectory)) {
            @mkdir($this->logDirectory, 0755, true);
        }

        @file_put_contents($filename, $content, FILE_APPEND);
    }

    private function getAnonymousUser(): User
    {
        $user = new User();
        $user->setEmail('anonymous');
        $user->setPassword('');
        $user->setStatus('enabled');
        return $user;
    }
}
