/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/*global define*/
/*global FORM_KEY*/
define([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'mage/translate',
    'jquery-ui-modules/widget'
], function ($, modal, $t) {
    'use strict';

    $.widget('mage.viewRule', {
        options: {
            updateUrl: '',
            periodSelect: null,
            popupClass: null
        },
        elementId: null,

        /**
         * @private
         */
        _create: function () {
            this.elementId = $(this.element).attr('id');
            if (this.options.periodSelect) {
                $(document).on('click', this.options.periodSelect, this.viewDetailRule.bind(this));
            }
        },

        /**
         * @public
         */
        viewDetailRule: function (e) {
            var cartRuleId = $(e.target).attr('data-type');
            var couponCode = $(e.target).closest('.coupon-content').find('input.coupon').val();
            var self = this;
            $(self.options.popupClass).children().remove();
            $.ajax({
                url: self.options.updateUrl,
                showLoader: true,
                data: {
                    'cart_rule_id': cartRuleId,
                    'coupon_code': couponCode
                },
                dataType: 'html',
                type: 'POST',
                success: function (response) {

                    var options;

                    options = {
                        'type': 'popup',
                        'modalClass': 'agreements-modal modal-view-voucher',
                        'responsive': true,
                        'innerScroll': true,
                        'buttons': [
                            {
                                text: $t('Close'),
                                class: 'action secondary action-hide-popup',

                                /** @inheritdoc */
                                click: function () {
                                    this.closeModal();
                                }
                            }
                        ]
                    };
                    $(self.options.popupClass).append(response);
                    modal(options, $(self.options.popupClass));
                    $(self.options.popupClass).modal('openModal');

                }
            });
        }
    });

    return $.mage.viewRule;
});
