<?php

namespace Ecommage\CustomSlide\Plugin\Codazon\Slideshow\Block\Adminhtml;

/**
 * Class Content
 */
class Content
{
    public function afterGetTemplate($subject, $result)
    {
        return 'Ecommage_CustomSlide::slideshow/helper/gallery.phtml';
    }
}
