<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Server;
use App\Entity\ServerTag;
use App\Entity\OperatingSystem;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
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
    public function index(Request $request): Response
    {
        $currentUser = $this->getUser();
        $isGlobalAdmin = $currentUser && in_array('ROLE_ADMIN', $currentUser->getRoles(), true);
        
        $tenantId = null;
        $selectedTenant = null;
        
        // Admins can see all servers or filter by tenant
        if ($isGlobalAdmin) {
            $selectedTenant = $request->query->get('tenant');
            if ($selectedTenant) {
                $tenantId = (int) $selectedTenant;
            }
        } elseif ($currentUser && $currentUser->getTenant()) {
            // Non-admin users can only see their tenant's stats
            $tenantId = $currentUser->getTenant()->getId();
            $selectedTenant = $tenantId;
        }

        // Start query builder
        $qb = $this->entityManager->getRepository(Server::class)->createQueryBuilder('s');
        
        // Apply tenant filter if needed
        if ($tenantId) {
            $qb->leftJoin('s.tenant', 't')
               ->andWhere('t.id = :tenantId')
               ->setParameter('tenantId', $tenantId);
        }

        // Total servers
        $totalServers = (clone $qb)->select('COUNT(s)')->getQuery()->getSingleScalarResult();

        // Servers by OS
        $serversByOs = (clone $qb)
            ->leftJoin('s.operatingSystemVersion', 'osv')
            ->leftJoin('osv.operatingSystem', 'os')
            ->select('COALESCE(os.name, s.os) as osName, COUNT(s) as count')
            ->groupBy('osName')
            ->getQuery()
            ->getResult();

        // Servers by status
        $serversByStatus = (clone $qb)
            ->select('s.status, COUNT(s) as count')
            ->groupBy('s.status')
            ->getQuery()
            ->getResult();

        // Tags usage
        $allServers = (clone $qb)->getQuery()->getResult();
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
        $serversByOsVersion = (clone $qb)
            ->leftJoin('s.operatingSystemVersion', 'osv')
            ->select('COALESCE(osv.version, s.osVersion) as osVersion, COUNT(s) as count')
            ->groupBy('osVersion')
            ->getQuery()
            ->getResult();

        $tenants = [];
        if ($isGlobalAdmin) {
            $tenants = $this->entityManager->getRepository(\App\Entity\Tenant::class)->findBy([], ['name' => 'ASC']);
        }

        return $this->render('statistics/index.html.twig', [
            'totalServers' => $totalServers,
            'serversByOs' => $serversByOs,
            'serversByStatus' => $serversByStatus,
            'tagsUsage' => $tagsUsage,
            'serversByOsVersion' => $serversByOsVersion,
            'tenants' => $tenants,
            'selectedTenant' => $selectedTenant,
            'isGlobalAdmin' => $isGlobalAdmin,
        ]);
    }
}