<?php

namespace Ecommage\CustomSlide\Plugin\Codazon\Slideshow\Block\Widget;

class Slideshow
{
    /**
     * @param $subject
     * @param $result
     *
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetTemplate($subject, $result)
    {
        return 'Ecommage_CustomSlide::slideshow.phtml';
    }

    /**
     * Get key pieces for caching block content
     *
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetCacheKeyInfo($subject, $cacheInfo)
    {
       return array_merge($cacheInfo, ['is_mobile' => $this->isMobile()]);;
    }

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
