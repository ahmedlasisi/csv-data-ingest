<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\PolicyRepository;
use App\Entity\Traits\TimestampableTrait;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: PolicyRepository::class)]
#[ORM\UniqueConstraint(columns: ['broker_id', 'policy_number'])]
#[ORM\UniqueConstraint(columns: ['insurer_id', 'insurer_policy_number'])]
#[UniqueEntity(fields: ['broker', 'policy_number'], message: 'Broker has a policy with policy number: {{ value }} on the system already')]
#[UniqueEntity(fields: ['insurer', 'insurer_policy_number'], message: 'Broker has a policy with insurer policy number: {{ value }} on the system already')]
#[ORM\HasLifecycleCallbacks]

class Policy
{
    use TimestampableTrait; // inherits createdAt & updatedAt

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'Policy Number cannot be blank')]
    private ?string $policy_number = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'Insurer Policy Number cannot be blank')]
    private ?string $insurer_policy_number = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Assert\NotBlank(message: 'Root Policy Ref cannot be blank')]
    private ?string $root_policy_ref = null;

    #[ORM\Column(length: 50)]
    private ?string $policy_type = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $start_date = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $end_date = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $effective_date = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $renewal_date = null;

    #[ORM\Column(length: 140, nullable: true)]
    private ?string $business_description = null;

    #[ORM\ManyToOne(targetEntity: BrokerClient::class, inversedBy: 'policies')]
    #[ORM\JoinColumn(nullable: false)]
    private ?BrokerClient $broker_client = null;

    #[ORM\ManyToOne(inversedBy: 'policies')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Insurer $insurer = null;

    #[ORM\ManyToOne(inversedBy: 'policies')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Product $product = null;

    #[ORM\ManyToOne(inversedBy: 'policies')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Event $event = null;

    #[ORM\OneToOne(targetEntity: Financials::class, mappedBy: 'policy', cascade: ['persist', 'remove'])]
    private ?Financials $financials = null;

    #[ORM\ManyToOne(inversedBy: 'policies')]
    private ?Broker $broker = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPolicyNumber(): ?string
    {
        return $this->policy_number;
    }

    public function setPolicyNumber(string $policy_number): static
    {
        $this->policy_number = $policy_number;

        return $this;
    }

    public function getInsurerPolicyNumber(): ?string
    {
        return $this->insurer_policy_number;
    }

    public function setInsurerPolicyNumber(string $insurer_policy_number): static
    {
        $this->insurer_policy_number = $insurer_policy_number;

        return $this;
    }

    public function getRootPolicyRef(): ?string
    {
        return $this->root_policy_ref;
    }

    public function setRootPolicyRef(string $root_policy_ref): static
    {
        $this->root_policy_ref = $root_policy_ref;

        return $this;
    }

    public function getPolicyType(): ?string
    {
        return $this->policy_type;
    }

    public function setPolicyType(string $policy_type): static
    {
        $this->policy_type = $policy_type;

        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->start_date;
    }

    public function setStartDate(?\DateTimeInterface $start_date): static
    {
        $this->start_date = $start_date;

        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->end_date;
    }

    public function setEndDate(\DateTimeInterface $end_date): static
    {
        $this->end_date = $end_date;

        return $this;
    }

    public function getEffectiveDate(): ?\DateTimeInterface
    {
        return $this->effective_date;
    }

    public function setEffectiveDate(\DateTimeInterface $effective_date): static
    {
        $this->effective_date = $effective_date;

        return $this;
    }

    public function getRenewalDate(): ?\DateTimeInterface
    {
        return $this->renewal_date;
    }

    public function setRenewalDate(\DateTimeInterface $renewal_date): static
    {
        $this->renewal_date = $renewal_date;

        return $this;
    }

    public function getBusinessDescription(): ?string
    {
        return $this->business_description;
    }

    public function setBusinessDescription(string $business_description): static
    {
        $this->business_description = $business_description;

        return $this;
    }

    public function getBrokerClient(): ?BrokerClient
    {
        return $this->broker_client;
    }

    public function setBrokerClient(?BrokerClient $broker_client): static
    {
        $this->broker_client = $broker_client;

        return $this;
    }

    public function getInsurer(): ?Insurer
    {
        return $this->insurer;
    }

    public function setInsurer(?Insurer $insurer): static
    {
        $this->insurer = $insurer;

        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;

        return $this;
    }

    public function getEvent(): ?Event
    {
        return $this->event;
    }

    public function setEvent(?Event $event): static
    {
        $this->event = $event;

        return $this;
    }

    public function getFinancials(): ?Financials
    {
        return $this->financials;
    }

    public function setFinancials(Financials $financials): static
    {
        // set the owning side of the relation if necessary
        if ($financials->getPolicy() !== $this) {
            $financials->setPolicy($this);
        }

        $this->financials = $financials;

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
