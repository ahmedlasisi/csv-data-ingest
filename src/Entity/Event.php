<?php

namespace App\Entity;

use App\Repository\EventRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EventRepository::class)]
#[ORM\UniqueConstraint(columns: ['broker_id', 'name'])]
#[UniqueEntity(fields: ['broker', 'name'], message: 'Broker has a events with name {{ value }} on the system already')]
class Event
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    #[Assert\NotBlank(message: 'Event name cannot be blank')]
    private ?string $name = null;

    /**
     * @var Collection<int, Policy>
     */
    #[ORM\OneToMany(targetEntity: Policy::class, mappedBy: 'event')]
    private Collection $policies;

    #[ORM\ManyToOne(inversedBy: 'events')]
    private ?Broker $broker = null;

    public function __construct()
    {
        $this->policies = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

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
            $policy->setEvent($this);
        }

        return $this;
    }

    public function removePolicy(Policy $policy): static
    {
        if ($this->policies->removeElement($policy)) {
            // set the owning side to null (unless already changed)
            if ($policy->getEvent() === $this) {
                $policy->setEvent(null);
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
