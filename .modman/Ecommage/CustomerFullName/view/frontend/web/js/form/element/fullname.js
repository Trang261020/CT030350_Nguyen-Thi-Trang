define([
    'Magento_Ui/js/form/element/abstract',
    'uiRegistry',
    'Magento_Customer/js/model/customer'
], function (Abstract, uiRegistry,customer) {
    'use strict';

    return Abstract.extend({
        defaults: {
            links: {
                firstName: '${ $.parentName }.firstname:value',
                lastName: '${ $.parentName }.lastname:value'
            }
        },

        initialize: function () {
            this._super();
            this.firstName.subscribe(this.makeFullName.bind(this));
            this.lastName.subscribe(this.makeFullName.bind(this));
            return this;
        },

        /**
         * Calls 'initObservable' of parent
         *
         * @returns {Object} Chainable.
         */
        initObservable: function () {
            this._super();
            this.observe(['firstName', 'lastName']);
            return this;
        },

        onUpdate: function () {
            this._super();
            this.updateNameText(this.value().trim());
        },

        updateNameText: function (value) {
            let firstname = `${value}`,
                lastname = firstname,
                items;
            let eFirst = uiRegistry.get(this.parentName + '.firstname'),
                eLast = uiRegistry.get(this.parentName + '.lastname');
            if(!value || this.value().indexOf(' ') == 0){
                eFirst.value('');
                eLast.value('');
            }else {
                if (value.indexOf(' ') == -1 && !customer.isLoggedIn())
                {
                    eFirst.value(value);
                    eLast.value(value);
                }
            }
            if (typeof value != "undefined" && value.indexOf(' ') !== -1) {
                items = value.trim().split(' ');
                lastname = items.pop();
                firstname = items.join(' ');
                eFirst.value(firstname);
                eLast.value(lastname);
            }
        },

        makeFullName: function () {
            let fullName = '',
                firstName = this.firstName(),
                lastName = this.lastName();
            if (typeof firstName != "undefined") {
                fullName = firstName;
            }

            if (typeof lastName != "undefined") {
                fullName += ' '+ lastName;
            }
            if (firstName == lastName)
            {
                fullName = lastName;
            }

            uiRegistry.get(this.parentName + '.firstname').hide();
            uiRegistry.get(this.parentName + '.lastname').hide();
            this.value(fullName);
        }
    });
});
