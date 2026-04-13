<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Server;
use App\Entity\ServerTag;
use App\Entity\OperatingSystem;
use App\Entity\OperatingSystemVersion;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ServerApiController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/api/servers', name: 'api_servers_list', methods: ['GET'])]
    #[IsGranted('ROLE_API_GET')]
    public function listServers(): JsonResponse
    {
        $servers = $this->entityManager->getRepository(Server::class)->findAll();

        return new JsonResponse(array_map(fn (Server $server) => $this->serializeServer($server), $servers));
    }

    #[Route('/api/servers', name: 'api_servers_create', methods: ['POST'])]
    #[IsGranted('ROLE_API_POST')]
    public function createServer(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $server = new Server();
        $server->setName($data['name'] ?? '');
        $server->setOs($data['os'] ?? '');
        $server->setOsVersion($data['osVersion'] ?? '');
        $server->setManagementIp($data['managementIp'] ?? '');
        $server->setSshUser($data['sshUser'] ?? '');
        $server->setSshPassword($data['sshPassword'] ?? '');
        $server->setCpu($data['cpu'] ?? '');
        $server->setRam($data['ram'] ?? '');
        $server->setHd($data['hd'] ?? '');
        $server->setProvider($data['provider'] ?? '');
        $server->setSite($data['site'] ?? '');
        $server->setDescription($data['description'] ?? null);
        $server->setStatus($data['status'] ?? 'active');

        if (!empty($data['operatingSystemVersionId'])) {
            $osVersion = $this->entityManager->getRepository(OperatingSystemVersion::class)->find($data['operatingSystemVersionId']);
            if ($osVersion) {
                $server->setOperatingSystemVersion($osVersion);
            }
        }

        $this->entityManager->persist($server);
        $this->entityManager->flush();

        return new JsonResponse($this->serializeServer($server), Response::HTTP_CREATED);
    }

    #[Route('/api/servers/{id}', name: 'api_servers_show', methods: ['GET'])]
    #[IsGranted('ROLE_API_GET')]
    public function showServer(int $id): JsonResponse
    {
        $server = $this->entityManager->getRepository(Server::class)->find($id);

        if (!$server) {
            return new JsonResponse(['error' => 'Server not found'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($this->serializeServer($server));
    }

    #[Route('/api/servers/{id}', name: 'api_servers_update', methods: ['PUT'])]
    #[IsGranted('ROLE_API_PUT')]
    public function updateServer(Request $request, int $id): JsonResponse
    {
        $server = $this->entityManager->getRepository(Server::class)->find($id);

        if (!$server) {
            return new JsonResponse(['error' => 'Server not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['name'])) $server->setName($data['name']);
        if (isset($data['os'])) $server->setOs($data['os']);
        if (isset($data['osVersion'])) $server->setOsVersion($data['osVersion']);
        if (isset($data['managementIp'])) $server->setManagementIp($data['managementIp']);
        if (isset($data['sshUser'])) $server->setSshUser($data['sshUser']);
        if (isset($data['sshPassword'])) $server->setSshPassword($data['sshPassword']);
        if (isset($data['cpu'])) $server->setCpu($data['cpu']);
        if (isset($data['ram'])) $server->setRam($data['ram']);
        if (isset($data['hd'])) $server->setHd($data['hd']);
        if (isset($data['provider'])) $server->setProvider($data['provider']);
        if (isset($data['site'])) $server->setSite($data['site']);
        if (isset($data['description'])) $server->setDescription($data['description']);
        if (isset($data['status'])) $server->setStatus($data['status']);
        
        if (isset($data['operatingSystemVersionId'])) {
            if ($data['operatingSystemVersionId'] === null) {
                $server->setOperatingSystemVersion(null);
            } else {
                $osVersion = $this->entityManager->getRepository(OperatingSystemVersion::class)->find($data['operatingSystemVersionId']);
                if ($osVersion) {
                    $server->setOperatingSystemVersion($osVersion);
                }
            }
        }

        $this->entityManager->flush();

        return new JsonResponse($this->serializeServer($server));
    }

    #[Route('/api/servers/{id}', name: 'api_servers_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_API_DELETE')]
    public function deleteServer(int $id): JsonResponse
    {
        $server = $this->entityManager->getRepository(Server::class)->find($id);

        if (!$server) {
            return new JsonResponse(['error' => 'Server not found'], Response::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($server);
        $this->entityManager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/api/tags', name: 'api_tags_list', methods: ['GET'])]
    #[IsGranted('ROLE_API_GET')]
    public function listTags(): JsonResponse
    {
        $tags = $this->entityManager->getRepository(ServerTag::class)->findAll();

        return new JsonResponse(array_map(fn (ServerTag $tag) => [
            'id' => $tag->getId(),
            'name' => $tag->getName(),
            'color' => $tag->getColor(),
        ], $tags));
    }

    #[Route('/api/operating-systems', name: 'api_os_list', methods: ['GET'])]
    #[IsGranted('ROLE_API_GET')]
    public function listOperatingSystems(): JsonResponse
    {
        $oss = $this->entityManager->getRepository(OperatingSystem::class)->findAll();

        return new JsonResponse(array_map(fn (OperatingSystem $os) => [
            'id' => $os->getId(),
            'name' => $os->getName(),
            'color' => $os->getColor(),
        ], $oss));
    }

    private function serializeServer(Server $server): array
    {
        $osVersion = $server->getOperatingSystemVersion();
        return [
            'id' => $server->getId(),
            'name' => $server->getName(),
            'os' => $server->getOs(),
            'osVersion' => $server->getOsVersion(),
            'operatingSystemVersionId' => $osVersion?->getId(),
            'operatingSystemVersion' => $osVersion ? [
                'id' => $osVersion->getId(),
                'version' => $osVersion->getVersion(),
                'operatingSystemId' => $osVersion->getOperatingSystem()?->getId(),
            ] : null,
            'managementIp' => $server->getManagementIp(),
            'sshUser' => $server->getSshUser(),
            'sshPassword' => $server->getSshPassword(),
            'cpu' => $server->getCpu(),
            'ram' => $server->getRam(),
            'hd' => $server->getHd(),
            'provider' => $server->getProvider(),
            'site' => $server->getSite(),
            'description' => $server->getDescription(),
            'status' => $server->getStatus(),
            'tags' => $server->getTags()->map(fn (ServerTag $tag) => [
                'id' => $tag->getId(),
                'name' => $tag->getName(),
                'color' => $tag->getColor(),
            ])->toArray(),
        ];
    }
}