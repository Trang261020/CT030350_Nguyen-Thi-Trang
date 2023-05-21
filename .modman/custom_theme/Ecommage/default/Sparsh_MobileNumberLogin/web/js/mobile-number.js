define([
    'jquery',
    'countryCode'
], function ($, countryCode) {
    'use strict';

    let mobileNumberRegister = $('.mobile_number_register'),
        countryCodeInput = $('input[name="country_code"]'),
        mobileNumber = $('#mobile_number');

    if($('.sparsh-mobile-number-login-option').length) {
        countryCode.changeLoginUser($('input[name="user_option"]'), 'login[username]');
    }
    countryCode.setCountryDropdown(mobileNumber, countryCodeInput);
    countryCode.setCountryDropdown(mobileNumberRegister, countryCodeInput);
    countryCode.validateMobileNumber(mobileNumber, countryCodeInput);
    countryCode.validateMobileNumberRegister(mobileNumberRegister, countryCodeInput);
});
