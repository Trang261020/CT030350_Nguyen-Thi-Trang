<?php
/**
 * Copyright © 2021 Ecommage. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ecommage\SvgWebImages\Plugin\File\Validator;

use Magento\MediaStorage\Model\File\Validator\NotProtectedExtension;
use Ecommage\SvgWebImages\Helper\ImageHelper;

class NotProtectedExtensionPlugin
{
    /**
     * @var ImageHelper
     */
    private $imageHelper;

    /**
     * NotProtectedExtensionPlugin constructor.
     * @param ImageHelper $imageHelper
     */
    public function __construct(
        ImageHelper $imageHelper
    ) {
        $this->imageHelper = $imageHelper;
    }

    /**
     * Remove vector images from protected extensions list
     *
     * @param NotProtectedExtension $subject
     * @param $result
     * @return mixed
     * @SuppressWarnings(PHPMD)
     */
    public function afterGetProtectedFileExtensions(NotProtectedExtension $subject, $result)
    {
        $vectorExtensions = $this->imageHelper->getVectorExtensions();

        foreach (array_keys($result) as $extension) {
            if (in_array($extension, $vectorExtensions)) {
                unset($result[$extension]);
            }
        }

        return $result;
    }
}
