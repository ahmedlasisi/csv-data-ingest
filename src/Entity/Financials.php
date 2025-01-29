<?php

namespace App\Entity;

use App\Repository\FinancialsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FinancialsRepository::class)]
class Financials
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2)]
    private ?string $insured_amount = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $premium = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $commission = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $admin_fee = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $tax_amount = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $policy_fee = null;

    #[ORM\OneToOne(inversedBy: 'financials', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?Policy $policy = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getInsuredAmount(): ?string
    {
        return $this->insured_amount;
    }

    public function setInsuredAmount(string $insured_amount): static
    {
        $this->insured_amount = $insured_amount;

        return $this;
    }

    public function getPremium(): ?string
    {
        return $this->premium;
    }

    public function setPremium(?string $premium): static
    {
        $this->premium = $premium;

        return $this;
    }

    public function getCommission(): ?string
    {
        return $this->commission;
    }

    public function setCommission(string $commission): static
    {
        $this->commission = $commission;

        return $this;
    }

    public function getAdminFee(): ?string
    {
        return $this->admin_fee;
    }

    public function setAdminFee(string $admin_fee): static
    {
        $this->admin_fee = $admin_fee;

        return $this;
    }

    public function getTaxAmount(): ?string
    {
        return $this->tax_amount;
    }

    public function setTaxAmount(string $tax_amount): static
    {
        $this->tax_amount = $tax_amount;

        return $this;
    }

    public function getPolicyFee(): ?string
    {
        return $this->policy_fee;
    }

    public function setPolicyFee(string $policy_fee): static
    {
        $this->policy_fee = $policy_fee;

        return $this;
    }

    public function getPolicy(): ?Policy
    {
        return $this->policy;
    }

    public function setPolicy(Policy $policy): static
    {
        $this->policy = $policy;

        return $this;
    }
}
