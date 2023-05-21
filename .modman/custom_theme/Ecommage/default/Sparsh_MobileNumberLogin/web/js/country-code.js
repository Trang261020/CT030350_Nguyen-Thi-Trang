define([
    'jquery',
    'jquery/validate',
    'intlTelInput',
    'intlTelInputUtils'
], function ($) {
    'use strict';

    function changeLoginUser(userInput, userName){
        $('.sparsh-user-name.sparsh-email').hide();
        $('.sparsh-user-name.sparsh-mobile-number').show();
        userInput.click(function(){
            let inputValue = $(this).attr('value');
            if (inputValue) {
                let targetValue = $("." + inputValue);
                let user =  $('.sparsh-user-name');
                user.not(targetValue).hide();
                user.find('input').removeAttr('name');
                $(targetValue).show();
                $(targetValue).find('input#email').attr('name', userName);
                $(targetValue).find('input#sparsh-mobiles-number').attr('name', userName);
            }
        });
        setTimeout(function () {
            userInput.filter('input[value="sparsh-mobile-number"]').trigger('click');
        }, 100);
    }

    function setCountryDropdown(telInput, countryCode) {
        let countryCodeValue = countryCode.val();
        telInput.intlTelInput({
            separateDialCode: true,
            autoPlaceholder: false,
            formatOnDisplay: false,
            preventInvalidNumbers: true,
            preferredCountries: [],
            initialCountry: $.trim(countryCodeValue) ? countryCodeValue : 'auto',
            geoIpLookup: function(success, failure) {
                $.get("https://ipinfo.io", function() {}, "jsonp").always(function(resp) {
                    var countryCode = (resp && resp.country) ? resp.country : '';
                    success(countryCode);
                });
            },
            utilsScript: intlTelInputUtils
        });
    }

    function validateMobileNumber(telInput, countryCode) {
        $.validator.addMethod(
            'validate-mobile-number',
            function (value, element){
                if ($.trim(value)) {
                    telInput.val(value);
                    if (telInput.intlTelInput('isValidNumber')) {
                        countryCode.val(telInput.intlTelInput('getSelectedCountryData').iso2);
                        return true;
                    }
                    return false;
                }
            },
            $.mage.__('Please enter a valid mobile number.')
        );
    }

    function validateMobileNumberRegister(telInput, countryCode) {
        $.validator.addMethod(
            'validate-mobile-number-register',
            function (value, element){
                if ($.trim(telInput.val())) {
                    if (telInput.intlTelInput('isValidNumber')) {
                        countryCode.val(telInput.intlTelInput('getSelectedCountryData').iso2);
                        return true;
                    }
                    return false;
                }
            },
            $.mage.__('Please enter a valid mobile number.')
        );
    }
    return {
        changeLoginUser: changeLoginUser,
        setCountryDropdown: setCountryDropdown,
        validateMobileNumber: validateMobileNumber,
        validateMobileNumberRegister: validateMobileNumberRegister
    };
});
