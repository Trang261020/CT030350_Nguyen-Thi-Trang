define([
    'jquery'
], function ($) {
    "use strict";
    return function (config, element) {
        $(element).click(function () {
            var form = $(config.form),
                baseUrl = form.attr('action'),
                addToCartUrl = config.addToCartUrl;
            form.attr('action', addToCartUrl);
            form.trigger('submit');
            form.attr('action', baseUrl);
            return false;
        });
    }
});
