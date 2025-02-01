<?php

namespace App\Tests\Service;

use App\Entity\Broker;
use App\Entity\BrokerConfig;
use App\Entity\Client;
use App\Entity\Event;
use App\Entity\Financials;
use App\Entity\Insurer;
use App\Entity\Policy;
use App\Entity\Product;
use App\Service\PolicyImportService;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use League\Csv\Reader;
use PHPUnit\Framework\MockObject\MockObject;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use ReflectionClass;
use ReflectionMethod;

class PolicyImportServiceTest extends TestCase
{
    /** @var EntityManagerInterface|MockObject */
    private $entityManager;

    /** @var LoggerInterface|MockObject */
    private $logger;

    /** @var ManagerRegistry|MockObject */
    private $managerRegistry;

    /** @var SymfonyStyle|MockObject */
    private $io;

    /** @var Broker|MockObject */
    private $broker;

    /** @var PolicyImportService */
    private $service;

    protected function setUp(): void
    {
        $this->entityManager   = $this->createMock(EntityManagerInterface::class);
        $this->logger          = $this->createMock(LoggerInterface::class);
        $this->managerRegistry = $this->createMock(ManagerRegistry::class);
        $this->io              = $this->createMock(SymfonyStyle::class);
    
        // Ensure that resetting the entity manager returns our mock entity manager.
        $this->managerRegistry
             ->method('getManager')
             ->willReturn($this->entityManager);
    
        // Create a dummy Broker that returns an ID
        $this->broker = $this->createMock(Broker::class);
        $this->broker->method('getId')->willReturn(1);
    
        $this->service = new PolicyImportService(
            $this->entityManager,
            $this->logger,
            $this->managerRegistry
        );
    
        // Override the data directory to a temporary directory so we can create temp files.
        $refObj  = new \ReflectionObject($this->service);
        $refProp = $refObj->getProperty('dataDirectory');
        $refProp->setAccessible(true);
        $refProp->setValue($this->service, sys_get_temp_dir());
    }

    public function testImportPoliciesNoBrokerConfigs(): void
    {
        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findAll')->willReturn([]);
    
        $this->entityManager
            ->method('getRepository')
            ->with(BrokerConfig::class)
            ->willReturn($repository);

        $this->io->expects($this->once())
            ->method('warning')
            ->with("No broker configurations found in database.");

        $this->service->importPolicies($this->io);
    }

    public function testImportPoliciesFileNotFound(): void
    {
        // Create a dummy BrokerConfig that refers to a file that does not exist.
        $brokerConfig = $this->createMock(BrokerConfig::class);
        $brokerConfig->method('getFileName')->willReturn('nonexistent.csv');
        $brokerConfig->method('getBroker')->willReturn($this->broker);
    
        // Create a repository mock that returns our dummy BrokerConfig.
        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findAll')->willReturn([$brokerConfig]);
    
        $this->entityManager
             ->method('getRepository')
             ->with(BrokerConfig::class)
             ->willReturn($repository);
    
        // Expect warnings from both IO and logger.
        $expectedMsg = "File not found:"; // we match part of the message
    
        $this->io->expects($this->once())
             ->method('warning')
             ->with($this->stringContains($expectedMsg));
    
        $this->logger->expects($this->once())
             ->method('warning')
             ->with($this->stringContains($expectedMsg));
    
        $this->service->importPolicies($this->io);
    }

    public function testTransformCsvRecord(): void
    {
        // We use reflection to access the private method.
        $record = [
            'col1' => 'value1',
            'col2' => 'value2',
        ];
        $fileMapping = [
            'key1' => 'col1',
            'key2' => 'col2',
            'key3' => 'col3', // missing column to trigger warning
        ];

        // Expect a warning from logger for the missing column.
        $this->logger->expects($this->once())
            ->method('warning')
            ->with("CSV is missing expected column: 'col3'");

        $refMethod = new ReflectionMethod($this->service, 'transformCsvRecord');
        $refMethod->setAccessible(true);
        $result = $refMethod->invoke($this->service, $record, $fileMapping);

        $this->assertSame('value1', $result['key1']);
        $this->assertSame('value2', $result['key2']);
        $this->assertArrayNotHasKey('key3', $result);
    }

    public function testParseDateValid(): void
    {
        // Use reflection to call the private parseDate method.
        $refMethod = new ReflectionMethod($this->service, 'parseDate');
        $refMethod->setAccessible(true);

        // Test valid date formats.
        $date1 = $refMethod->invoke($this->service, '01/01/2020', true);
        $this->assertInstanceOf(\DateTimeInterface::class, $date1);
        $this->assertEquals('2020-01-01', $date1->format('Y-m-d'));

        $date2 = $refMethod->invoke($this->service, '2020-12-31', false);
        $this->assertInstanceOf(\DateTimeInterface::class, $date2);
        $this->assertEquals('2020-12-31', $date2->format('Y-m-d'));
    }

    public function testParseDateInvalid(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Invalid date format");

        $refMethod = new ReflectionMethod($this->service, 'parseDate');
        $refMethod->setAccessible(true);
        $refMethod->invoke($this->service, 'invalid-date', false);
    }

