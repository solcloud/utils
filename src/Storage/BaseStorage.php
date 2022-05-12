<?php

namespace Solcloud\Utils\Storage;

use Aws\S3\S3Client;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\Exception as FsException;
use League\Flysystem\File;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemException;
use Solcloud\Utils\Interface\IAttachment;
use Solcloud\Utils\Storage\Exception\NotFoundException;
use Solcloud\Utils\Storage\Exception\StorageException;

class BaseStorage
{

    protected Filesystem $fileSystem;

    public function __construct(Filesystem $fileSystem)
    {
        $this->fileSystem = $fileSystem;
    }

    public function getAttachmentContent(IAttachment $attachment): string
    {
        return $this->getContent($attachment->getPath(), $attachment->getBucket());
    }

    public function getContent(string $path, ?string $bucket, bool $throwException = false): string
    {
        $adapter = $this->fileSystem->getAdapter();
        if ($adapter instanceof AwsS3Adapter) {
            if ($bucket !== null) {
                $adapter->setBucket($bucket);
            }
        } else {
            if ($bucket !== null) {
                $path = $bucket . DIRECTORY_SEPARATOR . $path;
            }
        }

        try {
            $file = $this->fileSystem->get($path);
            if (!($file instanceof File)) {
                if ($throwException) {
                    throw new NotFoundException("Path is a directory");
                }
                return '';
            }

            $content = $file->read();
            if ($content) {
                return $content;
            }
        } catch (FsException|FilesystemException $ex) {
            if ($throwException) {
                throw new NotFoundException($ex->getMessage(), $ex->getCode(), $ex);
            }
        }

        return '';
    }

    public function saveAttachmentContent(IAttachment $attachment, string &$contents): void
    {
        $this->saveContent($attachment->getPath(), $contents, $attachment->getBucket());
    }

    public function saveContent(string $path, string &$contents, ?string $bucket): void
    {
        $adapter = $this->fileSystem->getAdapter();
        if ($adapter instanceof AwsS3Adapter) {
            $clientApi = $adapter->getClient();
            if ($bucket !== null && $clientApi->doesBucketExist($bucket) === false) {
                /** @var S3Client $clientApi */
                @$clientApi->createBucket(['Bucket' => $bucket]);
            }
            if ($bucket !== null) {
                $adapter->setBucket($bucket);
            }
        } else {
            if ($bucket !== null) {
                $path = $bucket . DIRECTORY_SEPARATOR . $path;
            }
        }

        try {
            $this->fileSystem->put($path, $contents);
        } catch (FsException|FilesystemException $ex) {
            throw new StorageException("File cannot be put into path '{$path}' bucket '{$bucket}'", $ex->getCode(), $ex);
        }
    }

    public function removeAttachmentContent(IAttachment $attachment): void
    {
        $this->removeContent($attachment->getPath(), $attachment->getBucket());
        $thumb = $attachment->getThumb();
        if ($thumb) {
            $this->removeContent($thumb->getPath(), $thumb->getBucket());
        }
    }

    public function removeContent(string $path, ?string $bucket): void
    {
        $adapter = $this->fileSystem->getAdapter();
        if ($adapter instanceof AwsS3Adapter) {
            if ($bucket !== null) {
                $adapter->setBucket($bucket);
            }
        } else {
            if ($bucket !== null) {
                $path = $bucket . DIRECTORY_SEPARATOR . $path;
            }
        }

        try {
            $this->fileSystem->delete($path);
        } catch (FsException|FilesystemException $ex) {
            throw new StorageException("File cannot be deleted at path '{$path}' bucket '{$bucket}'", $ex->getCode(), $ex);
        }
    }

}
