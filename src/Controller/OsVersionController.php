<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\OperatingSystem;
use App\Entity\OperatingSystemVersion;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class OsVersionController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/api/operating-systems/{osId}/versions', name: 'api_os_versions_list', methods: ['GET'])]
    public function listByOperatingSystem(int $osId): JsonResponse
    {
        $os = $this->entityManager->getRepository(OperatingSystem::class)->find($osId);
        
        if (!$os) {
            return new JsonResponse(['error' => 'Operating system not found'], Response::HTTP_NOT_FOUND);
        }

        $versions = $this->entityManager->getRepository(OperatingSystemVersion::class)->findByOperatingSystem($osId);

        return new JsonResponse(array_map(fn (OperatingSystemVersion $version) => [
            'id' => $version->getId(),
            'version' => $version->getVersion(),
            'color' => $version->getColor(),
        ], $versions));
    }

    #[Route('/api/operating-systems/{osId}/versions', name: 'api_os_versions_create', methods: ['POST'])]
    #[IsGranted('ROLE_API_POST')]
    public function create(int $osId, Request $request): JsonResponse
    {
        $os = $this->entityManager->getRepository(OperatingSystem::class)->find($osId);
        
        if (!$os) {
            return new JsonResponse(['error' => 'Operating system not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        
        if (empty($data['version'])) {
            return new JsonResponse(['error' => 'Version is required'], Response::HTTP_BAD_REQUEST);
        }

        $version = new OperatingSystemVersion();
        $version->setOperatingSystem($os);
        $version->setVersion($data['version']);
        $version->setColor($data['color'] ?? 'info');

        $this->entityManager->persist($version);
        $this->entityManager->flush();

        return new JsonResponse([
            'id' => $version->getId(),
            'version' => $version->getVersion(),
            'color' => $version->getColor(),
        ], Response::HTTP_CREATED);
    }

    #[Route('/api/operating-systems/versions/{id}', name: 'api_os_versions_update', methods: ['PUT'])]
    #[IsGranted('ROLE_API_PUT')]
    public function update(int $id, Request $request): JsonResponse
    {
        $version = $this->entityManager->getRepository(OperatingSystemVersion::class)->find($id);
        
        if (!$version) {
            return new JsonResponse(['error' => 'Version not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        
        if (isset($data['version'])) {
            $version->setVersion($data['version']);
        }
        if (isset($data['color'])) {
            $version->setColor($data['color']);
        }

        $version->setUpdatedAt(new \DateTimeImmutable());
        $this->entityManager->flush();

        return new JsonResponse([
            'id' => $version->getId(),
            'version' => $version->getVersion(),
            'color' => $version->getColor(),
        ]);
    }

    #[Route('/api/operating-systems/versions/{id}', name: 'api_os_versions_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_API_DELETE')]
    public function delete(int $id): JsonResponse
    {
        $version = $this->entityManager->getRepository(OperatingSystemVersion::class)->find($id);
        
        if (!$version) {
            return new JsonResponse(['error' => 'Version not found'], Response::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($version);
        $this->entityManager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}