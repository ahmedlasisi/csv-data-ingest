<?php

namespace App\Controller;

use App\Entity\Broker;
use App\Entity\BrokerConfig;
use App\Form\BrokerConfigType;
use App\Service\CacheHelper;
use App\Service\PolicyImportService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/admin/brokers')]
#[IsGranted('ROLE_ADMIN')]
class BrokerConfigAdminUIController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private PolicyImportService $policyImportService;
    private CacheHelper $cacheHelper;

    public function __construct(
        EntityManagerInterface $entityManager,
        PolicyImportService $policyImportService,
        CacheHelper $cacheHelper
    ) {
        $this->entityManager = $entityManager;
        $this->policyImportService = $policyImportService;
        $this->cacheHelper = $cacheHelper;
    }

    /**
     * List all broker configurations (Twig UI).
     */
    #[Route('/config', name: 'broker_config_index', methods: ['GET'])]
    public function index(): Response
    {
        $brokers = $this->entityManager->getRepository(Broker::class)->findAll();

        return $this->render('broker_config/index.html.twig', [
            'brokers' => $brokers
        ]);
    }

    /**
     * Create a new broker configuration (Twig UI).
     */
    #[Route('/config/new', name: 'broker_config_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        return $this->handleForm($request, new BrokerConfig(), false);
    }

    /**
     * Edit an existing broker configuration (Twig UI).
     */
    #[Route('/config/edit/{uuid}', name: 'broker_config_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, string $uuid): Response
    {
        $config = $this->getBrokerConfigByUuid($uuid);
        return $this->handleForm($request, $config, true);
    }

    /**
     * Delete a broker configuration (Twig UI).
     */
    #[Route('/config/delete/{uuid}', name: 'broker_config_delete', methods: ['POST'])]
    public function delete(Request $request, string $uuid): Response
    {
        $config = $this->getBrokerConfigByUuid($uuid);

        if (!$config) {
            throw $this->createNotFoundException("Broker configuration not found.");
        }

        if ($this->isCsrfTokenValid('delete' . $config->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($config);
            $this->entityManager->flush();

            $this->addFlash('success', 'Broker configuration deleted successfully.');
        }

        return $this->redirectToRoute('broker_config_index');
    }

    /**
     * Handles broker configuration form submission (for new/edit).
     */
    private function handleForm(Request $request, BrokerConfig $config, bool $isEdit): Response
    {
        $form = $this->createForm(BrokerConfigType::class, $config);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($config);
            $this->entityManager->flush();

            if ($isEdit) {
                $this->cacheHelper->invalidate('aggregation_by_broker');
                $this->cacheHelper->invalidate('aggregation_summary');
                $message = 'Broker configuration updated successfully.';
            } else {
                $message = 'Broker configuration created successfully.';
            }

            $this->addFlash('success', $message);
            return $this->redirectToRoute('broker_config_index');
        }

        return $this->render('broker_config/form.html.twig', [
            'form' => $form->createView(),
            'is_edit' => $isEdit
        ]);
    }

    /**
     * Retrieves a BrokerConfig by Broker UUID, throwing 404 if not found.
     */
    private function getBrokerConfigByUuid(string $uuid): BrokerConfig
    {
        $broker = $this->entityManager->getRepository(Broker::class)->findOneBy(['uuid' => $uuid]);

        if (!$broker) {
            throw $this->createNotFoundException("Broker not found.");
        }

        $config = $broker->getConfig();
        if (!$config) {
            throw $this->createNotFoundException("Broker configuration not found.");
        }

        return $config;
    }
}
