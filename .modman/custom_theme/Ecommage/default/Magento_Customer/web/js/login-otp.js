/**
 * Copyright © Ecommage, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'rjsResolver',
    'Magento_Ui/js/modal/modal',
    'mage/url',
    'mage/translate',
    'jquery-ui-modules/widget'
], function ($, resolver, modal,urlBuilder) {
    'use strict';

    $.widget('mage.loginOtp', {

        options: {},

        /** @inheritdoc */
        _create: function () {
            var self = this;
            self._sendOtpLogin();
            self._changeUserOption();
        },
        /**
         *
         * @private
         */
        _sendOtpLogin: function(){
            var self = this;
            if($('#mobile_number_user').is(':checked')){
                $('#login-form .field.password').hide();
                $('#login-form .field.password').removeClass('required');
                resolver(function () {
                    $('label.show-password').hide();
                }.bind(this));

                $('.form-login #send2').on('click',function (e) {
                    if($('#mobile_number_user').is(':checked')){
                        self._ajaxSendOtp();
                    }
                });
            }
        },
        /**
         *
         * @private
         */
        _ajaxSendOtp: function (){
            var self = this;
            setTimeout(function (){
                if(!$('#mobile_number').hasClass('mage-error')){
                    var form = $('#login-form');
                    $.ajax({
                        url: urlBuilder.build('sms/account/sendotplogin'),
                        type: 'POST',
                        data: form.serializeArray(),
                        showLoader: true,
                        success: function (data)
                        {
                            // remove error
                            $('.amsl-error.-default').remove();
                            if(data.isExistCustomer){
                                // OPEN MODAL OTP
                                $('body').find('.amsl-popup-overlay').css('display','none');
                                self._showPopupOtpCode(data.otplogin);
                            }else{
                                $('body').find('.amsl-popup-overlay').css('display','none');
                                self._showPopupNotExistCustomer();
                            }
                        }
                    });
                }
            },100)
        },
        /**
         *
         * @private
         */
        _showPopupNotExistCustomer: function (){
            $('.page-wrapper').append('<div id="modal-not-exsit-customer">Số điện thoại không tồn tại.</div>');
            var options;
            options = {
                'type': 'popup',
                'modalClass': '',
                'responsive': true,
                'innerScroll': true,
                'buttons': []
            };
            modal(options, $('#modal-not-exsit-customer'));
            $('#modal-not-exsit-customer').modal('openModal');
        },
        /**
         *
         * @param content
         * @private
         */
        _showPopupOtpCode: function (content){
            $('.page-wrapper').append('<div id="modal-otp">'+ content +'</div>');
            var options;
            options = {
                'type': 'popup',
                'modalClass': 'modal-verify-otp-wrap',
                'responsive': true,
                'innerScroll': true,
                'buttons': []
            };
            modal(options, $('#modal-otp'));
            $('#modal-otp').modal('openModal');
        },
        /**
         *
         * @private
         */
        _changeUserOption: function(){
            $('body').on('change','input[name=user_option]', function (){
                if($(this).val() == 'sparsh-mobile-number'){
                    $('.field.password').hide();
                    $('.field.password').addClass('required');
                    $('label.show-password').hide();
                }else{
                    $('.field.password').show();
                    $('.field.password').removeClass('required');
                    $('label.show-password').show();
                }
            });
        }
    });

    return $.mage.loginOtp;
});
