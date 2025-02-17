<?php

namespace App\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

trait UuidTrait
{
    #[ORM\Column(type: 'uuid', unique: true)]
    private ?Uuid $uuid = null;

    public function getUuid(): ?Uuid
    {
        return $this->uuid;
    }

    #[ORM\PrePersist]
    public function setUuid(): self
    {
        $this->uuid = Uuid::v7();

        return $this;
    }
}
