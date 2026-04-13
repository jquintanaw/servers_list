<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Server;
use App\Entity\OperatingSystem;
use App\Entity\OperatingSystemVersion;
use App\Repository\ServerRepository;
use App\Service\AuditLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/servers')]
#[IsGranted('ROLE_USER')]
class ServerController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ServerRepository $serverRepository,
        private AuditLogger $auditLogger
    ) {
    }

    #[Route('', name: 'app_servers')]
    public function index(Request $request): Response
    {
        $name = $request->query->get('name');
        $os = $request->query->get('os');
        $osVersion = $request->query->get('osVersion');
        $description = $request->query->get('description');
        $limit = (int) $request->query->get('limit', 10);
        $page = (int) $request->query->get('page', 1);

        $limit = in_array($limit, [10, 20, 50]) ? $limit : 10;
        $page = max(1, $page);
        $offset = ($page - 1) * $limit;

        $totalServers = $this->serverRepository->countWithFilters($name, $os, $osVersion, $description);
        $totalPages = ceil($totalServers / $limit);

        $servers = $this->serverRepository->findWithFilters($name, $os, $osVersion, $description, $limit, $offset);
        $distinctOs = $this->serverRepository->findDistinctOs();
        $distinctOsVersions = $this->serverRepository->findDistinctOsVersions();

        return $this->render('server/index.html.twig', [
            'servers' => $servers->getQuery()->getResult(),
            'filters' => [
                'name' => $name,
                'os' => $os,
                'osVersion' => $osVersion,
                'description' => $description,
            ],
            'osOptions' => $distinctOs,
            'osVersionOptions' => $distinctOsVersions,
            'pagination' => [
                'limit' => $limit,
                'page' => $page,
                'totalPages' => $totalPages,
                'totalServers' => $totalServers,
            ],
        ]);
    }

    #[Route('/new', name: 'app_servers_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_SERVICE_ADMIN')]
    public function new(Request $request): Response
    {
        $server = new Server();

        if ($request->isMethod('POST')) {
            try {
                $this->updateServerFromRequest($server, $request);

                $this->entityManager->persist($server);
                $this->entityManager->flush();

                $this->auditLogger->log($server, $this->getUser(), 'CREATE', null, $this->getServerData($server));

                $this->addFlash('success', 'Servidor "' . $server->getName() . '" creado correctamente.');

                return $this->redirectToRoute('app_servers');
            } catch (\Exception $e) {
                $this->addFlash('error', 'No se pudo crear el servidor. Verifique los datos e intente nuevamente.');
            }
        }

        return $this->render('server/new.html.twig', [
            'server' => $server,
            'services' => $this->entityManager->getRepository(\App\Entity\Service::class)->findAll(),
            'operatingSystems' => $this->entityManager->getRepository(\App\Entity\OperatingSystem::class)->findBy([], ['name' => 'ASC']),
            'tags' => $this->entityManager->getRepository(\App\Entity\ServerTag::class)->findBy([], ['name' => 'ASC']),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_servers_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_SERVICE_ADMIN')]
    public function edit(Server $server, Request $request): Response
    {
        $oldData = $this->getServerData($server);

        if ($request->isMethod('POST')) {
            try {
                $this->updateServerFromRequest($server, $request);

                $this->entityManager->flush();

                $this->auditLogger->log($server, $this->getUser(), 'UPDATE', $oldData, $this->getServerData($server));

                $this->addFlash('success', 'Servidor "' . $server->getName() . '" actualizado correctamente.');

                return $this->redirectToRoute('app_servers');
            } catch (\Exception $e) {
                $this->addFlash('error', 'No se pudo actualizar el servidor. Verifique los datos e intente nuevamente.');
            }
        }

        return $this->render('server/edit.html.twig', [
            'server' => $server,
            'services' => $this->entityManager->getRepository(\App\Entity\Service::class)->findAll(),
            'operatingSystems' => $this->entityManager->getRepository(\App\Entity\OperatingSystem::class)->findBy([], ['name' => 'ASC']),
            'osVersions' => $this->entityManager->getRepository(OperatingSystemVersion::class)->findAll(),
            'tags' => $this->entityManager->getRepository(\App\Entity\ServerTag::class)->findBy([], ['name' => 'ASC']),
        ]);
    }

    #[Route('/{id}/delete', name: 'app_servers_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Server $server, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('delete' . $server->getId(), $request->request->get('_token'))) {
            return $this->redirectToRoute('app_servers');
        }

        $serverData = $this->getServerData($server);

        $this->auditLogger->log($server, $this->getUser(), 'DELETE', $serverData, null);

        // Delete related logs first
        $logs = $this->entityManager->getRepository(\App\Entity\ServerLog::class)->findBy(['server' => $server]);
        foreach ($logs as $log) {
            $this->entityManager->remove($log);
        }

        // Clear services relationship before deleting
        $server->getServices()->clear();

        $this->entityManager->remove($server);
        $this->entityManager->flush();

        $this->addFlash('success', 'Servidor eliminado correctamente.');

        return $this->redirectToRoute('app_servers');
    }

    #[Route('/{id}/restart', name: 'app_servers_restart', methods: ['POST'])]
    #[IsGranted('ROLE_SERVICE_ADMIN')]
    public function restart(Server $server, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('restart' . $server->getId(), $request->request->get('_token'))) {
            return $this->redirectToRoute('app_servers');
        }

        $server->setStatus('restarting');
        $this->entityManager->flush();

        $this->auditLogger->log($server, $this->getUser(), 'RESTART', null, ['status' => 'restarting']);

        $this->addFlash('info', 'Solicitud de reinicio enviada para: ' . $server->getName());

        return $this->redirectToRoute('app_servers');
    }

    #[Route('/export', name: 'app_servers_export', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function export(Request $request): Response
    {
        $name = $request->query->get('name');
        $os = $request->query->get('os');
        $osVersion = $request->query->get('osVersion');
        $description = $request->query->get('description');

        $servers = $this->serverRepository->findWithFilters($name, $os, $osVersion, $description)->getQuery()->getResult();

        $csv = "Nombre,Sistema,Version,IP Gestion,Estado,CPU,RAM,HD,Proveedor,Ubicacion,Usuario SSH,Servicios,Tags,Descripcion\n";
        
        foreach ($servers as $server) {
            $services = implode(';', $server->getServices()->map(fn($s) => $s->getName())->toArray());
            $tags = implode(';', $server->getTags()->map(fn($t) => $t->getName())->toArray());
            
            $osName = $server->getOperatingSystemVersion()?->getOperatingSystem()?->getName() ?? $server->getOs();
            $osVersion = $server->getOperatingSystemVersion()?->getVersion() ?? $server->getOsVersion();
            
            $csv .= sprintf(
                "\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\"\n",
                $this->escapeCsv($server->getName()),
                $this->escapeCsv($osName),
                $this->escapeCsv($osVersion),
                $this->escapeCsv($server->getManagementIp()),
                $this->escapeCsv($server->getStatus()),
                $this->escapeCsv($server->getCpu()),
                $this->escapeCsv($server->getRam()),
                $this->escapeCsv($server->getHd()),
                $this->escapeCsv($server->getProvider()),
                $this->escapeCsv($server->getSite()),
                $this->escapeCsv($server->getSshUser()),
                $this->escapeCsv($services),
                $this->escapeCsv($tags),
                $this->escapeCsv($server->getDescription() ?? '')
            );
        }

        $response = new Response($csv);
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="servidores-' . date('Y-m-d') . '.csv"');

        return $response;
    }

    private function escapeCsv(string $value): string
    {
        return str_replace('"', '""', $value);
    }

    private function updateServerFromRequest(Server $server, Request $request): void
    {
        $server->setName($request->request->get('name', ''));
        $server->setManagementIp($request->request->get('managementIp', ''));
        $server->setSshUser($request->request->get('sshUser', ''));
        $server->setSshPassword($request->request->get('sshPassword', ''));
        $server->setCpu($request->request->get('cpu', ''));
        $server->setRam($request->request->get('ram', ''));
        $server->setHd($request->request->get('hd', ''));
        $server->setProvider($request->request->get('provider', ''));
        $server->setSite($request->request->get('site', ''));
        $server->setDescription($request->request->get('description'));
        $server->setStatus($request->request->get('status', 'active'));

        $osId = $request->request->get('os');
        $osVersion = $request->request->get('osVersion');
        if ($osId) {
            $operatingSystem = $this->entityManager->getRepository(\App\Entity\OperatingSystem::class)->find((int)$osId);
            if ($operatingSystem && $osVersion) {
                $osv = $this->entityManager->getRepository(\App\Entity\OperatingSystemVersion::class)->findOneBy([
                    'operatingSystem' => $operatingSystem,
                    'version' => $osVersion
                ]);
                if ($osv) {
                    $server->setOperatingSystemVersion($osv);
                }
            }
        }

        // Handle services
        $serviceIds = $request->request->all()['services'] ?? [];
        $services = $this->entityManager->getRepository(\App\Entity\Service::class)
            ->findBy(['id' => $serviceIds]);

        $server->getServices()->clear();
        foreach ($services as $service) {
            $server->addService($service);
        }

        // Handle tags
        $tagIds = $request->request->all()['tags'] ?? [];
        $tags = $this->entityManager->getRepository(\App\Entity\ServerTag::class)
            ->findBy(['id' => $tagIds]);

        $server->getTags()->clear();
        foreach ($tags as $tag) {
            $server->addTag($tag);
        }
    }

    private function getServerData(Server $server): array
    {
        return [
            'id' => $server->getId(),
            'name' => $server->getName(),
            'os' => $server->getOs(),
            'osVersion' => $server->getOsVersion(),
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
        ];
    }
}
