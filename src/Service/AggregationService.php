<?php

namespace App\Service;

use App\Entity\Broker;
use App\Repository\BrokerRepository;
use App\Repository\PolicyRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AggregationService
{
    private BrokerRepository $brokers;
    private PolicyRepository $brokers_policies;

    public function __construct(BrokerRepository $brokers, PolicyRepository $brokers_policies)
    {
        $this->brokers  = $brokers ;
        $this->brokers_policies = $brokers_policies;
    }

    /**
     * Validates Broker UUID and returns Broker entity.
     */
    public function getBrokerByUuid(string $uuid)
    {
        $broker = $this->brokers->findOneBy(['uuid' => $uuid]);

        if (!$broker) {
            throw new NotFoundHttpException("Broker with UUID {$uuid} not found.");
        }

        return $broker;
    }

    /**
    * Retrieves aggregated summary data.
    */

    public function getAggregatedDataSummary(): array
    {
        return $this->brokers_policies->findDataSummary();
    }

    /**
     * Retrieves aggregated data grouped by brokers.
     */

    public function getAggregatedBrokersData(): array
    {
        return $this->brokers_policies->findBrokerAggregation();
    }

    /**
     * Retrieves aggregated data for a specific broker.
     */
    
    public function getAggregationDataByBroker(Broker $broker): array
    {
        return $this->brokers_policies->findByBroker($broker);
    }
}
