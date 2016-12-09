<?php

declare(strict_types=1);

namespace Solcloud\Utils;

use Nette\Utils\Image as BaseImage;

class Image extends BaseImage
{

    public function destroy(): void
    {
        $resource = $this->getImageResource();
        if (is_resource($resource)) {
            imagedestroy($resource);
        }
    }

}
