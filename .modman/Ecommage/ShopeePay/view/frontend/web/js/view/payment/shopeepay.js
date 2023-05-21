define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'shopeepay',
                component: 'Ecommage_ShopeePay/js/view/payment/method-renderer/shopeepay-method'
            }
        );
        return Component.extend({});
    }
);
