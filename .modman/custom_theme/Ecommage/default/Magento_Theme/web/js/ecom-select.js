define([
    'jquery'
], function ($) {
    'use strict';

    $.widget('mage.ecomSelect', {
        _create: function() {
            this.element.on('click', function () {
                $(this).next('.am-swatch-link').trigger('click');
            });
        }
    });

    return $.mage.ecomSelect;
});
