<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Server;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Server>
 */
class ServerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Server::class);
    }

    public function findWithFilters(
        ?string $name = null,
        ?string $os = null,
        ?string $osVersion = null,
        ?string $tag = null,
        ?string $description = null,
        ?int $limit = null,
        ?int $offset = null,
        ?int $tenantId = null
    ): QueryBuilder {
        $qb = $this->createQueryBuilder('s')
            ->leftJoin('s.operatingSystemVersion', 'osv')
            ->addSelect('osv')
            ->leftJoin('osv.operatingSystem', 'os')
            ->addSelect('os')
            ->leftJoin('s.tenant', 't')
            ->addSelect('t');

        if ($tenantId !== null) {
            $qb->andWhere('t.id = :tenantId')
               ->setParameter('tenantId', $tenantId);
        }

        if ($name !== null && $name !== '') {
            $qb->andWhere('LOWER(s.name) LIKE LOWER(:name)')
               ->setParameter('name', '%' . $name . '%');
        }

        if ($os !== null && $os !== '') {
            $qb->andWhere('os.name = :os')
               ->setParameter('os', $os);
        }

        if ($osVersion !== null && $osVersion !== '') {
            $qb->andWhere('osv.version = :osVersion')
               ->setParameter('osVersion', $osVersion);
        }

        if ($tag !== null && $tag !== '') {
            $qb->leftJoin('s.tags', 'st')
               ->andWhere('st.name = :tag')
               ->setParameter('tag', $tag);
        }

        if ($description !== null && $description !== '') {
            $qb->andWhere('LOWER(s.description) LIKE LOWER(:description)')
               ->setParameter('description', '%' . $description . '%');
        }

        $qb->orderBy('s.name', 'ASC');

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }
        if ($offset !== null) {
            $qb->setFirstResult($offset);
        }

        return $qb;
    }

    public function countWithFilters(
        ?string $name = null,
        ?string $os = null,
        ?string $osVersion = null,
        ?string $tag = null,
        ?string $description = null,
        ?int $tenantId = null
    ): int {
        $qb = $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->leftJoin('s.operatingSystemVersion', 'osv')
            ->leftJoin('osv.operatingSystem', 'os')
            ->leftJoin('s.tenant', 't');

        if ($tenantId !== null) {
            $qb->andWhere('t.id = :tenantId')
               ->setParameter('tenantId', $tenantId);
        }

        if ($name !== null && $name !== '') {
            $qb->andWhere('LOWER(s.name) LIKE LOWER(:name)')
               ->setParameter('name', '%' . $name . '%');
        }

        if ($os !== null && $os !== '') {
            $qb->andWhere('os.name = :os')
               ->setParameter('os', $os);
        }

        if ($osVersion !== null && $osVersion !== '') {
            $qb->andWhere('osv.version = :osVersion')
               ->setParameter('osVersion', $osVersion);
        }

        if ($tag !== null && $tag !== '') {
            $qb->leftJoin('s.tags', 'tg')
               ->andWhere('tg.name = :tag')
               ->setParameter('tag', $tag);
        }

        if ($description !== null && $description !== '') {
            $qb->andWhere('LOWER(s.description) LIKE LOWER(:description)')
               ->setParameter('description', '%' . $description . '%');
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function findDistinctOs(): array
    {
        $allOs = $this->getEntityManager()->getRepository(\App\Entity\OperatingSystem::class)->findBy([], ['name' => 'ASC']);
        
        return array_map(fn($os) => ['osName' => $os->getName()], $allOs);
    }

    public function findDistinctOsVersions(): array
    {
        $allVersions = $this->getEntityManager()->getRepository(\App\Entity\OperatingSystemVersion::class)->findAll();
        
        return array_map(fn($v) => [
            'osVersion' => $v->getVersion(),
            'osName' => $v->getOperatingSystem()?->getName() ?? ''
        ], $allVersions);
    }
}