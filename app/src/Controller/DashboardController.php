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

    #[Route('admin/dashboard', name: 'admin_dashboard')]
    public function index(Request $request): Response
    {
        // Fetch Aggregated Data for Active Policies
        $aggregatedData = $this->policyRepository->findDataSummary();

        // Search Broker and Fetch Related Policies
        $brokerName = $request->query->get('broker');
        $brokerPolicies = [];
       
        if ($brokerName) {
            $broker = $this->brokerRepository->findOneBy(['name' => $brokerName]);

            if (!$broker) {
                $this->addFlash('error', "'{$brokerName}' does not exist.");
                return $this->redirectToRoute('admin_dashboard');
            }

            $brokerPolicies = $this->policyRepository->findByBroker($broker);
            if(empty($brokerPolicies)) {
                $message = "No active policy found for '".$broker->getName()."'";
                $this->addFlash('info', $message);
            }
        }

        return $this->render('dashboard/index.html.twig', [
            'aggregatedData' => $aggregatedData[0] ?? [],
            'brokerName' => $brokerName,
            'brokerPolicies' => $brokerPolicies
        ]);
    }
}
