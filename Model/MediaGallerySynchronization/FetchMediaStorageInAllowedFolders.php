<?php

declare(strict_types=1);

namespace MageSuite\MediaGallery\Model\MediaGallerySynchronization;

class FetchMediaStorageInAllowedFolders extends \Magento\MediaGallerySynchronization\Model\FetchMediaStorageFileBatches
{
    protected const MEDIA_GALLERY_IMAGE_FOLDERS_CONFIG_PATH
        = 'system/media_storage_configuration/allowed_resources/media_gallery_image_folders';

    public function __construct(
        protected \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        protected \Psr\Log\LoggerInterface $log,
        protected \Magento\MediaGalleryApi\Api\IsPathExcludedInterface $isPathExcluded,
        protected \Magento\Framework\Filesystem $filesystem,
        protected \Magento\MediaGallerySynchronization\Model\GetAssetsIterator $assetsIterator,
        protected \Magento\Framework\Filesystem\Driver\File $driver,
        protected int $batchSize,
        protected array $fileExtensions
    ) {
        parent::__construct(
            $log,
            $isPathExcluded,
            $filesystem,
            $assetsIterator,
            $driver,
            $batchSize,
            $fileExtensions
        );
    }

    public function execute(): \Traversable
    {
        $i = 0;
        $batch = [];
        $mediaDirectory = $this->filesystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA);

        foreach ($this->getAllowedFolders() as $folder) {
            $path = $mediaDirectory->getAbsolutePath($folder);

            if (!$this->driver->isDirectory($path)) {
                continue;
            }

            foreach ($mediaDirectory->readRecursively($path) as $file) {
                if (!$this->isApplicable($file)) {
                    continue;
                }

                $batch[] = $file;

                if (++$i == $this->batchSize) {
                    yield $batch;
                    $i = 0;
                    $batch = [];
                }
            }
        }

        if (count($batch) > 0) {
            yield $batch;
        }
    }

    private function isApplicable(string $path): bool
    {
        try {
            return $path
                && !str_contains($path, \Magento\Cms\Model\Wysiwyg\Images\Storage::THUMBS_DIRECTORY_NAME)
                && !$this->isPathExcluded->execute($path)
                && preg_match('#\.(' . implode("|", $this->fileExtensions) . ')$# i', $path);
        } catch (\Exception $exception) {
            $this->log->critical($exception);
            return false;
        }
    }

    protected function getAllowedFolders(): array
    {
        return (array)$this->scopeConfig->getValue(
            self::MEDIA_GALLERY_IMAGE_FOLDERS_CONFIG_PATH,
            'default'
        );
    }
}
