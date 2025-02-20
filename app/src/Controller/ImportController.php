<?php

namespace App\Controller;

use App\Entity\Broker;
use App\Service\PolicyImportService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ImportController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private PolicyImportService $policyImportService;

    public function __construct(
        EntityManagerInterface $entityManager,
        PolicyImportService $policyImportService
    ) {
        $this->entityManager = $entityManager;
        $this->policyImportService = $policyImportService;
    }

    /**
     * @Route("/import/upload", name="import_upload", methods={"POST"})
     */
    public function upload(Request $request): JsonResponse
    {
        $file = $request->files->get('file');
        $brokerId = $request->request->get('broker_id');
        $broker = $this->entityManager->getRepository(Broker::class)->find($brokerId);

        if (!$file || !$broker) {
            return new JsonResponse(['status' => 'error', 'message' => 'Invalid file or broker.']);
        }

        return $this->policyImportService->handleFileUpload($file, $broker);
    }
}
