/**
 * Copyright © Ecommage, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'rjsResolver',
    'mage/translate',
    'jquery-ui-modules/widget'
], function ($, resolver) {
    'use strict';

    $.widget('mage.forgotPass', {

        options: {},

        /** @inheritdoc */
        _create: function () {
            var self = this;
            self._showPopupLogin();
        },

        _showPopupLogin: function () {
            resolver(function () {
                let currentUrl = window.location.href;
                if(currentUrl.indexOf('forgotpassword') >= 0 && $('.page.messages .message-success').length > 0){
                    $('body').find('.amsl-popup-overlay').css('display','flex');
                    $('input[name=user_option][value="sparsh-email"]').trigger('click');
                    return false;
                }
            }.bind(this));
        }
    });

    return $.mage.forgotPass;
});
