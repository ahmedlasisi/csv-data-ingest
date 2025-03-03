<?php

namespace App\Entity;

use App\Entity\Traits\UuidTrait;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\BrokerRepository;
use App\Entity\Traits\TimestampableTrait;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

#[ORM\Entity(repositoryClass: BrokerRepository::class)]
#[ORM\UniqueConstraint(columns: ['name'])] // Ensure name is unique at DB level
#[ORM\HasLifecycleCallbacks]
class Broker implements BaseEntityInterface
{
    use UuidTrait;
    use TimestampableTrait; // inherits createdAt & updatedAt

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100, unique: true)]
    private ?string $name = null;

    /**
     * @var Collection<int, BrokerClient>
     */
    #[ORM\OneToMany(targetEntity: BrokerClient::class, mappedBy: 'broker')]
    private Collection $broker_clients;

    /**
     * @var Collection<int, Insurer>
     */
    #[ORM\OneToMany(targetEntity: Insurer::class, mappedBy: 'broker')]
    private Collection $insurers;

    /**
     * @var Collection<int, Product>
     */
    #[ORM\OneToMany(targetEntity: Product::class, mappedBy: 'broker')]
    private Collection $products;

    /**
     * @var Collection<int, Event>
     */
    #[ORM\OneToMany(targetEntity: Event::class, mappedBy: 'broker')]
    private Collection $events;

    /**
     * @var Collection<int, Policy>
     */
    #[ORM\OneToMany(targetEntity: Policy::class, mappedBy: 'broker')]
    private Collection $policies;

    /**
     * @var Collection<int, Financials>
     */
    #[ORM\OneToMany(targetEntity: Financials::class, mappedBy: 'broker')]
    private Collection $financials;

    #[ORM\OneToOne(mappedBy: 'broker', cascade: ['persist', 'remove'])]
    private ?BrokerConfig $config = null;

    public function __construct()
    {
        $this->broker_clients = new ArrayCollection();
        $this->insurers = new ArrayCollection();
        $this->products = new ArrayCollection();
        $this->events = new ArrayCollection();
        $this->policies = new ArrayCollection();
        $this->financials = new ArrayCollection();
        $this->createdAt = new \DateTime();
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
        // Normalize broker name (trim, remove special characters, multiple spaces)
        $cleanedName = preg_replace('/\s+/', ' ', trim($name)); // Remove extra spaces
        $cleanedName = preg_replace('/[^A-Za-z0-9 ]/', '', $cleanedName); // Remove special chars
        $this->name = $cleanedName;

        return $this;
    }

    /**
     * @return Collection<int, BrokerClient>
     */
    public function getBrokerClients(): Collection
    {
        return $this->broker_clients;
    }

    public function addBrokerClient(BrokerClient $broker_client): static
    {
        if (!$this->broker_clients->contains($broker_client)) {
            $this->broker_clients->add($broker_client);
            $broker_client->setBroker($this);
        }

        return $this;
    }

    public function removeBrokerClient(BrokerClient $broker_client): static
    {
        if ($this->broker_clients->removeElement($broker_client)) {
            // set the owning side to null (unless already changed)
            if ($broker_client->getBroker() === $this) {
                $broker_client->setBroker(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Insurer>
     */
    public function getInsurers(): Collection
    {
        return $this->insurers;
    }

    public function addInsurer(Insurer $insurer): static
    {
        if (!$this->insurers->contains($insurer)) {
            $this->insurers->add($insurer);
            $insurer->setBroker($this);
        }

        return $this;
    }

    public function removeInsurer(Insurer $insurer): static
    {
        if ($this->insurers->removeElement($insurer)) {
            // set the owning side to null (unless already changed)
            if ($insurer->getBroker() === $this) {
                $insurer->setBroker(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Product>
     */
    public function getProducts(): Collection
    {
        return $this->products;
    }

    public function addProduct(Product $product): static
    {
        if (!$this->products->contains($product)) {
            $this->products->add($product);
            $product->setBroker($this);
        }

        return $this;
    }

    public function removeProduct(Product $product): static
    {
        if ($this->products->removeElement($product)) {
            // set the owning side to null (unless already changed)
            if ($product->getBroker() === $this) {
                $product->setBroker(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Event>
     */
    public function getEvents(): Collection
    {
        return $this->events;
    }

    public function addEvent(Event $event): static
    {
        if (!$this->events->contains($event)) {
            $this->events->add($event);
            $event->setBroker($this);
        }

        return $this;
    }

    public function removeEvent(Event $event): static
    {
        if ($this->events->removeElement($event)) {
            // set the owning side to null (unless already changed)
            if ($event->getBroker() === $this) {
                $event->setBroker(null);
            }
        }

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
            $policy->setBroker($this);
        }

        return $this;
    }

    public function removePolicy(Policy $policy): static
    {
        if ($this->policies->removeElement($policy)) {
            // set the owning side to null (unless already changed)
            if ($policy->getBroker() === $this) {
                $policy->setBroker(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Financials>
     */
    public function getFinancials(): Collection
    {
        return $this->financials;
    }

    public function addFinancial(Financials $financial): static
    {
        if (!$this->financials->contains($financial)) {
            $this->financials->add($financial);
            $financial->setBroker($this);
        }

        return $this;
    }

    public function removeFinancial(Financials $financial): static
    {
        if ($this->financials->removeElement($financial)) {
            // set the owning side to null (unless already changed)
            if ($financial->getBroker() === $this) {
                $financial->setBroker(null);
            }
        }

        return $this;
    }

    public function getConfig(): ?BrokerConfig
    {
        return $this->config;
    }

    public function setConfig(BrokerConfig $config): static
    {
        // set the owning side of the relation if necessary
        if ($config->getBroker() !== $this) {
            $config->setBroker($this);
        }

        $this->config = $config;

        return $this;
    }
}
