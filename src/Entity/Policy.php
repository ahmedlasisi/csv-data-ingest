<?php

namespace App\Entity;

use App\Repository\PolicyRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PolicyRepository::class)]
class Policy
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $policy_number = null;

    #[ORM\Column(length: 50)]
    private ?string $insurer_policy_number = null;

    #[ORM\Column(length: 50)]
    private ?string $root_policy_ref = null;

    #[ORM\Column(length: 50)]
    private ?string $policy_type = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $start_date = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $end_date = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $effective_date = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $renewal_date = null;

    #[ORM\Column(length: 140)]
    private ?string $company_description = null;

    #[ORM\ManyToOne(inversedBy: 'policies')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Client $Client = null;

    #[ORM\ManyToOne(inversedBy: 'policies')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Insurer $insurer = null;

    #[ORM\ManyToOne(inversedBy: 'policies')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Product $product = null;

    #[ORM\ManyToOne(inversedBy: 'policies')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Event $event = null;

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

    public function setStartDate(\DateTimeInterface $start_date): static
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

    public function getCompanyDescription(): ?string
    {
        return $this->company_description;
    }

    public function setCompanyDescription(string $company_description): static
    {
        $this->company_description = $company_description;

        return $this;
    }

    public function getClient(): ?Client
    {
        return $this->Client;
    }

    public function setClient(?Client $Client): static
    {
        $this->Client = $Client;

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
}
