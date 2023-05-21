<?php

namespace Ecommage\BlogPost\Model\Layout\Generator;

class Preference
{
     const MOBILE_POST = 'mobile_post';
     const MOBILE_LIST = 'mobile_list';

    /**
     * @param \Amasty\Blog\Model\Layout\Config $subject
     * @param string $result
     * @return string
     */
    public function afterGetConfigIdentifier(
        \Amasty\Blog\Model\Layout\Config $subject,
        $result
    ) {

        if (in_array($result, [self::MOBILE_LIST, self::MOBILE_POST])){
            return 'desktop_list';
        } else {
            return $result ;
        }
    }
}
