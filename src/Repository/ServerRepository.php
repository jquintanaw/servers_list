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
        ?string $description = null,
        ?int $limit = null,
        ?int $offset = null
    ): QueryBuilder {
        $qb = $this->createQueryBuilder('s')
            ->leftJoin('s.operatingSystemVersion', 'osv')
            ->addSelect('osv')
            ->leftJoin('osv.operatingSystem', 'os')
            ->addSelect('os');

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
        ?string $description = null
    ): int {
        $qb = $this->createQueryBuilder('s')
            ->select('COUNT(s.id)');

        if ($name !== null && $name !== '') {
            $qb->andWhere('LOWER(s.name) LIKE LOWER(:name)')
               ->setParameter('name', '%' . $name . '%');
        }

        if ($os !== null && $os !== '') {
            $qb->leftJoin('s.operatingSystemVersion', 'osv')
              ->leftJoin('osv.operatingSystem', 'os')
              ->andWhere('os.name = :os')
               ->setParameter('os', $os);
        }

        if ($osVersion !== null && $osVersion !== '') {
            $qb->leftJoin('s.operatingSystemVersion', 'osv')
              ->andWhere('osv.version = :osVersion')
               ->setParameter('osVersion', $osVersion);
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
        
        return array_map(fn($v) => ['osVersion' => $v->getVersion()], $allVersions);
    }
}
