<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Server;
use App\Entity\ServerTag;
use App\Entity\OperatingSystem;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/statistics')]
#[IsGranted('ROLE_USER')]
class StatisticsController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    #[Route('', name: 'app_admin_statistics')]
    public function index(): Response
    {
        // Total servers
        $totalServers = $this->entityManager->getRepository(Server::class)->count([]);

        // Servers by OS
        $serversByOs = $this->entityManager->getRepository(Server::class)->createQueryBuilder('s')
            ->leftJoin('s.operatingSystemVersion', 'osv')
            ->leftJoin('osv.operatingSystem', 'os')
            ->select('COALESCE(os.name, s.os) as osName, COUNT(s) as count')
            ->groupBy('osName')
            ->getQuery()
            ->getResult();

        // Servers by status
        $serversByStatus = $this->entityManager->getRepository(Server::class)->createQueryBuilder('s')
            ->select('s.status, COUNT(s) as count')
            ->groupBy('s.status')
            ->getQuery()
            ->getResult();

        // Tags usage
        $allServers = $this->entityManager->getRepository(Server::class)->findAll();
        $tagsUsage = [];
        foreach ($allServers as $server) {
            foreach ($server->getTags() as $tag) {
                $tagName = $tag->getName();
                if (!isset($tagsUsage[$tagName])) {
                    $tagsUsage[$tagName] = 0;
                }
                $tagsUsage[$tagName]++;
            }
        }
        arsort($tagsUsage);
        $tagsUsage = array_slice($tagsUsage, 0, 10, true);

        // Servers by OS version
        $serversByOsVersion = $this->entityManager->getRepository(Server::class)->createQueryBuilder('s')
            ->leftJoin('s.operatingSystemVersion', 'osv')
            ->select('COALESCE(osv.version, s.osVersion) as osVersion, COUNT(s) as count')
            ->groupBy('osVersion')
            ->getQuery()
            ->getResult();

        return $this->render('statistics/index.html.twig', [
            'totalServers' => $totalServers,
            'serversByOs' => $serversByOs,
            'serversByStatus' => $serversByStatus,
            'tagsUsage' => $tagsUsage,
            'serversByOsVersion' => $serversByOsVersion,
        ]);
    }
}