<?php

namespace Solcloud\Utils\Entity;

use App\Models\Entity\AttachmentThumb;
use Doctrine\ORM\Mapping as ORM;
use Solcloud\Utils\Entity\Traits\UuidTrait;
use Solcloud\Utils\Interface\IAttachment;
use Solcloud\Utils\Interface\IUuid;

abstract class BaseAttachment implements IUuid, IAttachment
{

    public const ALIAS = 'SolcloudEntityAttachment';
    public const COL_ID = 'id';
    public const COL_UUID = 'uuid';
    public const COL_CREATED = 'created';
    public const COL_NAME = 'name';
    public const COL_PATH = 'path';
    public const COL_BUCKET = 'bucket';
    public const COL_CONTENT_TYPE = 'content_type';
    public const COL_THUMB = 'thumb_id';
    private const JOIN_MAP = [
    ];

    use UuidTrait;
    use Traits\CreatedTrait;
    use Traits\ColumnBuilder;

    /**
     * @var int
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer", name=self::COL_ID)
     */
    protected int $id;

    /**
     * @ORM\Column(type="string", nullable=true, name=self::COL_NAME)
     */
    protected ?string $name;

    /**
     * @ORM\Column(type="string", name=self::COL_PATH)
     */
    protected string $path;

    /**
     * @ORM\Column(type="string", name=self::COL_BUCKET, nullable=true)
     */
    protected ?string $bucket = null;

    /**
     * @var string
     * @ORM\Column(type="string", name=self::COL_CONTENT_TYPE)
     */
    protected string $contentType;

    /**
     * @var AttachmentThumb|null
     * @ORM\OneToOne(targetEntity=AttachmentThumb::class)
     * @ORM\JoinColumn(onDelete="SET NULL", name=self::COL_THUMB)
     */
    protected ?AttachmentThumb $thumb = null;

    public function __construct(string $contentType, ?string $name = null)
    {
        $this->generateUuid();
        $this->generateCreated();
        $this->setContentType($contentType);

        $this->path = 'a_' . time() . '_' . $this->uuid;
        $this->name = $name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    public function getBucket(): ?string
    {
        return $this->bucket;
    }

    public function setBucket(string $bucket): void
    {
        $this->bucket = $bucket;
    }

    public function getName(): string
    {
        return $this->name ?? '';
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getContentType(): string
    {
        return $this->contentType;
    }

    public function setContentType(string $contentType): void
    {
        $this->contentType = mb_strtolower($contentType);
    }

    public function isImage(): bool
    {
        return in_array($this->getContentType(), [
            'image/png',
            'image/jpg',
            'image/jpeg',
        ], true);
    }

    public function isPdf(): bool
    {
        return in_array($this->getContentType(), [
            'application/pdf',
        ], true);
    }

    public function hasThumb(): bool
    {
        return ($this->getThumb() !== null);
    }

    public function getThumb(): ?AttachmentThumb
    {
        return $this->thumb;
    }

    public function setThumb(?AttachmentThumb $thumb): void
    {
        $this->thumb = $thumb;
    }

    public function toArray(): array
    {
        return [
            'path'   => $this->getPath(),
            'bucket' => $this->getBucket(),
        ];
    }

}
