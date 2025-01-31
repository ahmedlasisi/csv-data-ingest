<?php
namespace App\Entity;

interface BaseEntityInterface
{
    public function setName(string $name): static;
    public function getName(): ?string;
}
