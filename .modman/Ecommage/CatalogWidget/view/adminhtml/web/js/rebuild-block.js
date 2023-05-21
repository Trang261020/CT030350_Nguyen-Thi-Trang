/**
 * Copyright © Ecommage, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'Magento_Ui/js/modal/alert',
], function ($, alert) {
    'use strict';
    return function (config, element) {
        console.log(config);
        if (!$(config.buttonId).length) {
            $('.page-actions-buttons').append($(element).html());
        }

        $('body').on('click', '.rebuild-static-blocks', function () {
            $.ajax({
                type: 'POST',
                dataType: 'json',
                showLoader: true,
                url: config.ajaxUrl,
                data: {form_key: window.FORM_KEY, isAjax: 1}
            }).done(function (config) {
                console.log(config);
                alert({
                    content: config.message,
                    title: $.mage.__('Re-build static blocks cache refreshed.')
                });
            });
        });

    };
});
