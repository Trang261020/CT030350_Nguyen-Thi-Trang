<?php

namespace Ecommage\CustomSlide\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

/**
 * Class Data
 *
 * @package Ecommage\CustomSlide\Helper
 */
class Data extends AbstractHelper
{
    /**
     * @return bool
     */
    public function isMobile()
    {
        $userAgent = $_SERVER["HTTP_USER_AGENT"] ?? null;
        if (!$userAgent) {
            return false;
        }

        return preg_match("/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i", $userAgent);
    }
}
