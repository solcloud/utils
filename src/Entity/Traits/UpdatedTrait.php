<?php

namespace Solcloud\Utils\Entity\Traits;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

trait UpdatedTrait
{

    /**
     * @ORM\Column(type="datetime_immutable", name="updated", nullable=true)
     */
    private ?DateTimeImmutable $updated = null;

    public function getUpdated(): ?DateTimeImmutable
    {
        return $this->updated;
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function updated(): void
    {
        $this->updated = new DateTimeImmutable();
    }

}
