<?php

namespace App\Command;

use App\Entity\Event;
use App\Entity\Broker;
use App\Entity\Client;
use App\Entity\Policy;
use League\Csv\Reader;
use App\Entity\Insurer;
use App\Entity\Product;
use App\Entity\Financials;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:import-policies',
    description: 'Imports insurance policies from broker CSV files'
)]
class ImportPoliciesCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;
    private string $dataDirectory;

    public function __construct(
        EntityManagerInterface $entityManager,
        LoggerInterface $logger
    ) {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->dataDirectory = __DIR__ . '/../../var/data';
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Starting Policy Data Import');

        $brokerFiles = [
            'broker1.csv' => 'Broker One',
            // 'broker2.csv' => 'Broker Two',
        ];

        foreach ($brokerFiles as $file => $brokerName) {
            $filePath = $this->dataDirectory . '/' . $file;

            if (!file_exists($filePath)) {
                $io->warning("File not found: $filePath");
                $this->logger->warning("File not found: $filePath");
                continue;
            }

            $this->processFile($filePath, $brokerName, $io);
        }

        $io->success('Policy data import completed.');
        return Command::SUCCESS;
    }

    private function processFile(string $filePath, string $brokerName, SymfonyStyle $io): void
    {
        $io->section("Processing file: " . basename($filePath));

        try {
            $csv = Reader::createFromPath($filePath, 'r');
            $csv->setHeaderOffset(0);
            $records = $csv->getRecords();

            $broker = $this->findOrCreateEntity(Broker::class, $brokerName);
            $batchSize = 50;
            $i = 0;

            $this->entityManager->beginTransaction();

            foreach ($records as $record) {
                $this->processRecord($record, $broker);

                if (++$i % $batchSize === 0) {
                    $this->entityManager->flush();
                    $this->entityManager->clear();
                }
            }

            $this->entityManager->flush();
            $this->entityManager->commit();

            $io->success("Finished processing " . basename($filePath));
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            $this->logger->error("Failed processing {$filePath}: " . $e->getMessage());
            $io->error("Error: " . $e->getMessage());
        }
    }

    private function processRecord(array $record, Broker $broker): void
    {
        $policyNumber = trim($record['PolicyNumber'] ?? '');
        $clientRef = trim($record['ClientRef'] ?? '');

        if (!$policyNumber || !$clientRef) {
            throw new \Exception("Missing required fields (PolicyNumber, ClientRef)");
        }

        $client = $this->findOrCreateClient($clientRef, trim($record['ClientType'] ?? ''), $broker);
        $insurer = $this->findOrCreateEntity(Insurer::class, trim($record['Insurer'] ?? ''), $broker);
        $product = $this->findOrCreateEntity(Product::class, trim($record['Product'] ?? ''), $broker);
        $event = $this->findOrCreateEntity(Event::class, trim($record['BusinessEvent'] ?? ''), $broker);

        $policyRepo = $this->entityManager->getRepository(Policy::class);
        if ($policyRepo->findOneBy(['policy_number' => $policyNumber, 'broker' => $broker])) {
            return;
        }

        $policy = new Policy();
        $policy->setPolicyNumber($policyNumber)
            ->setInsurerPolicyNumber(trim($record['InsurerPolicyNumber'] ?? ''))
            ->setRootPolicyRef(trim($record['RootPolicyRef'] ?? ''))
            ->setPolicyType(trim($record['PolicyType'] ?? ''))
            ->setStartDate($this->parseDate($record['StartDate'] ?? '', true))
            ->setEndDate($this->parseDate($record['EndDate'] ?? ''))
            ->setEffectiveDate($this->parseDate($record['EffectiveDate'] ?? ''))
            ->setRenewalDate($this->parseDate($record['RenewalDate'] ?? '', true))
            ->setBusinessDescription(trim($record['BusinessDescription'] ?? ''))
            ->setClient($client)
            ->setInsurer($insurer)
            ->setBroker($broker)
            ->setProduct($product)
            ->setEvent($event);

        $this->entityManager->persist($policy);

        $financials = new Financials();
        $financials->setPolicy($policy)
            ->setBroker($broker)
            ->setInsuredAmount((float) ($record['InsuredAmount'] ?? 0))
            ->setPremium((float) ($record['Premium'] ?? 0))
            ->setCommission((float) ($record['Commission'] ?? 0))
            ->setAdminFee((float) ($record['AdminFee'] ?? 0))
            ->setIptaAmount((float) ($record['IPTAmount'] ?? 0))
            ->setPolicyFee((float) ($record['PolicyFee'] ?? 0));

        $this->entityManager->persist($financials);
    }

    private function findOrCreateClient(string $clientRef, string $clientType, Broker $broker): Client
    {
        $client = $this->entityManager->getRepository(Client::class)->findOneBy([
            'client_ref' => $clientRef,
            'broker' => $broker
        ]);

        if (!$client) {
            $client = new Client();
            $client->setClientRef($clientRef)
                ->setBroker($broker)
                ->setClientType($clientType);

            $this->entityManager->persist($client);
            $this->entityManager->flush();
        }

        return $client;
    }

    private function findOrCreateEntity(string $entityClass, string $name, ?Broker $broker = null)
    {
        if (!$name) {
            return null;
        }

        $criteria = ['name' => $name];
        if ($broker && property_exists($entityClass, 'broker')) {
            $criteria['broker'] = $broker;
        }

        $repository = $this->entityManager->getRepository($entityClass);
        $entity = $repository->findOneBy($criteria);

        if (!$entity) {
            $entity = new $entityClass();
            $entity->setName($name);
            if ($broker && property_exists($entityClass, 'broker')) {
                $entity->setBroker($broker);
            }
            $this->entityManager->persist($entity);
            $this->entityManager->flush();
        }

        return $entity;
    }

    private function parseDate(string $dateString, bool $allowNull = false): ?\DateTimeInterface
    {
        if ((!$dateString && $allowNull) || ($dateString === 'Not Known')) {
            return null;
        }

        foreach (['d/m/Y', 'Y-m-d', 'm/d/Y'] as $format) {
            if ($date = \DateTime::createFromFormat($format, $dateString)) {
                return $date;
            }
        }

        throw new \Exception("Invalid date format: $dateString");
    }
}
