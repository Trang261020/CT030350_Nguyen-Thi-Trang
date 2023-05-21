define([
    'jquery',
    'uiComponent',
    'mage/storage'
], function ($, Component, storage) {
    'use strict';

    var xhr = {};

    return Component.extend({
        defaults: {
            template: 'Amasty_PromoCountdown/countdown',
            serviceUrl: false,
            firstRun: true,

            days: 0,
            hours: 0,
            min: 0,
            sec: 0,

            link: null,
            targetTime: false,
            startTime: false,
            isVisible: false,
            secondsLeft: false
        },

        initObservable: function () {
            this._super().observe('days hours min sec isVisible secondsLeft');

            return this;
        },

        updateValues: function () {
            this.sec(Math.floor((this.secondsLeft() % 60)));

            if (this.firstRun || this.sec() === 59) {
                this.min(Math.floor((this.secondsLeft() % (60 * 60)) / 60));

                if (this.firstRun || this.min() === 59) {
                    this.hours(Math.floor((this.secondsLeft() % (60 * 60 * 24)) / (60 * 60)));

                    if (this.firstRun || this.hours() === 23) {
                        this.days(Math.floor(this.secondsLeft() / (60 * 60 * 24)));
                    }
                }
            }

            if (this.firstRun) {
                this.firstRun = false;
                this.isVisible(true);
            }
        },

        parseResult: function (result) {
            if (result) {
                this.secondsLeft(result);

                var x = setInterval(function () {
                    this.secondsLeft(this.secondsLeft() - 1);
                    this.updateValues();

                    if (this.secondsLeft() === 0) {
                        clearInterval(x);
                        $('#'+this.widgetId).remove();
                        this.isVisible(false);
                    }
                }.bind(this), 1000);
            }
            else{
                $('#'+this.widgetId).remove();
            }
        },

        setupTimer: function () {
            if (!this.firstRun) {
                return true;
            }

            if (xhr.hasOwnProperty(this.targetTime)) {
                xhr[this.targetTime].done(this.parseResult.bind(this));

                return true;
            }

            xhr[this.targetTime] = storage.post(this.serviceUrl,
                JSON.stringify({
                    start: this.startTime,
                    end: this.targetTime
                }),
                false
            ).done(
                this.parseResult.bind(this)
            ).always(
                function () {
                    delete xhr[this.targetTime];
                }.bind(this)
            );

            return true;
        }
    });
});
