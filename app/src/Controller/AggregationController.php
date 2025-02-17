<?php

namespace App\Controller;

use App\Service\AggregationService;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/api/aggregation')]
class AggregationController extends AbstractController
{
    private AggregationService $aggregationService;

    public function __construct(AggregationService $aggregationService)
    {
        $this->aggregationService = $aggregationService;
    }

    /**
     * Aggregation for all data.
     */
    #[Route('/summary', methods: ['GET'])]
    public function getOverallAggregation(): JsonResponse
    {
        $result = $this->aggregationService->getAggregatedDataSummary();
        return $this->json($result);
    }

    /**
     * Grouped aggregation by broker.
     */
    #[Route('/by-broker', methods: ['GET'])]
    public function getGroupedByBroker(): JsonResponse
    {
        $result = $this->aggregationService->getAggregatedBrokersData();
        return $this->json($result);
    }

    /**
     * Aggregation for a specific broker by UUID.
     */
    #[Route('/by-broker/{uuid}', methods: ['GET'])]
    public function getAggregationByBroker(string $uuid): JsonResponse
    {
        $broker = $this->aggregationService->getBrokerByUuid($uuid);
        $result = $this->aggregationService->getAggregationDataByBroker($broker);

        return $this->json($result);
    }
}
