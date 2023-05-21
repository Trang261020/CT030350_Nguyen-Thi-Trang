#!/usr/bin/env bash

php=$1

if [ -z "${php}" ];then
    php=php
fi

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"
ROOT="$(dirname $(dirname "${DIR}"))"

patch -p1 < patches/magento-disable-currency-number-formatter.patch
${php} ${ROOT}/bin/magento c:f
##TODO Your Custom module disable here...
${php} ${ROOT}/bin/magento module:disable -f Magento_PageBuilder Magento_CatalogPageBuilderAnalytics Magento_CmsPageBuilderAnalytics Magento_AwsS3PageBuilder Amasty_BlogPageBuilder Amasty_ReviewPageBuilder Amasty_CheckoutDeliveryDate Magento_TwoFactorAuth
