<?php

namespace App\Repository;

use App\Entity\Broker;
use App\Entity\Policy;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Policy>
 */
class PolicyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Policy::class);
    }

    public function findDataSummary(): array
    {
        return $this->findAllQuery()->getQuery()->getResult();
    }

    public function findBrokerAggregation(): array
    {
        return $this->findAllQuery(
            isByBrokers: true
        )->getQuery()->getResult();
    }

    public function findByBroker(Broker $broker): array
    {
        return $this->findAllQuery(
            isByBrokers: true
        )->where('p.broker = :broker')
        ->setParameter(
            'broker',
            $broker->getId()
        )->getQuery()
        ->getResult();
    }

    public function findAllQuery(bool $isByBrokers = false): QueryBuilder
    {
        $qb = $this->createQueryBuilder('p')
            ->select([
                $isByBrokers ? 'b.uuid AS broker_uuid' : "'' AS brokerUuid",
                $isByBrokers ? 'b.name AS broker_name' : "'All Brokers' AS brokerName" ,
                'COUNT(DISTINCT p.id) AS totalPolicies',
                'COUNT(DISTINCT c.id) AS totalClients',
                'SUM(f.insured_amount) as totalInsuredAmount',
                'SUM(f.premium) as totalPremium',
                'AVG(DATEDIFF(p.end_date, p.start_date)) AS avg_policy_duration',
                'SUM(CASE WHEN p.start_date <= CURRENT_DATE() AND p.end_date >= CURRENT_DATE() THEN 1 ELSE 0 END) as activePolicies'
            ])
           ->innerJoin('p.broker', 'b')
            ->innerJoin('p.broker_client', 'c')
            ->leftJoin('p.financials', 'f');
        if ($isByBrokers) {
            $qb->groupBy('b.id')
            ->orderBy('broker_name', 'ASC');
        }
        return $qb;
    }

    //    /**
    //     * @return Policy[] Returns an array of Policy objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Policy
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
