<?php

namespace Solcloud\Utils\Entity\Traits;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

trait CreatedTrait
{

    /**
     * @ORM\Column(type="datetime_immutable", name="created")
     */
    private DateTimeImmutable $created;

    public function getCreated(): DateTimeImmutable
    {
        return $this->created;
    }

    protected function generateCreated(): void
    {
        $this->created = new DateTimeImmutable();
    }

}
