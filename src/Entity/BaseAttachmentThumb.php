<?php

namespace Solcloud\Utils\Entity;

use Doctrine\ORM\Mapping as ORM;

class BaseAttachmentThumb extends BaseAttachment
{

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    protected ?int $width = null;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    protected ?int $height = null;

    public function __construct(string $contentType, ?string $name = null)
    {
        parent::__construct($contentType, $name);
        $this->setBucket('thumbs');
    }

    public function setDimension(int $width, int $height): void
    {
        $this->width = $width;
        $this->height = $height;
    }

    public function getWidth(): ?int
    {
        return $this->width;
    }

    public function getHeight(): ?int
    {
        return $this->height;
    }

}
