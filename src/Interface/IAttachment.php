<?php

declare(strict_types=1);

namespace Solcloud\Utils\Interface;

interface IAttachment
{

    public function getPath(): string;

    public function getBucket(): ?string;

    public function getThumb(): ?IAttachment;

    /**
     * @return array{path: string, bucket: ?string}
     */
    public function toArray(): array;

}
