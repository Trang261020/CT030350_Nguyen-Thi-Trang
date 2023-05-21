define([
    'jquery',
    'Magento_Ui/js/lib/validation/validator',
    'countryCodes',
    'mage/translate'
], function ($, validator, countryCode) {
    'use strict';

    let checkExist = setInterval(function(){
        let mobileNumber = $('input[name="customer[mobile_number]"]');
        if(mobileNumber.length){
            clearInterval(checkExist);
            let countryCodeInput = $('input[name="customer[country_code]"]')

            countryCode.setCountryDropdown(mobileNumber, countryCodeInput);

            validator.addRule(
                'validate-mobile-number',
                function (value) {
                    if ($.trim(mobileNumber.val())) {
                        if (mobileNumber.intlTelInput('isValidNumber')) {
                            if(countryCodeInput.length){
                                countryCodeInput.val(mobileNumber.intlTelInput('getSelectedCountryData').iso2).change();
                            }
                        }
                        else{
                            return false;
                        }
                    }
                    return true;
                },
                $.mage.__('Please enter a valid mobile number.')
            );
        }
    }, 100);
});