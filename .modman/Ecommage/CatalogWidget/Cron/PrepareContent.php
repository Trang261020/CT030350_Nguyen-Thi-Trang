<?php

namespace Ecommage\NavSyncPromotion\Cron;

use Ecommage\CatalogWidget\Helper\PrepareData;

class PrepareContent
{
    /**
     * @var PrepareData
     */
    protected $helper;

    /**
     * SyncSalesRule constructor.
     *
     * @param PrepareData $helperData
     */
    public function __construct(
        PrepareData $helperData
    ) {
        $this->helperData = $helperData;
    }

    /**
     * job chạy để cập nhập lại nội dung widget trong thư mục tương ứng của widget ở
     * var/view_preprocessed/pub/static/app/code/Ecommage/CatalogWidget
     *
     * @inheritdoc
     */
    public function execute()
    {
        $this->helperData->prepareContent();
    }
}
