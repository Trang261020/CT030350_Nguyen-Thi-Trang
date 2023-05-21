define([
    'jquery',
    'countryCode'
], function ($, countryCode) {
    'use strict';

    return function (Component) {
        return Component.extend({
            initialize: function () {
                this._super();
                if(this.checkModuleIsEnable().moduleStatus){
                    let checkExist = setInterval(function(){
                        let mobileNumber = $('#login-mobile-number');
                        if(mobileNumber.length){
                            clearInterval(checkExist);
                            let countryCodeInput = $('input[name="country_code"]');
                            if($('.sparsh-mobile-number-login-option').length) {
                                countryCode.changeLoginUser($('input[name="user_option"]'), 'login[username]');
                            }
                            else{
                                mobileNumber.attr('name', 'login[username]');
                            }
                            countryCode.setCountryDropdown(mobileNumber, countryCodeInput);

                            mobileNumber.blur(function() {
                                if ($.trim(mobileNumber.val())) {
                                    countryCodeInput.val(mobileNumber.intlTelInput('getSelectedCountryData').iso2)
                                }
                            });

                            countryCode.validateMobileNumber(mobileNumber, countryCodeInput);

                        }
                    }, 100);
                }
            },

            checkModuleIsEnable: function () {
                return window.checkoutConfig.mobileNumberConfig;
            }
        });
    }
});
