<?php

namespace App\Entity;

use App\Repository\BrokerConfigRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: BrokerConfigRepository::class)]
class BrokerConfig
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'config', targetEntity: Broker::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?Broker $broker = null;

    #[ORM\Column(length: 50, unique: true)]
    #[Assert\NotBlank(message: 'File name cannot be blank')]
    private ?string $file_name = null;

    #[ORM\Column(type: 'json')]
    #[Assert\NotBlank(message: 'File mapping cannot be blank')]
    private array $file_mapping = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBroker(): ?Broker
    {
        return $this->broker;
    }

    public function setBroker(Broker $broker): static
    {
        $this->broker = $broker;

        return $this;
    }

    public function getFileName(): ?string
    {
        return $this->file_name;
    }

    public function setFileName(string $file_name): static
    {
        $this->file_name = $file_name;

        return $this;
    }

    public function getFileMapping(): array
    {
        return $this->file_mapping;
    }

    public function setFileMapping(array $file_mapping): static
    {
        $this->file_mapping = $file_mapping;

        return $this;
    }
}
