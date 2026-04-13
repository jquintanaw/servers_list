<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\OperatingSystemVersion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class OperatingSystemVersionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OperatingSystemVersion::class);
    }

    public function findByOperatingSystem(int $operatingSystemId): array
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.operatingSystem = :osId')
            ->setParameter('osId', $operatingSystemId)
            ->orderBy('v.version', 'ASC')
            ->getQuery()
            ->getResult();
    }
}