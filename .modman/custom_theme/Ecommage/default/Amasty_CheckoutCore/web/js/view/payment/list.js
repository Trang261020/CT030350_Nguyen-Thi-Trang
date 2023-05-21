// Checkout payment methods view mixin
define([
    'ko',
    'Magento_Checkout/js/model/payment-service',
    'Amasty_CheckoutCore/js/view/utils',
    'Magento_Checkout/js/model/quote'
], function (ko, paymentService, viewUtils, quote) {
    'use strict';

    return function (Component) {
        return Component.extend({
            cmsPayment: ko.observable(null),
            isVisibleCms: ko.observable(false),
            cmsPayments: window.checkoutConfig.cms_payments,
            /**
             * add loader block for payment
             */
            isLoading: paymentService.isLoading,
            /**
             * add loader block for payment
             */
            initialize: function () {
                this._super();
                quote.paymentMethod.subscribe(function (payment) {
                    this.showCmsPayment(payment)
                }, this);
            },
            getGroupTitle: function (newValue) {
                var paymentMethodLayoutConfig = viewUtils.getBlockLayoutConfig('payment_method');

                if (newValue().index === 'methodGroup'
                    && paymentMethodLayoutConfig !== null
                ) {
                    return paymentMethodLayoutConfig.title;
                }

                return this._super(newValue);
            },
            showCmsPayment: function (payment) {
                let html = '',
                    visible = true;
                try {
                    let code = payment['method'];
                    html = this.cmsPayments[code];
                } catch (e) {
                    // continue
                }

                if (typeof html == "undefined" || html == '') {
                    html = '';
                    visible = false;
                }
                this.isVisibleCms(visible);
                this.cmsPayment(html);
            },
            getClassMethod: function (method) {
                var paymentMethod = quote.paymentMethod() ? quote.paymentMethod().method : null;
                if (method.item.method == paymentMethod) {
                    return '_active ' + method.item.method;
                }
                return method.item.method;
            }
        });
    };
});
