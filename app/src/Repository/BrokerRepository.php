<?php

namespace App\Repository;

use App\Entity\Broker;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Broker>
 */
class BrokerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Broker::class);
    }

    public function searchByName(string $query): array
    {
        return $this->createQueryBuilder('b')
            ->select('b.uuid, b.name')
            ->where('b.name LIKE :query')
            ->setParameter('query', "%{$query}%")
            ->setMaxResults(5)
            ->getQuery()
            ->getArrayResult();
    }

    //    /**
    //     * @return Broker[] Returns an array of Broker objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('b')
    //            ->andWhere('b.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('b.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Broker
    //    {
    //        return $this->createQueryBuilder('b')
    //            ->andWhere('b.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
