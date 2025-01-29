<?php

namespace App\Entity;

use App\Repository\ClientRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ClientRepository::class)]
class Client
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $client_ref = null;

    #[ORM\Column(length: 50)]
    private ?string $client_type = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getClientRef(): ?string
    {
        return $this->client_ref;
    }

    public function setClientRef(string $client_ref): static
    {
        $this->client_ref = $client_ref;

        return $this;
    }

    public function getClientType(): ?string
    {
        return $this->client_type;
    }

    public function setClientType(string $client_type): static
    {
        $this->client_type = $client_type;

        return $this;
    }
}
