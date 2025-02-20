<?php

namespace App\Service;

use App\Entity\Broker;
use App\Entity\BrokerClient;
use Doctrine\ORM\EntityManagerInterface;

class FileEntityService
{
    private EntityManagerInterface $entityManager;
    private array $clientCache = [];
    private array $entityCache = [];
    private bool $useCache = true;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function findOrCreateBrokerClient(string $clientRef, string $clientType, Broker $broker): BrokerClient
    {
        $cacheKey = $broker->getId() . '-' . $clientRef;
        if ($this->useCache && isset($this->clientCache[$cacheKey])) {
            return $this->clientCache[$cacheKey];
        }

        $repository = $this->entityManager->getRepository(BrokerClient::class);
        $client = $repository->findOneBy(['client_ref' => $clientRef, 'broker' => $broker]);

        if (!$client) {
            $client = new BrokerClient();
            $client->setClientRef($clientRef)
                   ->setBroker($broker)
                   ->setClientType($clientType);
            $this->entityManager->persist($client);
            $this->entityManager->flush();
        }

        if ($this->useCache) {
            $this->clientCache[$cacheKey] = $client;
        }
        return $client;
    }

    public function findOrCreateEntity(string $entityClass, string $name, ?Broker $broker = null)
    {
        if (!$name) {
            return null;
        }

        $brokerKey = $broker ? $broker->getId() : 'none';
        $cacheKey  = $brokerKey . '-' . $name;
        if ($this->useCache && !isset($this->entityCache[$entityClass])) {
            $this->entityCache[$entityClass] = [];
        }
        if ($this->useCache && isset($this->entityCache[$entityClass][$cacheKey])) {
            return $this->entityCache[$entityClass][$cacheKey];
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

        $this->entityCache[$entityClass][$cacheKey] = $entity;
        return $entity;
    }
}
