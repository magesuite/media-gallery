<?php
declare(strict_types=1);

namespace MageSuite\MediaGallery\Plugin\Cms\Model\Wysiwyg\Images\Storage;

class UnsetUploadedFileType
{
    public function beforeUploadFile(\Magento\Cms\Model\Wysiwyg\Images\Storage $subject, $targetPath, $type = null): array
    {
        return [$targetPath, null];
    }
}
