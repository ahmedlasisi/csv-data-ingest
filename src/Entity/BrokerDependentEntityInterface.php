<?php

namespace App\Entity;

use App\Entity\Broker;

interface BrokerDependentEntityInterface extends BaseEntityInterface
{
    public function setBroker(Broker $broker): static;
    public function getBroker(): ?Broker;
}
