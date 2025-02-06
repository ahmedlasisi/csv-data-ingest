<?php

namespace App\Controller;

use App\Repository\PolicyRepository;
use App\Repository\BrokerRepository;
use App\Service\AggregationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DashboardController extends AbstractController
{
    private PolicyRepository $policyRepository;
    private BrokerRepository $brokerRepository;
    private AggregationService $aggregationService;

    public function __construct(
        PolicyRepository $policyRepository,
        BrokerRepository $brokerRepository,
        AggregationService $aggregationService
    ) {
        $this->policyRepository = $policyRepository;
        $this->aggregationService = $aggregationService;
        $this->brokerRepository = $brokerRepository;
    }

    #[Route('/dashboard', name: 'dashboard')]
    public function index(): Response
    {
        $summary = $this->policyRepository->findDataSummary();

        return $this->render('dashboard/index.html.twig', [
            'summary' => $summary[0],
        ]);
    }

    #[Route('/dashboard/search-broker', name: 'search_broker', methods: ['GET'])]
    public function searchBroker(Request $request): JsonResponse
    {
        $query = $request->query->get('q', '');
        $brokers = $this->brokerRepository->searchByName($query);

        return $this->json($brokers);
    }

    #[Route('/dashboard/broker/{uuid}', name: 'dashboard_broker')]
    public function dashboardByBroker(string $uuid): Response
    {
        $broker =  $this->aggregationService->getBrokerByUuid($uuid);
        $summary = $this->policyRepository->findByBroker($broker);
        return $this->render('dashboard/_summary.html.twig', [
            'summary' => $summary,
        ]);
    }
}
