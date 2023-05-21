/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define(
    [
        'ko',
        'jquery',
        'uiComponent',
        'Magento_Ui/js/modal/modal',
        'Magento_Checkout/js/action/place-order',
        'Magento_Checkout/js/action/select-payment-method',
        'Magento_Checkout/js/model/quote',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/model/payment-service',
        'Magento_Checkout/js/checkout-data',
        'Magento_Checkout/js/model/checkout-data-resolver',
        'uiRegistry',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Ui/js/model/messageList',
        'Magento_Ui/js/model/messages',
        'uiLayout',
        'mage/url',
        'Magento_Checkout/js/action/redirect-on-success'
    ],
    function (
        ko,
        $,
        Component,
        modal,
        placeOrderAction,
        selectPaymentMethodAction,
        quote,
        customer,
        paymentService,
        checkoutData,
        checkoutDataResolver,
        registry,
        additionalValidators,
        messageList,
        Messages,
        layout,
        urlBuilder,
        redirectOnSuccessAction
    ) {
        'use strict';
        var platForm = window.checkoutConfig.plat_form;
        return Component.extend({
            defaults: {
                template: 'Ecommage_ShopeePay/payment/shopeepay'
            },
            redirectAfterPlaceOrder: false,
            isPlaceOrderActionAllowed: ko.observable(quote.billingAddress() != null),
            platformType: ko.observable(platForm),
            expiredTime: ko.observable(1200),
            popupInfo: null,

            /**
             * After place order callback
             */
            afterPlaceOrder: function () {
                // Override this function and put after place order logic here
            },

            /**
             * Initialize view.
             *
             * @return {exports}
             */
            initialize: function () {
                var billingAddressCode,
                    billingAddressData,
                    defaultAddressData;

                this._super().initChildren();
                quote.billingAddress.subscribe(function (address) {
                    this.isPlaceOrderActionAllowed(address !== null);
                }, this);
                checkoutDataResolver.resolveBillingAddress();
                billingAddressCode = 'billingAddress' + this.getCode();
                registry.async('checkoutProvider')(function (checkoutProvider) {
                    defaultAddressData = checkoutProvider.get(billingAddressCode);

                    if (defaultAddressData === undefined) {
                        // Skip if payment does not have a billing address form
                        return;
                    }

                    billingAddressData = checkoutData.getBillingAddressFromData();
                    if (billingAddressData) {
                        checkoutProvider.set(
                            billingAddressCode,
                            $.extend(true, {}, defaultAddressData, billingAddressData)
                        );
                    }
                    checkoutProvider.on(billingAddressCode, function (providerBillingAddressData) {
                        checkoutData.setBillingAddressFromData(providerBillingAddressData);
                    }, billingAddressCode);
                });

                return this;
            },

            /**
             * Initialize child elements
             *
             * @returns {Component} Chainable.
             */
            initChildren: function () {
                this.messageContainer = new Messages();
                this.createMessagesComponent();

                return this;
            },

            /**
             * Create child message renderer component
             *
             * @returns {Component} Chainable.
             */
            createMessagesComponent: function () {

                var messagesComponent = {
                    parent: this.name,
                    name: this.name + '.messages',
                    displayArea: 'messages',
                    component: 'Magento_Ui/js/view/messages',
                    config: {
                        messageContainer: this.messageContainer
                    }
                };

                layout([messagesComponent]);

                return this;
            },

            /**
             * Place order.
             */
            placeOrder: function (data, event) {
                var self = this;
                if (event) {
                    event.preventDefault();
                }

                if (this.validate() && additionalValidators.validate()) {
                    this.isPlaceOrderActionAllowed(false);
                    this.getPlaceOrderDeferredObject()
                        .fail(function () {
                            self.isPlaceOrderActionAllowed(true);
                        }).done(function (orderID) {
                            if (self.platformType() === 'pc') {
                                self.getPopupQRCode(orderID);//Luồng MPM - Scan QR
                            } else {
                                self.jumpApp(orderID);//Luồng App-to-app (Jump App)
                            }
                        }
                    );
                    return true;
                }

                return false;
            },

            jumpApp: function (orderID) {
                let self = this,
                    serviceUrl = self.getServiceUrl('create/order');
                $.post(serviceUrl, {
                    order_id: orderID,
                    form_key: $.cookie('form_key')
                }, function (res) {
                    if (res.errcode !== 0) {
                        self.addError(res.message);
                    }

                    window.location.href = res.redirect;
                }).fail(function (err) {
                    self.addError(err);
                    window.location.href = res.redirect;
                });
            },

            cancelOrder: function (orderID) {
                let self = this,
                    serviceUrl = self.getServiceUrl('order/cancel');
                $.post(serviceUrl, {
                    order_id: orderID,
                    form_key: $.cookie('form_key')
                }, function (res) {
                    console.log(res);
                });
            },

            getPopupQRCode: function (orderID) {
                let self = this,
                    serviceUrl = self.getServiceUrl('create/qrcode');
                $.post(serviceUrl, {
                    order_id: orderID,
                    form_key: $.cookie('form_key')
                }, function (res) {
                    console.log(res);
                    if (res.errcode === 0) {
                        let img = $('<img />', {
                            src: res.qr_url,
                            alt: res.store_name
                        });
                        let distance = res.expired_time,
                            title = self.getTitlePopupQRCode(distance);
                        self.createModal(title, img);
                        self.countDownTime(distance);
                        self.paymentCheck(orderID);
                    } else {
                        self.addError(res.message);
                        window.location.href = self.getUrlCheckoutFailure();
                    }
                }).fail(function (err) {
                    self.addError(err);
                });
            },

            getTitlePopupQRCode: function (distance) {
                let expiredTime = this.getExpiredTime(distance);
                return $.mage.__('This qr code is valid for %1 minutes').replace('%1', expiredTime);
            },

            getExpiredTime: function (distance) {
                let minutes = Math.floor((distance % (60 * 60)) / (60)),
                    seconds = Math.floor((distance % (60)));
                return minutes + ':' + seconds;
            },

            countDownTime: function (distance) {
                var self = this,
                    x = setInterval(function() {
                        distance--;
                        self.expiredTime(distance);
                        let title = self.getTitlePopupQRCode(distance);
                        self.popupInfo.setTitle(title);
                        if (distance < 0) {
                            clearInterval(x);
                            self.closeModal();
                            self.popupInfo.setTitle('EXPIRED');
                        }
                    }, 1000);
            },

            createModal: function (title, content) {
                if (typeof content !== "object") {
                    content = $(content);
                }

                this.popupInfo = modal({type: 'popup', responsive: false, title: title, buttons: []}, content);
                this.popupInfo.openModal();
            },

            closeModal: function () {
                this.popupInfo.closeModal();
            },

            paymentCheck: function (orderID) {
                var self = this,
                    serviceUrl = self.getServiceUrl('notify_transaction/check');
                $.post(serviceUrl, {
                    order_id: orderID,
                    form_key: $.cookie('form_key')
                }, function (data) {
                    if (data.errcode === 0 && data.status === 2) {
                        setTimeout(function () {
                            self.paymentCheck(orderID);
                        }, 1500);
                    } else if (data.errcode === 0 && data.status === 3) {
                        self.closeModal();
                        redirectOnSuccessAction.execute();
                    } else {
                        self.cancelOrder(orderID);
                    }
                }).fail(function (err) {
                    self.cancelOrder(orderID);
                });
            },

            getServiceUrl: function (route) {
                return urlBuilder.build('shopeepay/' + route);
            },

            getPlaceOrderDeferredObject: function () {
                return $.when(
                    placeOrderAction(this.getData(), this.messageContainer)
                );
            },

            /**
             * @return {Boolean}
             */
            selectPaymentMethod: function () {
                selectPaymentMethodAction(this.getData());
                checkoutData.setSelectedPaymentMethod(this.item.method);
                return true;
            },

            isChecked: ko.computed(function () {
                return quote.paymentMethod() ? quote.paymentMethod().method : null;
            }),

            isRadioButtonVisible: ko.computed(function () {
                return paymentService.getAvailablePaymentMethods().length !== 1;
            }),

            /**
             * Get payment method data
             */
            getData: function () {
                return {
                    'method': this.item.method,
                    'po_number': null,
                    'additional_data': null
                };
            },

            /**
             * Get payment method type.
             */
            getTitle: function () {
                return this.item.title;
            },

            /**
             * Get payment method code.
             */
            getCode: function () {
                return this.item.method;
            },

            /**
             * @return {Boolean}
             */
            validate: function () {
                return true;
            },

            /**
             * @return {String}
             */
            getBillingAddressFormName: function () {
                return 'billing-address-form-' + this.item.method;
            },

            /**
             * Adds error message
             *
             * @param {String} message
             */
            addError: function (message) {
                messageList.addErrorMessage({
                    message: message
                });
            },

            /**
             * Dispose billing address subscriptions
             */
            disposeSubscriptions: function () {
                // dispose all active subscriptions
                var billingAddressCode = 'billingAddress' + this.getCode();

                registry.async('checkoutProvider')(function (checkoutProvider) {
                    checkoutProvider.off(billingAddressCode);
                });
            }
        });
    }
);
