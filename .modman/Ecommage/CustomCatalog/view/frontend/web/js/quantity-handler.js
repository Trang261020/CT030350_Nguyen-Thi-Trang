/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
], function ($) {
    'use strict';

    $.widget('mage.quantityHandler', {
        options: {
            minQty: 1,
            maxQty: 10000
        },
        /** @inheritdoc */
        _create: function () {
            var self = this,
                timeoutId = 0;
            this.element.on('blur', '[name="qty"]', function () {
                self.setNumberQty($(this));
            });
            this.element.on('click', 'button.change-qty', function () {
                self.setNumberQty($(this));
            });
            this.element.find('button.change-qty').on('mousedown', function() {
                var that = this;
                timeoutId = setInterval(self.setNumberQty.bind(self,$(that)),200);
            }).on('mouseup mouseleave', function() {
                clearInterval(timeoutId);
            });
        },
        setNumberQty: function (element) {
            let role = element.attr('data-role'),
                qty = this.getNumberQty();
            if (role === 'decrease') {
                qty--;
                if (qty < this.options.minQty) {
                    qty = this.options.minQty;
                }
            } else if (role === 'increase') {
                qty++;
                if (qty > this.options.maxQty) {
                    qty = this.options.maxQty;
                }
            }

            this.element.find('[name="qty"]').val(qty);
        },

        /** @inheritdoc */
        getNumberQty: function () {
            let qty = this.element.find('[name="qty"]').val(),
                n = parseInt(qty); //parseFloat
            if (isNaN(n)) {
                return this.options.minQty;
            }

            return n;
        }
    });

    return $.mage.quantityHandler;
});
