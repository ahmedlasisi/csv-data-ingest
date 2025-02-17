<?php

namespace App\Repository;

use App\Entity\Broker;
use App\Entity\Policy;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use App\Service\CacheHelper;

class PolicyRepository extends ServiceEntityRepository
{
    private CacheHelper $cacheHelper;
    private const CACHE_TTL_SUMMARY = 1800; // 30 minutes
    private const CACHE_TTL_BROKER = 900;   // 15 minutes

    public function __construct(ManagerRegistry $registry, CacheHelper $cacheHelper)
    {
        parent::__construct($registry, Policy::class);
        $this->cacheHelper = $cacheHelper;
    }

    public function findDataSummary(): array
    {
        return $this->cacheHelper->get('policy_summary', function () {
            return $this->buildPolicyQuery(onlyActive: true)->getQuery()->getResult();
        }, self::CACHE_TTL_SUMMARY);
    }

    public function findBrokerAggregation(): array
    {
        return $this->cacheHelper->get('policy_by_broker', function () {
            return $this->buildPolicyQuery(isByBrokers: true, onlyActive: true)->getQuery()->getResult();
        }, self::CACHE_TTL_SUMMARY);
    }

    public function findByBroker(Broker $broker): array
    {
        return $this->cacheHelper->get("broker_{$broker->getUuid()}_policies", function () use ($broker) {
            return $this->buildPolicyQuery(isByBrokers: true, onlyActive: true)
                ->andWhere('p.broker = :broker')
                ->setParameter('broker', $broker)
                ->getQuery()
                ->getResult();
        }, self::CACHE_TTL_BROKER);
    }

    public function findAllPolicies(bool $isByBrokers = false): array
    {
        return $this->buildPolicyQuery(isByBrokers: $isByBrokers)->getQuery()->getResult();
    }

    private function buildPolicyQuery(bool $isByBrokers = false, bool $onlyActive = false): QueryBuilder
    {
        $qb = $this->createQueryBuilder('p')
        ->select([
            $isByBrokers ? 'b.uuid AS broker_uuid' : "'' AS brokerUuid",
            $isByBrokers ? 'b.name AS broker_name' : "'All Brokers' AS brokerName" ,
            'COUNT(DISTINCT p.id) AS totalPolicies',
            'COUNT(DISTINCT c.id) AS totalClients',
            'COALESCE(SUM(f.insured_amount), 0) as totalInsuredAmount',
            'COALESCE(SUM(f.premium), 0) as totalPremium',
            'COALESCE(AVG(DATEDIFF(p.end_date, p.start_date)), 0) AS avgPolicyDuration'
        ])
        ->innerJoin('p.broker', 'b')
        ->innerJoin('p.broker_client', 'c')
        ->leftJoin('p.financials', 'f')
        // ->where('p.start_date <= CURRENT_DATE() AND p.end_date >= CURRENT_DATE()')
        ;

        if ($onlyActive) {
            $qb->where('p.start_date <= CURRENT_DATE() AND p.end_date >= CURRENT_DATE()');
        } else {
            $qb->addSelect(
                'COALESCE(SUM(CASE WHEN p.start_date <= CURRENT_DATE() AND p.end_date >= CURRENT_DATE() THEN 1 ELSE 0 END), 0) as activePolicies'
            );
        }

        if ($isByBrokers) {
            $qb->groupBy('b.id')->orderBy('broker_name', 'ASC');
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
