<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Server;
use App\Repository\ServerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/servers')]
#[IsGranted('ROLE_SERVICE_ADMIN')]
class BulkServerController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ServerRepository $serverRepository,
    ) {}

    #[Route('/bulk/template', name: 'app_servers_bulk_template')]
    public function template(): Response
    {
        $headers = ['name', 'os', 'osVersion', 'managementIp', 'sshUser', 'sshPassword', 'cpu', 'ram', 'hd', 'provider', 'site', 'description', 'status'];
        $example = [
            'server-001', 'Ubuntu', '22.04', '192.168.1.100', 'admin', 'password123', '4', '8GB', '500GB', 'AWS', 'us-east-1', 'Production server', 'active'
        ];

        $content = implode(',', $headers) . "\n" . implode(',', $example);

        $response = new Response($content);
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="servers_template.csv"');

        return $response;
    }

    #[Route('/bulk', name: 'app_servers_bulk')]
    public function bulk(): Response
    {
        return $this->render('server/bulk.html.twig');
    }

    #[Route('/bulk/upload', name: 'app_servers_bulk_upload', methods: ['POST'])]
    public function upload(Request $request): Response
    {
        $file = $request->files->get('csvFile');
        
        if (!$file) {
            $this->addFlash('error', 'No se ha proporcionado ningún archivo CSV');
            return $this->redirectToRoute('app_servers_bulk');
        }

        $content = file_get_contents($file->getPathname());
        $lines = explode("\n", trim($content));
        $headers = str_getcsv(array_shift($lines));
        
        $errors = [];
        $servers = [];
        $created = 0;

        foreach ($lines as $lineNum => $line) {
            if (empty(trim($line))) continue;
            
            $values = str_getcsv($line);
            if (count($values) !== count($headers)) {
                $errors[] = "Línea " . ($lineNum + 2) . ": número de columnas incorrecto";
                continue;
            }
            
            $row = array_combine($headers, $values);
            $name = $row['name'] ?? '';
            
            if (empty($name)) {
                $errors[] = "Línea " . ($lineNum + 2) . ": nombre requerido";
                continue;
            }
            
            $existing = $this->serverRepository->findOneBy(['name' => $name]);
            if ($existing) {
                $errors[] = "Línea " . ($lineNum + 2) . ": el servidor '$name' ya existe";
                continue;
            }

            $server = new Server();
            $server->setName($name);
            $server->setOs($row['os'] ?? '');
            $server->setOsVersion($row['osVersion'] ?? '');
            $server->setManagementIp($row['managementIp'] ?? '');
            $server->setSshUser($row['sshUser'] ?? 'root');
            $server->setSshPassword($row['sshPassword'] ?? '');
            $server->setCpu($row['cpu'] ?? '');
            $server->setRam($row['ram'] ?? '');
            $server->setHd($row['hd'] ?? '');
            $server->setProvider($row['provider'] ?? '');
            $server->setSite($row['site'] ?? '');
            $server->setDescription($row['description'] ?? '');
            $server->setStatus($row['status'] ?? 'active');

            $user = $this->getUser();
            $isGlobalAdmin = $user && in_array('ROLE_ADMIN', $user->getRoles(), true);
            if (!$isGlobalAdmin && $user && method_exists($user, 'getTenant')) {
                $tenant = $user->getTenant();
                if ($tenant) {
                    $server->setTenant($tenant);
                }
            }

            $this->entityManager->persist($server);
            $servers[] = $row;
            $created++;
        }

        if (empty($errors)) {
            $this->entityManager->flush();
            $this->addFlash('success', "Se han cargado $created servidores correctamente");
        } else {
            foreach ($errors as $error) {
                $this->addFlash('error', $error);
            }
        }

        return $this->render('server/bulk-result.html.twig', [
            'servers' => $servers,
            'count' => $created,
            'errors' => $errors,
        ]);
    }
}