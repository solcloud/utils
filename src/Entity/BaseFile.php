<?php

namespace Solcloud\Utils\Entity;

use Doctrine\ORM\Mapping as ORM;
use Solcloud\Utils\Interface\IUuid;

class BaseFile extends BaseAttachment
{

    public const ALIAS = 'SolcloudEntityFile';
    public const COL_OWNER = 'owner_id';
    const TABLE = 'file';

    use Traits\ColumnBuilder;

    /**
     * @ORM\Column(type="string", name=File::COL_OWNER)
     */
    private string $owner;

    public function __construct(IUuid $owner, string $name, string $contentType)
    {
        $this->owner = $owner->getUuid();
        parent::__construct($contentType, $name);
    }

}