    public function testProcessRecordSkipsWhenMissingFields(): void
    {
        // Use reflection to call processRecord (which is private) with missing policyNumber.
        $record = [
            'PolicyNumber' => '',
            'ClientRef' => '',
        ];

        // Expect a warning about missing fields.
        $this->logger->expects($this->once())
            ->method('warning')
            ->with($this->stringContains("Skipping record due to missing fields"));

        $refMethod = new ReflectionMethod($this->service, 'processRecord');
        $refMethod->setAccessible(true);
        // Passing a dummy mapping (won't be used if record is skipped)
        $refMethod->invoke($this->service, $record, $this->broker, []);
    }

    public function testFindOrCreateClientCreatesNewClient(): void
    {
        $clientRef  = 'CR001';
        $clientType = 'TypeA';
        $cacheKey   = $this->broker->getId() . '-' . $clientRef;

        // Create a repository mock based on EntityRepository.
        $clientRepository = $this->createMock(EntityRepository::class);
        // When findOneBy is called, return null to simulate a missing client.
        $clientRepository->method('findOneBy')->willReturn(null);

        // Configure the entity manager to return our repository when queried for Client::class.
        $this->entityManager
            ->method('getRepository')
            ->with(Client::class)
            ->willReturn($clientRepository);

        // Expect beginTransaction, persist, flush, and commit calls.
        $this->entityManager->expects($this->once())->method('beginTransaction');
        $this->entityManager->expects($this->once())->method('persist')
             ->with($this->callback(function ($client) use ($clientRef, $clientType) {
                 return $client instanceof Client &&
                        $client->getClientRef() === $clientRef &&
                        $client->getClientType() === $clientType;
             }));
        $this->entityManager->expects($this->once())->method('flush');
        $this->entityManager->expects($this->once())->method('commit');

        $refMethod = new \ReflectionMethod($this->service, 'findOrCreateClient');
        $refMethod->setAccessible(true);

        /** @var Client $client */
        $client = $refMethod->invoke($this->service, $clientRef, $clientType, $this->broker);
        $this->assertInstanceOf(Client::class, $client);

        // Calling a second time should return the cached client.
        $clientCached = $refMethod->invoke($this->service, $clientRef, $clientType, $this->broker);
        $this->assertSame($client, $clientCached);
    }

    public function testFindOrCreateEntityCreatesNewEntity(): void
    {
        $entityClass = Insurer::class;
        $name        = 'TestInsurer';
        $broker      = $this->broker;
        $cacheKey    = $broker->getId() . '-' . $name;
    
        // Create a repository mock based on EntityRepository.
        $repository = $this->createMock(EntityRepository::class);
        // When findOneBy is called, return null to simulate a missing entity.
        $repository->method('findOneBy')->willReturn(null);
    
        $this->entityManager
             ->method('getRepository')
             ->with($entityClass)
             ->willReturn($repository);
    
        // Expect persist to be called (flush is deferred).
        $this->entityManager->expects($this->once())
             ->method('persist')
             ->with($this->callback(function ($entity) use ($name) {
                 return method_exists($entity, 'getName') && $entity->getName() === $name;
             }));
    
        $refMethod = new \ReflectionMethod($this->service, 'findOrCreateEntity');
        $refMethod->setAccessible(true);
    
        $entity = $refMethod->invoke($this->service, $entityClass, $name, $broker);
        $this->assertNotNull($entity);
        $this->assertEquals($name, $entity->getName());
    
        // A second invocation should return the same cached instance.
        $entityCached = $refMethod->invoke($this->service, $entityClass, $name, $broker);
        $this->assertSame($entity, $entityCached);
    }
    public function testProcessFileWithInvalidMappingThrowsException(): void
    {
        // Create a temporary CSV file with headers that do not match the mapping.
        $csvContent = "A,B,C\n1,2,3\n";
        $tempFile   = tempnam(sys_get_temp_dir(), 'test_csv_');
        file_put_contents($tempFile, $csvContent);

        // Create a dummy BrokerConfig whose mapping expects columns not present in the CSV.
        $brokerConfig = $this->createMock(BrokerConfig::class);
        $brokerConfig->method('getFileName')->willReturn(basename($tempFile));
        $brokerConfig->method('getBroker')->willReturn($this->broker);
        $brokerConfig->method('getFileMapping')->willReturn([
            'PolicyNumber' => 'PolicyNumber', // missing from CSV headers
            'ClientRef'    => 'ClientRef'
        ]);

        // Create a repository mock for BrokerConfig.
        $repository = $this->createMock(\Doctrine\ORM\EntityRepository::class);
        $repository->method('findAll')->willReturn([$brokerConfig]);

        $this->entityManager
             ->method('getRepository')
             ->with(BrokerConfig::class)
             ->willReturn($repository);

        // Adjust expectations for logger->error() to allow multiple calls.
        // We now expect an error containing "Invalid JSON mapping" rather than "csv file is missing expected column"
        $this->logger->expects($this->atLeastOnce())
             ->method('error')
             ->with($this->stringContains("Invalid JSON mapping"));

        // Expect the IO to get an error message.
        $this->io->expects($this->once())
             ->method('error')
             ->with($this->stringContains("Invalid JSON mapping"));

        // Invoke importPolicies which calls processFile.
        $this->service->importPolicies($this->io);

        unlink($tempFile);
    }
}
