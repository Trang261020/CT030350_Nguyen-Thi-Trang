/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * @api
 */
define([
    'underscore',
    'uiRegistry',
    'Magento_Ui/js/form/element/select'
], function (_, registry, Select) {
    'use strict';

    return Select.extend({
        defaults: {
            skipValidation: false,
            imports: {
                cityOptions: '${ $.parentName }.city_id:indexedOptions',
                update: '${ $.parentName }.city_id:value',
                toggleVisibility: '${ $.parentName }.city_id:visible'
            }
        },

        /**
         * Method called every time country selector's value gets changed.
         * Updates all validations and requirements for certain country.
         * @param {String} value - Selected country ID.
         */
        update: function (value) {
            var isWardRequired,
                option,
                city = registry.get(this.parentName + '.' + 'city_id'),
                cityOptions = city.indexedOptions;

            if (!value) {
                return;
            }

            option = _.isObject(cityOptions) && cityOptions[value];

            if (!option) {
                this.toggleVisibility(false);
                return;
            } else {
                this.toggleVisibility(true);
            }
            if (_.isEmpty(this.indexedOptions)) {
                this.toggleVisibility(false);
            } else {
                this.toggleVisibility(true);
            }

            if (option['is_city_visible'] === false) {
                this.toggleVisibility(false);
            }

            isWardRequired = true;

            if (!isWardRequired) {
                this.error(false);
            }

            this.required(isWardRequired);
            this.validation['required-entry'] = isWardRequired;

            registry.get(this.customName, function (input) {
                input.required(isWardRequired);
                input.validation['required-entry'] = isWardRequired;
                input.validation['validate-not-number-first'] = !this.options().length;
            }.bind(this));
        },

        onUpdate: function () {
            this._super();
            this.updateWardText(this.value());
        },

        /**
         * Update ward text field.
         */
        updateWardText: function(value) {
            var textField = registry.get(this.parentName + '.' + 'ward'),
                textValue = '';

            if (!textField) {
                return;
            }

            if (value !== '') {
                var option = this.indexedOptions[value];
                if (!_.isUndefined(option)) {
                    textValue = this.indexedOptions[value].label;
                }
            }
            textField.value(textValue);
        },

        /**
         * Set visibility dependencies.
         */
        toggleVisibility: function(visible) {

            var visible = _.isArray(visible) ? visible.length : visible;
            var cityField = registry.get(this.parentName + '.' + 'city_id');
            var textField = registry.get(this.parentName + '.' + 'ward');

            if (!this.isInitialized) {
                this.isInitialized = true;
            }

            if (!textField) {
                return;
            }

            // if (visible) {
                textField.hide();
                this.required(true);
                this.show();
            // } else {
            //     textField.show();
            //     this.required(false);
            //     this.hide();
            // }
        },
    });
});

