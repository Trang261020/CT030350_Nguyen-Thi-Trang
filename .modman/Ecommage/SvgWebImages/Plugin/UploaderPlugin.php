<?php

namespace Ecommage\SvgWebImages\Plugin;

use Magento\Framework\File\Uploader;

class UploaderPlugin
{
    /**
     * @param Uploader $subject
     * @param string[] $extensions
     * @return array
     * @SuppressWarnings(PHPMD)
     */
    public function beforeSetAllowedExtensions(Uploader $subject, $extensions = []): array
    {
        $extensions[] = 'svg';
        return [$extensions];
    }

    /**
     * @param Uploader $subject
     * @param string[] $validTypes
     * @return array
     * @SuppressWarnings(PHPMD)
     */
    public function beforeCheckMimeType(Uploader $subject, $validTypes = []): array
    {
        $validTypes[] = 'image/svg';
        $validTypes[] = 'image/svg+xml';
        $validTypes[] = 'image/jpg';
        $validTypes[] = 'image/jpeg';
        $validTypes[] = 'image/png';
        $validTypes[] = 'image/gif';
        return [$validTypes];
    }


}
