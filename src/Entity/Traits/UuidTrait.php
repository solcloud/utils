<?php

namespace Solcloud\Utils\Entity\Traits;

use Ramsey\Uuid\Uuid;
use Doctrine\ORM\Mapping as ORM;

trait UuidTrait
{

    /**
     * @var string
     * @ORM\Column(type="string", name="uuid")
     */
    protected string $uuid;

    public function getUuid(): string
    {
        return $this->uuid;
    }

    protected function generateUuid(): void
    {
        $this->uuid = Uuid::uuid4()->toString();
    }

}
