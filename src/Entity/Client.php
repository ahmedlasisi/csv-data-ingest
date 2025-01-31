<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\ClientRepository;
use App\Entity\Traits\TimestampableTrait;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: ClientRepository::class)]
#[ORM\UniqueConstraint(columns: ['broker_id', 'client_ref'])]
#[UniqueEntity(fields: ['broker', 'client_ref'], message: 'Broker has a client with client_ref: {{ value }} on the system already')]
#[ORM\HasLifecycleCallbacks]

class Client 
{
    use TimestampableTrait; // inherits createdAt & updatedAt

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    #[Assert\NotBlank(message: 'Client Ref cannot be blank')]
    private ?string $client_ref = null;

    #[ORM\Column(length: 50)]
    private ?string $client_type = null;

    /**
     * @var Collection<int, Policy>
     */
    #[ORM\OneToMany(targetEntity: Policy::class, mappedBy: 'client')]
    private Collection $policies;

    #[ORM\ManyToOne(inversedBy: 'clients')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Broker $broker = null;

    public function __construct()
    {
        $this->policies = new ArrayCollection();
    }

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

    /**
     * @return Collection<int, Policy>
     */
    public function getPolicies(): Collection
    {
        return $this->policies;
    }

    public function addPolicy(Policy $policy): static
    {
        if (!$this->policies->contains($policy)) {
            $this->policies->add($policy);
            $policy->setClient($this);
        }

        return $this;
    }

    public function removePolicy(Policy $policy): static
    {
        if ($this->policies->removeElement($policy)) {
            // set the owning side to null (unless already changed)
            if ($policy->getClient() === $this) {
                $policy->setClient(null);
            }
        }

        return $this;
    }

    public function getBroker(): ?Broker
    {
        return $this->broker;
    }

    public function setBroker(?Broker $broker): static
    {
        $this->broker = $broker;

        return $this;
    }
}
