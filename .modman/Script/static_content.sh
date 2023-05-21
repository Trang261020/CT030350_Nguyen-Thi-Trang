#!/usr/bin/env bash

php=$1

if [ -z "${php}" ];then
    php=php
fi

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"
ROOT="$(dirname $(dirname "${DIR}"))"

${php} ${ROOT}/bin/magento setup:static-content:deploy -f en_US vi_VN