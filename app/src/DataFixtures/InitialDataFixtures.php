<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Broker;
use App\Entity\BrokerConfig;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;

class InitialDataFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;
    public function __construct(
        UserPasswordHasherInterface $passwordHasher
    ) {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $user = new User();
        $user->setEmail('admin@example.com');
        $password = 'Admin123';
        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);
        $user->setRoles(['ROLE_ADMIN']); // Set default role
        $manager->persist($user);

        // Broker One
        $broker1 = new Broker();
        $broker1->setName('Broker One');
        // $broker1->setUuid(Uuid::fromString('0191aac7-03ed-7055-94d9-86f49b1da149')); // Convert to Symfony Uuid format
        $manager->persist($broker1);

        // Broker Two
        $broker2 = new Broker();
        $broker2->setName('Broker Two');
        // $broker2->setUuid(Uuid::fromString('0194efae-895b-7d4c-bba4-ab625dc9a567'));
        $manager->persist($broker2);

        // BrokerConfig for Broker One
        $config1 = new BrokerConfig();
        $config1->setBroker($broker1);
        $config1->setFileName('broker1.csv');
        $config1->setFileMapping([
            "PolicyNumber" => "PolicyNumber",
            "InsuredAmount" => "InsuredAmount",
            "StartDate" => "StartDate",
            "EndDate" => "EndDate",
            "AdminFee" => "AdminFee",
            "BusinessDescription" => "BusinessDescription",
            "BusinessEvent" => "BusinessEvent",
            "ClientType" => "ClientType",
            "ClientRef" => "ClientRef",
            "Commission" => "Commission",
            "EffectiveDate" => "EffectiveDate",
            "InsurerPolicyNumber" => "InsurerPolicyNumber",
            "IPTAmount" => "IPTAmount",
            "Premium" => "Premium",
            "PolicyFee" => "PolicyFee",
            "PolicyType" => "PolicyType",
            "Insurer" => "Insurer",
            "RenewalDate" => "RenewalDate",
            "RootPolicyRef" => "RootPolicyRef",
            "Product" => "Product"
        ]);
        $manager->persist($config1);

        // BrokerConfig for Broker Two
        $config2 = new BrokerConfig();
        $config2->setBroker($broker2);
        $config2->setFileName('broker2.csv');
        $config2->setFileMapping([
            "PolicyNumber" => "PolicyRef",
            "InsuredAmount" => "CoverageAmount",
            "StartDate" => "InitiationDate",
            "EndDate" => "ExpirationDate",
            "AdminFee" => "AdminCharges",
            "BusinessDescription" => "CompanyDescription",
            "BusinessEvent" => "ContractEvent",
            "ClientType" => "ConsumerCategory",
            "ClientRef" => "ConsumerID",
            "Commission" => "BrokerFee",
            "EffectiveDate" => "ActivationDate",
            "InsurerPolicyNumber" => "InsuranceCompanyRef",
            "IPTAmount" => "TaxAmount",
            "Premium" => "CoverageCost",
            "PolicyFee" => "ContractFee",
            "PolicyType" => "ContractCategory",
            "Insurer" => "Underwriter",
            "RenewalDate" => "NextRenewalDate",
            "RootPolicyRef" => "PrimaryPolicyRef",
            "Product" => "InsurancePlan"
        ]);
        $manager->persist($config2);

        $manager->flush();
    }
}
