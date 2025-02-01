<?php
// src/Controller/PerformanceTestController.php

namespace App\Controller;

use App\Service\PolicyImportService;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Console\Style\SymfonyStyle;

class PerformanceTestController extends AbstractController
{
    private PolicyImportService $policyImportService;
    private LoggerInterface $logger;
    private ManagerRegistry $managerRegistry;

    public function __construct(
        PolicyImportService $policyImportService,
        LoggerInterface $logger,
        ManagerRegistry $managerRegistry
    ) {
        $this->policyImportService = $policyImportService;
        $this->logger = $logger;
        $this->managerRegistry = $managerRegistry;
    }

    #[Route('/performance_test', name: 'app_performance_test')]

    public function index(): Response
    {
        // Create a SymfonyStyle instance for console-like output.
        // In a controller, you might simulate it with a dummy output,
        // or simply collect output into a variable.
        $io = new SymfonyStyle(
            new \Symfony\Component\Console\Input\ArrayInput([]),
            new \Symfony\Component\Console\Output\BufferedOutput()
        );

        $stopwatch = new Stopwatch();

        // Clone the service so that we can test both variants independently,
        // or create two new instances if needed. For demonstration,
        // we'll assume our service has a setter for toggling the cache.
        // Option 1: Without Cache
        $serviceWithoutCache = clone $this->policyImportService;
        $serviceWithoutCache->setUseCache(false);
        $serviceWithoutCache->clearCaches();

        $stopwatch->start('withoutCache');
        $serviceWithoutCache->importPolicies($io);
        $eventWithoutCache = $stopwatch->stop('withoutCache');
        $durationWithoutCache = $eventWithoutCache->getDuration() / 1000; // seconds

        // Option 2: With Cache (default behavior)
        $serviceWithCache = clone $this->policyImportService;
        $serviceWithCache->setUseCache(true);
        $serviceWithCache->clearCaches();

        $stopwatch->start('withCache');
        $serviceWithCache->importPolicies($io);
        $eventWithCache = $stopwatch->stop('withCache');
        $durationWithCache = $eventWithCache->getDuration() / 1000; // seconds

        // Create an output response:
        $output = sprintf(
            "Duration without cache: %0.3f seconds\nDuration with cache: %0.3f seconds",
            $durationWithoutCache,
            $durationWithCache
        );

        return new Response(nl2br($output));
    }
}
